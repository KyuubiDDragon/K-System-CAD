<script setup lang="ts">
import { ref, computed, onMounted, reactive, type Ref, watch, unref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { User, Groups as Group } from '@/types/User'; // Adjust paths and names if needed
import type { EmployeeOnly } from '@/types/Training'; // Adjust path if needed
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import { useModulePermission } from '@/composables/useModulePermission'; // Permission checking
import ImageLinkWithTooltip from '@/components/Admin/ImageLinkWithTooltip.vue'; // Assuming a reusable component
import LogoutUserButton from '@/components/Admin/LogoutUserButton.vue'; // Session management
import { useToast } from 'vue-toastification'; // Import toast
import { useI18n } from 'vue-i18n';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
// --- Router & Permissions ---
const route = useRoute();
const authStore = useAuthStore();
const { hasModulePermission, hasAllPermissions } = useModulePermission();

// --- Permission Checks (CRITICAL SECURITY FIX - Was hardcoded to true!) ---
const isAdmin = computed(() =>
  hasAllPermissions.value ||
  hasModulePermission('settings', 'admin')
);

const canEdit = computed(() =>
  isAdmin.value ||
  !!route.meta?.canEdit ||
  hasModulePermission('settings', 'admin') ||
  hasAllPermissions.value
);

const canDelete = computed(() =>
  isAdmin.value ||
  !!route.meta?.canDelete ||
  hasModulePermission('settings', 'admin') || // Delete users requires ADMIN permission
  hasAllPermissions.value
);

// --- Component State ---
const users = ref<User[]>([]);
const groups = ref<Group[]>([]);
const Employees = ref<EmployeeOnly[]>([]); // Renamed from Employees to follow conventions
const loadingUsers = ref(false);
const loadingGroups = ref(false);
const loadingEmployees = ref(false);
const savingUser = ref(false);
const updatingUserStatus = ref<number | null>(null); // Store ID of user being banned/unbanned
const resettingPassword = ref(false);
const loadingAction = computed(() => !!updatingUserStatus.value || resettingPassword.value); // Combined loading state for confirm dialog

// --- Dialog States & Data ---
const newUserDialog = ref(false);
const editUserDialog = ref(false);
const banUnbanResetDialog = reactive({
    // Combined confirmation dialog
    show: false,
    type: null as 'ban' | 'unban' | 'resetPassword' | null,
    title: '',
    text: '',
    confirmText: '',
    confirmColor: 'primary',
    action: (() => {}) as () => Promise<void>,
    user: null as User | null,
});

// --- Form Refs & Validation ---
const newUserFormRef = ref<any>(null);
const editUserFormRef = ref<any>(null);
const resetPasswordFormRef = ref<any>(null);
const isNewUserFormValid = ref(false);
const isEditUserFormValid = ref(false);
const isResetPasswordFormValid = ref(false);

const initialNewUserData = { username: '', email: '', groups: [] as number[], password: '' };
const newUser = reactive({ ...initialNewUserData });

const initialEditedUserData = {
    id: null as number | null,
    username: '',
    email: '',
    groups: [] as Group[], // Original groups structure from API
    allGroups: [] as number[], // Deprecated, use groupIds
    groupIds: [] as number[], // Model for the v-select
    mail_header: '',
    mail_footer: '',
    mail_header_neutral: '',
    mail_footer_neutral: '',
    signature: '',
    linked_employee: null as number | null,
    banned: 0, // Keep banned status
};
const editedUser = reactive({ ...initialEditedUserData });

const initialPasswordResetData = { password: '', passwordRetry: '' };
const passwordReset = reactive({ ...initialPasswordResetData });

// Replace errorSnackbar reactive object with toast instance
const toast = useToast();
const { t } = useI18n();

// Update the showSnackbar function
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// Hier sind die zusätzlichen Skript-Eigenschaften, die du zu deinem bestehenden Script hinzufügen solltest:

// Neue Eigenschaften für die Filterung und Suche
const searchQuery = ref('');
const filterStatus = ref('all');
const statusOptions = [
    { title: t('userView.statusAll'), value: 'all' },
    { title: t('userView.statusActive'), value: 'active' },
    { title: t('userView.statusBanned'), value: 'banned' },
];

// Modifizierte headers-Definition (enthält die Status-Spalte)
const headers = computed(() => [
    { title: t('adminUser.headers.username'), key: 'username', sortable: true },
    { title: t('adminUser.headers.email'), key: 'email', sortable: true },
    { title: t('adminUser.headers.employee'), key: 'name', sortable: true },
    { title: t('adminUser.headers.groups'), key: 'groups', sortable: false },
    { title: t('adminUser.headers.status'), key: 'banned', sortable: true },
    { title: t('adminUser.headers.mailHeader'), key: 'mail_header', sortable: false, optional: true },
    { title: t('adminUser.headers.mailFooter'), key: 'mail_footer', sortable: false, optional: true },
    { title: t('adminUser.headers.mailHeaderNeutral'), key: 'mail_header_neutral', sortable: false, optional: true },
    { title: t('adminUser.headers.mailFooterNeutral'), key: 'mail_footer_neutral', sortable: false, optional: true },
    { title: t('adminUser.headers.signature'), key: 'signature', sortable: false, optional: true },
    { title: t('adminUser.headers.lastLogin'), key: 'last_login', sortable: true, align: 'end' },
    { title: t('adminUser.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '160px' },
] as const);

// Computed property für gefilterte Benutzer basierend auf Suchbegriff und Status-Filter
const filteredUsers = computed(() => {
    if (!users.value) return [];

    return users.value.filter(user => {
        // Status-Filter anwenden
        if (filterStatus.value === 'active' && user.banned == 1) return false;
        if (filterStatus.value === 'banned' && user.banned == 0) return false;

        // Suchbegriff anwenden, wenn vorhanden
        if (!searchQuery.value) return true;

        const searchTerm = searchQuery.value.toLowerCase();
        const username = (user.username || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const name = (user.name || '').toLowerCase();

        // Prüfen, ob der Suchbegriff in Username, Email oder verknüpftem Mitarbeiter vorkommt
        return (
            username.includes(searchTerm) || email.includes(searchTerm) || name.includes(searchTerm)
        );
    });
});

// Erweiterte Funktion, um User-Daten zu holen, die auch die Gruppenzuweisung richtig berücksichtigt
const fetchUsers = async () => {
    try {
        loadingUsers.value = true;
        const response = await apiClientAuth.get<any[]>('/admin/user?action=getUserOverview');
        const data = response.data || [];
        // Optional: Log zur Überprüfung der Rohdaten beibehalten
        // console.log(">>> RAW API Response (fetchUsers):", data);

        users.value = data.map(user => {
            // Nimm das 'allGroups'-Feld. Stelle sicher, dass es ein Array ist.
            // Falls 'allGroups' bei manchen Usern fehlt oder null ist, wird ein leeres Array verwendet.
            const groupsData = Array.isArray(user.allGroups) ? user.allGroups : [];

            // Optional: Loggen, was für jeden User zugewiesen wird
            // console.log(`>>> User ${user.id} - Assigning groups:`, groupsData);

            return {
                ...user, // Übernehme alle Felder vom API-User-Objekt
                name: user.name ? `[${user.servicenumber}] ${user.name}` : '', // Formatierung des Namens
                groups: groupsData, // Überschreibe/Setze 'groups' mit dem Inhalt von 'allGroups'
                banned: user.banned || 0,
            };
        });

        // Optional: Logge das finale Ergebnis nach dem Mapping
        // console.log(">>> Processed users.value:", users.value);
    } catch (error: any) {
        console.error('Error fetching users:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Benutzer.', 'error');
        users.value = [];
    } finally {
        loadingUsers.value = false;
    }
};

// --- Validation Rules ---
/**
 * Pflichtfeld-Regel.
 *
 * Der Entwurf verlangt, dass eine Meldung die Ursache benennt: "Diese
 * Dienstnummer ist bereits vergeben" statt "Ungueltige Eingabe". Fuer ein
 * fehlendes Pflichtfeld heisst das, den Namen des Feldes zu nennen - er steht
 * ohnehin als Beschriftung daneben.
 */
const requiredRule = (feld?: string) => (value: any) =>
    (value !== null && value !== undefined && String(value).trim() !== '') ||
    (feld ? `${feld} fehlt.` : 'Dieses Feld muss ausgefüllt werden.');
const multiSelectRequiredRule = (value: any[]) =>
    (value && value.length > 0) || 'Mind. eine Auswahl erforderlich.';
const emailRule = (value: string) => /.+@.+\..+/.test(value) || 'Gültige E-Mail erforderlich.';
const passwordRules = {
    required: (value: string) => !!value || 'Erforderlich.',
    minLength: (value: string) => (value && value.length >= 8) || 'Mind. 8 Zeichen.',
    matchPassword: (value: string) =>
        value === passwordReset.password || 'Passwörter stimmen nicht überein.',
};

// --- Data Fetching ---
const fetchData = async <T,>(
    action: string,
    targetRef: Ref<T[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string,
    mapFn?: (item: any) => T,
    baseEndpoint: string = '/admin/user'
) => {
    loadingRef.value = true;
    try {
        // Adjust endpoint based on action (some might be GET, some POST, some different base)
        let response;
        if (action === 'getEmployees') {
            // Special case for employee endpoint
            baseEndpoint = '/admin/employee/index.php'; // Or just 'admin/employee' if index.php is default/handled
            response = await apiClientAuth.get<any[]>(`${baseEndpoint}?action=${action}`);
        } else {
            // Assume GET for fetching lists, adjust if POST needed
            response = await apiClientAuth.get<any[]>(`${baseEndpoint}?action=${action}`);
        }

        const data = response.data || response.data || [];
        targetRef.value = mapFn ? data.map(mapFn) : data;
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

// Stelle sicher, dass fetchUsers VOR fetchAllInitialData definiert ist

const fetchAllInitialData = async () => {
    // async hinzufügen, falls fetchUsers await benötigt
    // Korrekte Funktion aufrufen, die das Mapping durchführt:
    await fetchUsers(); // Rufe fetchUsers() statt fetchData('getUserOverview', ...) auf

    // Die anderen Daten wie gehabt laden:
    fetchData<Group>('getGroups', groups, loadingGroups, 'Fehler beim Laden der Gruppen.');
    fetchData<EmployeeOnly>(
        'getEmployees',
        Employees,
        loadingEmployees,
        'Fehler beim Laden der Mitarbeiter.',
        (item: any) => ({
            id: item.id,
            name: `[${item.servicenumber}] ${item.name}`,
            servicenumber: item.servicenumber,
        })
    );
};

onMounted(() => {
    fetchAllInitialData();
});

// --- Methods ---

// Dialog Openers/Closers
const openNewUserDialog = () => {
    Object.assign(newUser, { ...initialNewUserData, groups: [] }); // Reset form
    isNewUserFormValid.value = false;
    newUserDialog.value = true;
    setTimeout(() => newUserFormRef.value?.resetValidation(), 100);
};
const closeNewUserDialog = () => (newUserDialog.value = false);

const openEditUserDialog = (user: User) => {
    Object.assign(editedUser, { ...user }); // Load user data
    // Map the user's groups (array of group objects) to an array of group IDs for the v-select model
    editedUser.groupIds = user.groups?.map(g => g.id) || [];
    isEditUserFormValid.value = false;
    editUserDialog.value = true;
    setTimeout(() => editUserFormRef.value?.resetValidation(), 100);
};
const closeEditUserDialog = () => (editUserDialog.value = false);

const openBanDialog = (user: User) => {
    banUnbanResetDialog.type = 'ban';
    banUnbanResetDialog.user = user;
    banUnbanResetDialog.title = t('userView.banTitle');
    banUnbanResetDialog.text = t('userView.banConfirm', { username: user.username });
    banUnbanResetDialog.confirmText = t('userView.ban');
    banUnbanResetDialog.confirmColor = 'error';
    banUnbanResetDialog.action = confirmBanUser;
    banUnbanResetDialog.show = true;
};

const openUnbanDialog = (user: User) => {
    banUnbanResetDialog.type = 'unban';
    banUnbanResetDialog.user = user;
    banUnbanResetDialog.title = t('userView.unbanTitle');
    banUnbanResetDialog.text = t('userView.unbanConfirm', { username: user.username });
    banUnbanResetDialog.confirmText = t('userView.unban');
    banUnbanResetDialog.confirmColor = 'success';
    banUnbanResetDialog.action = confirmUnbanUser;
    banUnbanResetDialog.show = true;
};

const openResetPasswordDialog = (user: User) => {
    Object.assign(passwordReset, initialPasswordResetData); // Reset password fields
    banUnbanResetDialog.type = 'resetPassword';
    banUnbanResetDialog.user = user; // Store user for ID
    banUnbanResetDialog.title = t('userView.resetPasswordTitle');
    banUnbanResetDialog.text = t('userView.resetPasswordConfirm', { username: user.username });
    banUnbanResetDialog.confirmText = t('userView.setPassword');
    banUnbanResetDialog.confirmColor = 'primary';
    banUnbanResetDialog.action = confirmResetPassword;
    isResetPasswordFormValid.value = false; // Reset validation state
    banUnbanResetDialog.show = true;
    setTimeout(() => resetPasswordFormRef.value?.resetValidation(), 100);
};

const closeConfirmDialogs = () => {
    banUnbanResetDialog.show = false;
    // Reset potentially sensitive data after a short delay
    setTimeout(() => {
        banUnbanResetDialog.user = null;
        banUnbanResetDialog.type = null;
        Object.assign(passwordReset, initialPasswordResetData);
    }, 300);
};

// User Actions
const addNewUser = async () => {
    if (!isNewUserFormValid.value) return;
    savingUser.value = true;
    try {
        // Send only the necessary fields
        const payload = {
            username: newUser.username,
            email: newUser.email,
            groups: newUser.groups, // Send array of group IDs
            password: newUser.password,
        };
        const response = await apiClientAuth.post('/admin/user?action=addNewUser', payload); // Adjust path
        if (response.data.error) {
            showSnackbar(response.data.error, 'error');
        } else {
            await fetchUsers(); // Refresh list
            closeNewUserDialog();
            showSnackbar('Benutzer erfolgreich hinzugefügt.', 'success');
        }
    } catch (error: any) {
        console.error('Error adding user:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Hinzufügen des Benutzers.',
            'error'
        );
    } finally {
        savingUser.value = false;
    }
};

const updateUser = async () => {
    if (!isEditUserFormValid.value || !editedUser.id) return;
    savingUser.value = true;
    try {
        // Prepare payload, sending group IDs instead of the full group objects
        const payload = {
            ...editedUser,
            groups: editedUser.groupIds, // Send the array of IDs
            // Explicitly set linked_employee to null if empty (not undefined), so backend receives it
            linked_employee: editedUser.linked_employee !== undefined ? editedUser.linked_employee : null,
            // Remove properties not needed for update if API expects clean payload
            allGroups: undefined,
            groupIds: undefined,
        };
        await apiClientAuth.post('/admin/user?action=updateUser', payload); // Adjust path
        await fetchUsers(); // Refresh list
        closeEditUserDialog();
        showSnackbar('Benutzer erfolgreich aktualisiert.', 'success');
    } catch (error: any) {
        console.error('Error updating user:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren des Benutzers.',
            'error'
        );
    } finally {
        savingUser.value = false;
    }
};

const confirmBanUser = async () => {
    if (!banUnbanResetDialog.user) return;
    updatingUserStatus.value = banUnbanResetDialog.user.id;
    try {
        await apiClientAuth.post('/admin/user?action=bannUser', { id: banUnbanResetDialog.user.id }); // Adjust path
        await fetchUsers(); // Refresh list to show updated status
        closeConfirmDialogs();
        showSnackbar(`Benutzer "${banUnbanResetDialog.user.username}" gesperrt.`, 'success');
    } catch (error: any) {
        console.error('Error banning user:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Sperren des Benutzers.', 'error');
    } finally {
        updatingUserStatus.value = null;
    }
};

const confirmUnbanUser = async () => {
    if (!banUnbanResetDialog.user) return;
    updatingUserStatus.value = banUnbanResetDialog.user.id;
    try {
        await apiClientAuth.post('/admin/user?action=unbannUser', {
            id: banUnbanResetDialog.user.id,
        }); // Adjust path
        await fetchUsers();
        closeConfirmDialogs();
        showSnackbar(`Benutzer "${banUnbanResetDialog.user.username}" entsperrt.`, 'success');
    } catch (error: any) {
        console.error('Error unbanning user:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Entsperren des Benutzers.',
            'error'
        );
    } finally {
        updatingUserStatus.value = null;
    }
};

const confirmResetPassword = async () => {
    if (!banUnbanResetDialog.user || !isResetPasswordFormValid.value) return;
    resettingPassword.value = true;
    try {
        await apiClientAuth.post('/admin/user?action=resetPassword', {
            // Adjust path
            id: banUnbanResetDialog.user.id,
            password: passwordReset.password, // Send only the new password
        });
        closeConfirmDialogs();
        showSnackbar(
            `Passwort für "${banUnbanResetDialog.user.username}" zurückgesetzt.`,
            'success'
        );
    } catch (error: any) {
        console.error('Error resetting password:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Zurücksetzen des Passworts.',
            'error'
        );
    } finally {
        resettingPassword.value = false;
    }
};

// --- Utilities ---
const copyLinkToClipboard = async (payload: { success: boolean; text: string | null; }) => {
    const { text } = payload;
    if (!text) {
        showSnackbar('Kein Link zum Kopieren vorhanden.', 'info');
        return;
    }
    try {
        await navigator.clipboard.writeText(text);
        showSnackbar('Link erfolgreich kopiert', 'success');
    } catch (err) {
        console.error('Failed to copy link: ', err);
        showSnackbar('Link konnte nicht kopiert werden.', 'error');
    }
};

const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Ungültig';
        return date.toLocaleString('de-DE', { 
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        } as Intl.DateTimeFormatOptions);
    } catch (e) {
        return 'Fehler Datum';
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchAllInitialData();
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/UserView', () => unref(headers) as any);

/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(filteredUsers) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'benutzer' });
}

</script>

<template>
    <div class="user-management-container">
        <v-container fluid class="pa-4">
            <!-- Header with title -->
            <v-row class="mb-4 align-center">
                <v-col cols="auto">
                    <div class="d-flex align-center header-title">
                        <v-icon
                            icon="mdi-account-group-outline"
                            size="28"
                            class="mr-3 text-primary header-icon"
                        ></v-icon>
                        <h1 class="text-h5 font-weight-medium mb-0">Benutzerverwaltung</h1>
                    </div>
                </v-col>
                <v-col>
                    <v-alert
                        border="start"
                        border-color="primary"
                        elevation="2"
                        density="comfortable"
                        icon="mdi-information-outline"
                        variant="tonal"
                        class="mt-0 info-alert"
                    >
                        Hier können Sie Benutzerkonten verwalten, Gruppen zuweisen und
                        Zugriffsberechtigungen steuern.
                    </v-alert>
                </v-col>
            </v-row>

            <!-- Action Bar -->
            <v-card class="action-bar mb-5" variant="outlined">
                <v-card-text class="py-3 px-4">
                    <div class="d-flex align-center flex-wrap justify-space-between">
                        <div class="d-flex flex-grow-1">
                            <v-text-field
                                v-model="searchQuery"
                                label="Benutzer suchen"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="compact"
                                hide-details
                                clearable
                                color="primary"
                                class="search-field mr-4"
                                style="max-width: 280px"
                            ></v-text-field>

                            <v-select
                                v-model="filterStatus"
                                :items="statusOptions"
                                label="Status"
                                variant="outlined"
                                density="compact"
                                hide-details
                                color="primary"
                                class="status-filter"
                                style="max-width: 200px"
                            ></v-select>
                        </div>

                        <v-btn
                            v-if="canEdit"
                            @click="openNewUserDialog"
                            color="primary"
                            variant="elevated"
                            prepend-icon="mdi-account-plus-outline"
                            class="action-button ml-auto"
                        >
                            {{ t('userView.newUser') }}
                        </v-btn>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Users Table -->
            <v-card class="main-card elevation-4">
                <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                <KTableToolbar :columns="kCols" :shown="(filteredUsers || []).length" />
                <v-data-table
                    :headers="kCols.visible.value"
                    :items="filteredUsers"
                    item-value="id"
                    :loading="loadingUsers"
                    density="comfortable"
                    hover
                    class="user-table"
                    v-model="kSelected"
                    show-select
                >
                    <template v-slot:[`item.groups`]="{ item }">
                        <div class="group-chips">
                            <template v-if="Array.isArray(item.groups) && item.groups.length > 0">
                                <v-chip
                                    v-for="group in item.groups"
                                    :key="group.id"
                                    size="x-small"
                                    variant="flat"
                                    color="primary"
                                    class="mr-1 mb-1 group-chip"
                                >
                                    {{ group.name }}
                                </v-chip>
                            </template>
                            <span v-else class="text-caption text-grey">-</span>
                        </div>
                    </template>

                    <template v-slot:[`item.banned`]="{ item }">
                        <v-chip
                            size="small"
                            :color="item.banned == 1 ? 'error' : 'success'"
                            variant="flat"
                            class="status-chip"
                        >
                            {{ item.banned == 1 ? t('userView.banned') : t('userView.active') }}
                        </v-chip>
                    </template>

                    <template v-slot:[`item.last_login`]="{ item }">
                        {{ formatDate(item.last_login) }}
                    </template>

                    <template v-slot:[`item.mail_header`]="{ item }">
                        <ImageLinkWithTooltip
                            :url="item.mail_header"
                            label="Header"
                            @copy="copyLinkToClipboard"
                        />
                    </template>

                    <template v-slot:[`item.mail_footer`]="{ item }">
                        <ImageLinkWithTooltip
                            :url="item.mail_footer"
                            label="Footer"
                            @copy="copyLinkToClipboard"
                        />
                    </template>

                    <template v-slot:[`item.mail_header_neutral`]="{ item }">
                        <ImageLinkWithTooltip
                            :url="item.mail_header_neutral"
                            label="Header (N)"
                            @copy="copyLinkToClipboard"
                        />
                    </template>

                    <template v-slot:[`item.mail_footer_neutral`]="{ item }">
                        <ImageLinkWithTooltip
                            :url="item.mail_footer_neutral"
                            label="Footer (N)"
                            @copy="copyLinkToClipboard"
                        />
                    </template>

                    <template v-slot:[`item.signature`]="{ item }">
                        <ImageLinkWithTooltip
                            :url="item.signature"
                            label="Signatur"
                            @copy="copyLinkToClipboard"
                        />
                    </template>

                    <template v-slot:[`item.actions`]="{ item }">
                        <div class="d-flex gap-1">
                            <v-tooltip text="Bearbeiten" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openEditUserDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-pencil</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip
                                :text="item.banned == 0 ? 'Bannen' : 'Entbannen'"
                                location="top"
                            >
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="
                                            item.banned == 0
                                                ? openBanDialog(item)
                                                : openUnbanDialog(item)
                                        "
                                        v-bind="props"
                                        :loading="updatingUserStatus === item.id"
                                        class="action-icon"
                                    >
                                        <v-icon
                                            size="small"
                                            :color="item.banned == 0 ? 'error' : 'success'"
                                            >{{
                                                item.banned == 0
                                                    ? 'mdi-account-cancel-outline'
                                                    : 'mdi-lock-open-variant-outline'
                                            }}</v-icon
                                        >
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip text="Passwort zurücksetzen" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-if="canEdit"
                                        icon
                                        variant="text"
                                        size="small"
                                        @click="openResetPasswordDialog(item)"
                                        v-bind="props"
                                        class="action-icon"
                                    >
                                        <v-icon size="small">mdi-key-variant</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <LogoutUserButton
                                v-if="canEdit"
                                :user-id="item.id"
                                :username="item.username"
                                icon-only
                                @success="fetchUsers"
                            />
                        </div>
                    </template>

                    <template v-slot:no-data>
                        <div class="empty-state">
                            <v-icon size="40" color="grey-darken-1" class="mb-2"
                                >mdi-account-off-outline</v-icon
                            >
                            <span>Keine Benutzer gefunden.</span>
                        </div>
                    </template>

                    <template v-slot:loading>
                        <div class="loading-state">
                            <v-progress-circular
                                indeterminate
                                color="primary"
                                size="24"
                                class="mr-2"
                            ></v-progress-circular>
                            <span>Lade Benutzer...</span>
                        </div>
                    </template>
                </v-data-table>
                <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                <KBulkBar
                    :count="kSelected.length"
                    :shown="filteredUsers.length"
                    :total="filteredUsers.length"
                    @clear="kSelected = []"
                >
                    <template #actions>
                        <v-btn variant="outlined" size="small" @click="kExportSelection">
                            {{ t('kTable.exportSelection') }}
                        </v-btn>
                    </template>
                </KBulkBar>
            </v-card>

            <!-- New User Dialog -->
            <v-dialog v-model="newUserDialog" max-width="600px" persistent>
                <v-card class="dialog-card">
                    <v-toolbar color="primary" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon icon="mdi-account-plus" class="mr-2" size="small"></v-icon>
                            {{ t('userView.newUser') }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeNewUserDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-form ref="newUserFormRef" v-model="isNewUserFormValid">
                        <v-card-text class="pt-4">
                            <v-container>
                                <v-row>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Benutzername"
                                            v-model="newUser.username"
                                            required
                                            :rules="[requiredRule('Benutzername')]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="E-Mail"
                                            v-model="newUser.email"
                                            required
                                            :rules="[requiredRule('E-Mail')]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-select
                                            label="Gruppen"
                                            v-model="newUser.groups"
                                            :items="groups"
                                            item-title="name"
                                            item-value="id"
                                            multiple
                                            chips
                                            closable-chips
                                            required
                                            :rules="[multiSelectRequiredRule]"
                                            variant="outlined"
                                            density="comfortable"
                                            :loading="loadingGroups"
                                            color="primary"
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            label="Passwort"
                                            v-model="newUser.password"
                                            type="password"
                                            required
                                            :rules="[requiredRule('Passwort'), passwordRules.minLength]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions class="pa-4">
                            <v-spacer></v-spacer>
                            <v-btn variant="text" @click="closeNewUserDialog" class="mr-2">
                                {{ t('cancel') }}
                            </v-btn>
                            <v-btn
                                color="primary"
                                variant="elevated"
                                @click="addNewUser"
                                :disabled="!isNewUserFormValid"
                                :loading="savingUser"
                                class="action-button"
                            >
                                Hinzufügen
                            </v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-dialog>

            <!-- Edit User Dialog -->
            <v-dialog v-model="editUserDialog" max-width="700px" persistent>
                <v-card class="dialog-card">
                    <v-toolbar color="primary" class="dialog-toolbar" density="compact">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon icon="mdi-account-edit" class="mr-2" size="small"></v-icon>
                            {{ t('userView.editUser') }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeEditUserDialog" size="small"></v-btn>
                    </v-toolbar>

                    <v-form ref="editUserFormRef" v-model="isEditUserFormValid">
                        <v-card-text class="pt-4">
                            <v-container>
                                <v-row>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Benutzername"
                                            v-model="editedUser.username"
                                            required
                                            :rules="[requiredRule('Benutzername')]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="E-Mail"
                                            v-model="editedUser.email"
                                            required
                                            :rules="[requiredRule('E-Mail')]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-autocomplete
                                            label="Mitarbeiter verknüpfen (optional)"
                                            v-model="editedUser.linked_employee"
                                            :items="Employees"
                                            item-title="name"
                                            item-value="id"
                                            clearable
                                            variant="outlined"
                                            density="comfortable"
                                            :loading="loadingEmployees"
                                            color="primary"
                                        ></v-autocomplete>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-select
                                            label="Gruppen"
                                            v-model="editedUser.groupIds"
                                            :items="groups"
                                            item-title="name"
                                            item-value="id"
                                            multiple
                                            chips
                                            closable-chips
                                            required
                                            :rules="[multiSelectRequiredRule]"
                                            variant="outlined"
                                            density="comfortable"
                                            :loading="loadingGroups"
                                            color="primary"
                                        ></v-select>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-divider class="mb-2"></v-divider>
                                        <div class="text-subtitle-2 mb-2">
                                            Template Einstellungen
                                        </div>
                                    </v-col>

                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Mail Header URL"
                                            v-model="editedUser.mail_header"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Mail Footer URL"
                                            v-model="editedUser.mail_footer"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Mail Header Neutral URL"
                                            v-model="editedUser.mail_header_neutral"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            label="Mail Footer Neutral URL"
                                            v-model="editedUser.mail_footer_neutral"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-text-field
                                            label="Signatur URL"
                                            v-model="editedUser.signature"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions class="pa-4">
                            <v-spacer></v-spacer>
                            <v-btn variant="text" @click="closeEditUserDialog" class="mr-2">
                                {{ t('cancel') }}
                            </v-btn>
                            <v-btn
                                color="primary"
                                variant="elevated"
                                @click="updateUser"
                                :loading="savingUser"
                                class="action-button"
                            >
                                {{ t('save') }}
                            </v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-dialog>

            <!-- Ban/Unban/Reset Password Dialog -->
            <v-dialog v-model="banUnbanResetDialog.show" max-width="500px" persistent>
                <v-card class="dialog-card">
                    <v-toolbar
                        :color="banUnbanResetDialog.confirmColor"
                        class="dialog-toolbar"
                        density="compact"
                    >
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon
                                :icon="
                                    banUnbanResetDialog.type === 'ban'
                                        ? 'mdi-account-cancel'
                                        : banUnbanResetDialog.type === 'unban'
                                          ? 'mdi-account-check'
                                          : 'mdi-key'
                                "
                                class="mr-2"
                                size="small"
                            ></v-icon>
                            {{ banUnbanResetDialog.title }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-close" @click="closeConfirmDialogs" size="small"></v-btn>
                    </v-toolbar>

                    <v-card-text class="pt-4">
                        <span v-html="banUnbanResetDialog.text"></span>
                        <v-form
                            v-if="banUnbanResetDialog.type === 'resetPassword'"
                            ref="resetPasswordFormRef"
                            v-model="isResetPasswordFormValid"
                            class="mt-4"
                        >
                            <v-text-field
                                type="password"
                                label="Neues Passwort"
                                v-model="passwordReset.password"
                                required
                                :rules="[passwordRules.required, passwordRules.minLength]"
                                variant="outlined"
                                density="comfortable"
                                class="mb-3"
                                color="primary"
                            ></v-text-field>
                            <v-text-field
                                type="password"
                                label="Passwort wiederholen"
                                v-model="passwordReset.passwordRetry"
                                required
                                :rules="[
                                    passwordRules.required,
                                    passwordRules.matchPassword,
                                    passwordRules.minLength,
                                ]"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                            ></v-text-field>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeConfirmDialogs" class="mr-2">
                            {{ t('cancel') }}
                        </v-btn>
                        <v-btn
                            :color="banUnbanResetDialog.confirmColor"
                            variant="elevated"
                            @click="banUnbanResetDialog.action"
                            :loading="loadingAction"
                            :disabled="
                                banUnbanResetDialog.type === 'resetPassword' &&
                                !isResetPasswordFormValid
                            "
                            class="action-button"
                        >
                            {{ banUnbanResetDialog.confirmText }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.user-management-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 10% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 90% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Header Styles */
.header-title {
    animation: fadeIn 0.5s ease-out;
}

.header-icon {
    filter: drop-shadow(0 2px 6px rgba(59, 130, 246, 0.4));
}

.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Action Bar */
.action-bar {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.5s ease-out;
    animation-delay: 0.1s;
}

.search-field,
.status-filter {
    transition: all 0.3s ease;
}

.search-field:focus-within,
.status-filter:focus-within {
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.3);
}

.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Main Card & Table Styles */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    animation: fadeIn 0.5s ease-out;
    animation-delay: 0.2s;
}

:deep(.v-table .v-table__wrapper > table > thead > tr > th) {
    font-weight: 600;
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    padding: 12px 16px;
}

:deep(.v-table .v-table__wrapper > table > thead > tr > th:not(:last-child)) {
    border-right: thin solid rgba(255, 255, 255, 0.1);
}

:deep(.v-table .v-table__wrapper > table > tbody > tr > td) {
    padding: 12px 16px;
    border-bottom: thin solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
    transition: background 0.2s ease;
}

:deep(.v-table .v-table__wrapper > table > tbody > tr > td:not(:last-child)) {
    border-right: thin solid rgba(255, 255, 255, 0.05);
}

:deep(.v-table .v-table__wrapper > table > tbody > tr:hover > td) {
    background: rgba(30, 41, 59, 0.4) !important;
}

/* Group chips */
.group-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.group-chip {
    transition: all 0.2s ease;
}

.group-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.status-chip {
    min-width: 80px;
    text-align: center;
}

/* Table Action Icons */
.action-icon {
    opacity: 0.7;
    transition: all 0.2s;
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty & Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    color: var(--v-theme-text-secondary);
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styles */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-toolbar {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover)) !important;
}

/* Image tooltips */
:deep(.v-tooltip > .v-overlay__content img) {
    max-width: 200px;
    max-height: 100px;
    display: block;
    margin: 5px 0;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Adjustments */
@media (max-width: 960px) {
    .search-field,
    .status-filter {
        max-width: 100%;
        margin-bottom: 12px;
    }
}
</style>
