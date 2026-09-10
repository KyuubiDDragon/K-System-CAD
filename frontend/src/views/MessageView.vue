<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, reactive, unref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import { useModulePermission } from '@/composables/useModulePermission'; // Permission checking
import type { User, Group, Message, Folder } from '@/types/Message'; // Adjust path if needed
import TiptapEditor from '@/components/TiptapEditor.vue';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { exportRowsAsCsv } from '@/utils/tableExport';
import { useTableFilters } from '@/composables/useTableFilters';
// Define local interfaces
interface SelectOption {
  id: string;
  label: string;
  type?: string;
}

// Define the NewMessage interface locally since it's not exported from @/types/Message
interface NewMessage {
    title: string;
    body: string;
    sender_id: string | null; // e.g., user_1 or group_5
    recipient_ids: string[]; // Array of user_x or group_y
    sender_folder_id: number | null;
    recipient_folder_id: number | null;
}

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Router
const route = useRoute();

// --- Components & Stores ---
const authStore = useAuthStore();
const { t } = useI18n();
const { hasModulePermission, hasAllPermissions } = useModulePermission();

// --- Permission Checks (CRITICAL SECURITY FIX) ---
const canEdit = computed(() =>
  props.allPermissions ||
  props.canEdit ||
  !!route.meta?.canEdit ||
  hasModulePermission('messaging', 'write') ||
  hasAllPermissions.value
);

const canDelete = computed(() =>
  props.allPermissions ||
  props.canDelete ||
  !!route.meta?.canDelete ||
  hasModulePermission('messaging', 'delete') ||
  hasAllPermissions.value
);

const canCreate = computed(() =>
  props.allPermissions ||
  props.canCreate ||
  !!route.meta?.canCreate ||
  hasModulePermission('messaging', 'write') || // CREATE messages uses WRITE permission
  hasAllPermissions.value
);

// --- Refs & State ---
const view = ref<'inbox' | 'sent' | 'folders' | 'deleted'>('inbox');
const messages = ref<Message[]>([]);
const users = ref<User[]>([]);
const groups = ref<Group[]>([]);
const folders = ref<Folder[]>([]);
const search = ref('');
const filterFolderSearch = ref<number | null>(null);
const selectedGroup = ref<number | null>(null); // null for personal, group.id for group
const currentUserId = ref<number | null>(null); // Will be fetched
const loadingMessages = ref(false);
const loadingFolders = ref(false);
const deleting = ref(false);
const savingFolder = ref(false);

// --- Dialog States ---
const newMessageDialog = ref(false);
const deleteConfirmationDialog = ref(false);
const folderDialog = ref(false);

// --- Form Refs ---
const newMessageFormRef = ref<any>(null); // Type depends on Vuetify's VForm type
const folderFormRef = ref<any>(null);
const isNewMessageFormValid = ref(false);
const isFolderFormValid = ref(false);

// --- Data Models ---
const initialNewMessageState: NewMessage = {
    title: '',
    body: '',
    sender_id: null, // e.g., user_1 or group_5
    recipient_ids: [], // Array of user_x or group_y
    sender_folder_id: null,
    recipient_folder_id: null,
};
const newMessage = reactive<NewMessage>({ ...initialNewMessageState });

const initialReadMessageState: Message = {
    id: 0,
    title: '',
    body: '',
    sender_id: 0,
    sender_type: 'user',
    sender_name: '',
    recipient_id: 0,
    recipient_type: 'user',
    recipient_name: '' as any, // Allow array of strings for multiple recipients
    sender_folder_id: null,
    recipient_folder_id: null,
    group_id: null,
    is_anonymous: false,
    is_read: false,
    read_at: null,
    sender_note: '',
    recipient_note: '',
    created_at: null,
    deleted_by_sender: false,
    deleted_by_recipient: false,
    pinned: false,
    recipient_folder_name: '',
    sender_folder_name: '',
};
const readMessage = reactive<Message>({ ...initialReadMessageState });

const initialFolderFormData = {
    id: null as number | null,
    name: '',
    ownerId: null as string | null,
};
const folderFormData = reactive({ ...initialFolderFormData });
const isEditingFolder = computed(() => !!folderFormData.id);

// --- Snackbar ---
const snackbar = reactive({
    visible: false,
    message: '',
    color: 'info', // 'success', 'error', 'info', 'warning'
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.visible = true;
}

// --- Delete Confirmation ---
const itemToDelete = ref<Message | Folder | null>(null);
const deleteType = ref<'message' | 'message_permanent' | 'folder' | null>(null);

const deleteConfirmationTitle = computed(() => {
    switch (deleteType.value) {
        case 'message':
            return t('messageView.deleteMessageTitle');
        case 'message_permanent':
            return t('messageView.permanentlyDelete');
        case 'folder':
            return t('messageView.deleteFolderTitle');
        default:
            return t('confirm');
    }
});
const deleteConfirmationText = computed(() => {
    if (!itemToDelete.value) return '';
    switch (deleteType.value) {
        case 'message':
            return t('messageView.confirmDeleteMessage', { title: (itemToDelete.value as Message).title });
        case 'message_permanent':
            return t('messageView.confirmDeleteMessagePermanent', { title: (itemToDelete.value as Message).title });
        case 'folder':
            return t('messageView.confirmDeleteFolder', { name: (itemToDelete.value as Folder).name });
        default:
            return t('messageView.deleteConfirm');
    }
});
const deleteConfirmationActionText = computed(() => {
    switch (deleteType.value) {
        case 'message':
            return t('delete');
        case 'message_permanent':
            return t('messageView.permanentlyDelete');
        case 'folder':
            return t('delete');
        default:
            return t('confirm');
    }
});
const deleteConfirmationColor = computed(() =>
    deleteType.value === 'message_permanent' ? 'error' : 'primary'
);

// --- Table Headers ---
const messageHeaders = computed(() => [
    { title: '', align: 'center', sortable: false, key: 'status', width: '60px' },
    { title: 'Betreff', align: 'start', key: 'title' },
    // { title: "", key: "body", width: '1px' }, // Don't display body column
    { title: 'Absender', key: 'sender_name' },
    { title: 'Empfänger', key: 'recipient_name', optional: true },
    { title: 'Ordner', key: 'folder_name' },
    { title: 'Datum/Uhrzeit', key: 'created_at', width: '160px', align: 'end' },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end', width: '150px' },
]);
const folderHeaders = ref([
    { title: 'Name', align: 'start', key: 'name' },
    { title: 'Aktionen', key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const);

// --- Data Fetching ---
const fetchMessages = async (isBackgroundRefresh = false) => {
    if (!isBackgroundRefresh) loadingMessages.value = true;
    try {
        const response = await apiClientAuth.get<Message[]>(
            '/message/?action=getMessages'
        );
        messages.value = response.data || []; // Ensure it's an array
    } catch (error: any) {
        console.error('Error fetching messages:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messagesLoadError'),
            'error'
        );
    } finally {
        if (!isBackgroundRefresh) loadingMessages.value = false;
    }
};

const fetchUsers = async () => {
    try {
        // This might not be needed if user/group info comes with messages or from authStore
        const response = await apiClientAuth.get<User[]>('/message/?action=getUsers');
        users.value = response.data || [];
        // Prefer getting ID from authStore if available and reliable
        currentUserId.value = authStore.user?.id ?? (users.value.find(u => u.is_current_user)?.id || null);
    } catch (error: any) {
        console.error('Error fetching users:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.usersLoadError'),
            'error'
        );
    }
};

const fetchGroups = async () => {
    try {
        const response = await apiClientAuth.get< Group[]>('/message/?action=getGroups');
        groups.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching groups:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.groupsLoadError'),
            'error'
        );
    }
};

const fetchFolders = async (isBackgroundRefresh = false) => {
    if (!isBackgroundRefresh) loadingFolders.value = true;
    try {
        const response = await apiClientAuth.get<Folder[]>('/message/?action=getFolders');
        folders.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching folders:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.foldersLoadError'),
            'error'
        );
    } finally {
        if (!isBackgroundRefresh) loadingFolders.value = false;
    }
};

const fetchDataForCurrentView = (isBackgroundRefresh = false) => {
    if (view.value === 'folders') {
        fetchFolders(isBackgroundRefresh);
    } else {
        fetchMessages(isBackgroundRefresh);
    }
};

// --- Computed Properties ---
const currentViewTitle = computed(() => {
    switch (view.value) {
        case 'inbox':
            return 'Posteingang';
        case 'sent':
            return 'Gesendet';
        case 'deleted':
            return 'Papierkorb';
        case 'folders':
            return 'Ordnerverwaltung';
        default:
            return 'Nachrichten';
    }
});

const filteredMessages = computed(() => {
    const filterByFolder = (message: Message): boolean => {
        if (!filterFolderSearch.value) return true;
        const folderId =
            view.value === 'inbox' || view.value === 'deleted'
                ? message.recipient_folder_id
                : message.sender_folder_id;
        return folderId === filterFolderSearch.value;
    };

    const pinSort = (a: Message, b: Message): number => {
        if (a.pinned && !b.pinned) return -1;
        if (!a.pinned && b.pinned) return 1;
        // Secondary sort: newest first
        const dateA = a.created_at ? new Date(a.created_at).getTime() : 0;
        const dateB = b.created_at ? new Date(b.created_at).getTime() : 0;
        return dateB - dateA;
    };

    let baseMessages: Message[];

    if (view.value === 'deleted') {
        // Show messages deleted by the current user/group perspective
        baseMessages = messages.value.filter(
            m =>
                (selectedGroup.value === null &&
                    m.recipient_type === 'user' &&
                    m.recipient_id === currentUserId.value &&
                    m.deleted_by_recipient) ||
                (selectedGroup.value !== null &&
                    m.recipient_type === 'group' &&
                    m.recipient_id === selectedGroup.value &&
                    m.deleted_by_recipient) ||
                (selectedGroup.value === null &&
                    m.sender_type === 'user' &&
                    m.sender_id === currentUserId.value &&
                    m.deleted_by_sender) || // Also show own deleted sent items
                (selectedGroup.value !== null &&
                    m.sender_type === 'group' &&
                    m.sender_id === selectedGroup.value &&
                    m.deleted_by_sender) // Also show group deleted sent items
        );
    } else if (view.value === 'sent') {
        baseMessages = messages.value.filter(
            m =>
                !m.deleted_by_sender &&
                ((selectedGroup.value === null &&
                    m.sender_type === 'user' &&
                    m.sender_id === currentUserId.value) ||
                    (selectedGroup.value !== null &&
                        m.sender_type === 'group' &&
                        m.sender_id === selectedGroup.value))
        );
    } else {
        // Inbox
        baseMessages = messages.value.filter(
            m =>
                !m.deleted_by_recipient &&
                ((selectedGroup.value === null &&
                    m.recipient_type === 'user' &&
                    m.recipient_id === currentUserId.value) ||
                    (selectedGroup.value !== null &&
                        m.recipient_type === 'group' &&
                        m.recipient_id === selectedGroup.value))
        );
    }

    return baseMessages.filter(filterByFolder).sort(view.value === 'inbox' ? pinSort : undefined);
});

const filteredGroups = computed(() => groups.value.filter(group => group.is_member));

const filteredFolders = computed(() => {
    return folders.value.filter(folder =>
        selectedGroup.value === null
            ? folder.user_id === currentUserId.value
            : folder.group_id === selectedGroup.value
    );
});

// Folders available for the current view (for filtering dropdown)
const currentViewFolders = computed(() => filteredFolders.value);

const getUnreadCount = (groupId: number | null): number => {
    return messages.value.filter(
        m =>
            !m.is_read &&
            !m.deleted_by_recipient &&
            ((groupId === null &&
                m.recipient_type === 'user' &&
                m.recipient_id === currentUserId.value) ||
                (groupId !== null && m.recipient_type === 'group' && m.recipient_id === groupId))
    ).length;
};

const unreadInboxCount = computed(() => getUnreadCount(selectedGroup.value));

const recipientOptions = computed<SelectOption[]>(() => {
    const userOptions = users.value
        // .filter(user => user.id !== currentUserId.value) // Exclude self? Decide based on requirements
        .map(user => ({
            id: `user_${user.id}`,
            label: user.employee || user.username || `User ${user.id}`,
            type: 'Benutzer',
        }));
    const groupOptions = groups.value
        // .filter(group => group.can_message) // Filter groups that can receive messages?
        .map(group => ({ id: `group_${group.id}`, label: group.name, type: 'Gruppe' }));
    return [...userOptions, ...groupOptions];
});

const senderOptions = computed<SelectOption[]>(() => {
    const currentUser = users.value.find(user => user.id === currentUserId.value);
    const userOption = currentUser
        ? [{ id: `user_${currentUser.id}`, label: `${currentUser.username} (Ich)` }]
        : [];
    const groupOptions = groups.value
        .filter(group => group.is_member) // Only groups the user is a member of
        .map(group => ({ id: `group_${group.id}`, label: `${group.name} (Gruppe)` }));
    return [...userOption, ...groupOptions];
});

// Options for the owner select in the folder dialog
const folderOwnerOptions = computed<SelectOption[]>(() => {
    const currentUser = users.value.find(user => user.id === currentUserId.value);
    const userOption = currentUser
        ? [{ id: `user_${currentUser.id}`, label: `${currentUser.username} (Ich)` }]
        : [];
    const groupOptions = groups.value
        .filter(group => group.can_manage_folders) // Only groups where user can manage folders
        .map(group => ({ id: `group_${group.id}`, label: `${group.name} (Gruppe)` }));
    return [...userOption, ...groupOptions];
});

// --- Methods ---

// View Switching
const changeView = async (newView: 'inbox' | 'sent' | 'folders' | 'deleted') => {
    view.value = newView;
    selectedGroup.value = null; // Reset group filter on view change
    filterFolderSearch.value = null; // Reset folder filter
    search.value = ''; // Reset search
    await fetchDataForCurrentView();
};
const viewInbox = () => changeView('inbox');
const viewSent = () => changeView('sent');
const viewFolders = () => changeView('folders');
const viewDeleted = () => changeView('deleted');

// Filtering
const filterContentByGroup = (groupId: number | null) => {
    selectedGroup.value = groupId;
    filterFolderSearch.value = null; // Reset folder filter when group changes
    // Data is already filtered by computed properties based on selectedGroup
};

// Message Actions
const openNewMessageDialog = () => {
    Object.assign(newMessage, initialNewMessageState); // Reset form
    // Pre-select sender based on current view context
    newMessage.sender_id =
        selectedGroup.value === null
            ? currentUserId.value
                ? `user_${currentUserId.value}`
                : null
            : `group_${selectedGroup.value}`;
    newMessageDialog.value = true;
};

const addNewMessage = async () => {
    // Optional: Validate with formRef
    // const { valid } = await newMessageFormRef.value?.validate();
    // if (!valid || !newMessage.body) return;

    if (
        !isNewMessageFormValid.value ||
        !newMessage.body ||
        !newMessage.sender_id ||
        newMessage.recipient_ids.length === 0
    ) {
        showSnackbar('Bitte füllen Sie alle erforderlichen Felder aus.', 'warning');
        return;
    }

    // Determine sender type from sender_id prefix
    const senderType = newMessage.sender_id?.startsWith('user_') ? 'user' : 'group';
    const senderIdNum = parseInt(newMessage.sender_id?.split('_')[1] ?? '0');

    // Prepare payload for multiple recipients
    const payload = newMessage.recipient_ids.map(recipient => {
        const recipientType = recipient.startsWith('user_') ? 'user' : 'group';
        const recipientIdNum = parseInt(recipient.split('_')[1]);
        return {
            title: newMessage.title,
            body: newMessage.body,
            sender_id: senderIdNum,
            sender_type: senderType,
            recipient_id: recipientIdNum,
            recipient_type: recipientType,
            sender_folder_id: newMessage.sender_folder_id, // Optional
            recipient_folder_id: newMessage.recipient_folder_id, // Optional
            // group_id might be needed depending on backend logic? Usually derived from sender/recipient
        };
    });

    try {
        // Assuming the backend handles an array of messages or iterates
        // If backend expects one message at a time, loop here.
        // Using a bulk endpoint if available is better.
        const response = await apiClientAuth.post('/message/?action=addMessage', {
            messages: payload,
        }); // Adjust endpoint/payload structure as needed
        showSnackbar(t('messageView.messageSentSuccess'), 'success');
        await fetchMessages(); // Refresh relevant list
        newMessageDialog.value = false;
        // No need to reset newMessage here, openNewMessageDialog does it
    } catch (error: any) {
        console.error('Error sending message:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messageSendError'),
            'error'
        );
    }
};

const isReadingMessage = ref(false);

const openReadMessageDialog = async (message: Message) => {
    Object.assign(readMessage, message); // Copy message data
    // Convert single recipient_id/type/name to array if backend returns single value for 'sent' view
    if (view.value === 'sent' && typeof readMessage.recipient_name === 'string') {
        (readMessage.recipient_name as any) = [readMessage.recipient_name];
    }
    
    isReadingMessage.value = true; // Toggle to reading view
    
    // Mark as read only if in inbox and not already read
    if (view.value === 'inbox' && !message.is_read) {
        await markMessageReadStatus(message, true); // Mark as read
    }
};

const closeReadMessageDialog = () => {
    isReadingMessage.value = false;
};

const updateMessage = async () => {
    // Updates folder or note
    const payload = {
        id: readMessage.id,
        recipient_folder_id: readMessage.recipient_folder_id,
        sender_folder_id: readMessage.sender_folder_id,
        recipient_note: readMessage.recipient_note,
        sender_note: readMessage.sender_note,
    };
    try {
        await apiClientAuth.post('/message/?action=updateMessage', payload);
        showSnackbar(t('messageView.messageUpdated'), 'success');
        await fetchMessages(); // Refresh list
        // Keep dialog open or close? Closing for now.
        // readMessageDialog.value = false;
    } catch (error: any) {
        console.error('Error updating message:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messageUpdateError'),
            'error'
        );
    }
};

const replyMessage = (message: Message) => {
    Object.assign(newMessage, initialNewMessageState); // Reset
    newMessage.title = `Re: ${message.title}`;
    newMessage.body = `<br><br><p>--- Ursprüngliche Nachricht am ${formatDate(message.created_at)} von ${message.sender_name} ---</p><blockquote>${message.body}</blockquote>`;
    newMessage.sender_id =
        selectedGroup.value === null
            ? currentUserId.value
                ? `user_${currentUserId.value}`
                : null
            : `group_${selectedGroup.value}`;
    // Reply to original sender (which could be user or group)
    newMessage.recipient_ids = [`${message.sender_type}_${message.sender_id}`];
    closeReadMessageDialog(); // Close read dialog
    newMessageDialog.value = true; // Open compose dialog
};

const forwardMessage = (message: Message) => {
    Object.assign(newMessage, initialNewMessageState); // Reset
    newMessage.title = `Fwd: ${message.title}`;
    newMessage.body = `<br><br><p>--- Weitergeleitete Nachricht von ${message.sender_name} (${formatDate(message.created_at)}) ---</p><blockquote>${message.body}</blockquote>`;
    newMessage.sender_id =
        selectedGroup.value === null
            ? currentUserId.value
                ? `user_${currentUserId.value}`
                : null
            : `group_${selectedGroup.value}`;
    newMessage.recipient_ids = []; // Clear recipients, user needs to select new ones
    closeReadMessageDialog(); // Close read dialog
    newMessageDialog.value = true; // Open compose dialog
};

const openDeleteMessageDialog = (message: Message) => {
    itemToDelete.value = message;
    deleteType.value = 'message';
    deleteConfirmationDialog.value = true;
};

const deleteMessage = async (message: Message) => {
    // Moves to trash (sets deleted_by_sender or deleted_by_recipient flag)
    const payload = {
        id: message.id,
        // Determine based on current view perspective
        deleted_by_sender: view.value === 'sent',
        deleted_by_recipient: view.value === 'inbox' || view.value === 'deleted', // Allow delete from deleted view? Maybe permanent delete instead.
    };
    deleting.value = true;
    try {
        await apiClientAuth.post('/message/?action=deleteMessage', payload);
        showSnackbar(t('messageView.messageMovedToTrash'), 'success');
        await fetchMessages(); // Refresh list
        deleteConfirmationDialog.value = false;
    } catch (error: any) {
        console.error('Error deleting message:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messageDeleteError'),
            'error'
        );
    } finally {
        deleting.value = false;
    }
};

const restoreMessage = async (message: Message) => {
    // Unsets deleted flags
    const payload = {
        id: message.id,
        deleted_by_sender: false,
        deleted_by_recipient: false,
    };
    deleting.value = true; // Use same loading indicator
    try {
        await apiClientAuth.post('/message/?action=restoreMessage', payload); // Assuming dedicated endpoint
        showSnackbar(t('messageView.messageRestored'), 'success');
        await fetchMessages(); // Refresh list
    } catch (error: any) {
        console.error('Error restoring message:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messageRestoreError'),
            'error'
        );
    } finally {
        deleting.value = false;
    }
};

const openDeleteMessagePermanentlyDialog = (message: Message) => {
    itemToDelete.value = message;
    deleteType.value = 'message_permanent';
    deleteConfirmationDialog.value = true;
};

const deleteMessagePermanently = async (message: Message) => {
    // Physical deletion from DB
    deleting.value = true;
    try {
        await apiClientAuth.post('/message/?action=deleteMessagePermanent', { id: message.id }); // Assuming dedicated endpoint
        showSnackbar(t('messageView.messagePermanentlyDeleted'), 'success');
        await fetchMessages(); // Refresh list
        deleteConfirmationDialog.value = false;
    } catch (error: any) {
        console.error('Error permanently deleting message:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.messagePermanentDeleteError'),
            'error'
        );
    } finally {
        deleting.value = false;
    }
};

const markMessageReadStatus = async (message: Message, isRead: boolean) => {
    // Optimistic UI update
    const originalStatus = message.is_read;
    message.is_read = isRead;
    try {
        await apiClientAuth.post('/message/?action=markAsRead', { id: message.id, is_read: isRead });
        // No explicit fetch needed if optimistic update is sufficient, or rely on WebSocket update
        // await fetchMessages();
    } catch (error: any) {
        // Revert optimistic update on error
        message.is_read = originalStatus;
        console.error('Error marking message read status:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.readStatusChangeError'),
            'error'
        );
    }
};

const toggleMessageReadStatus = (message: Message) => {
    markMessageReadStatus(message, !message.is_read);
};

const markMessagePinnedStatus = async (message: Message, pinned: boolean) => {
    // Optimistic UI update
    const originalStatus = message.pinned;
    message.pinned = pinned;
    try {
        await apiClientAuth.post('/message/?action=markAsPinned', {
            id: message.id,
            pinned: pinned,
        });
        // Re-sort or re-fetch if pinning affects order significantly and WS doesn't handle it
        await fetchMessages(); // Fetch to ensure correct order after pinning change
    } catch (error: any) {
        // Revert optimistic update
        message.pinned = originalStatus;
        console.error('Error marking message pinned status:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.pinStatusChangeError'),
            'error'
        );
    }
};

const toggleMessagePinnedStatus = (message: Message) => {
    markMessagePinnedStatus(message, !message.pinned);
};

// Folder Actions
const openNewFolderDialog = () => {
    Object.assign(folderFormData, initialFolderFormData); // Reset
    // Pre-select owner based on current view context
    folderFormData.ownerId =
        selectedGroup.value === null
            ? currentUserId.value
                ? `user_${currentUserId.value}`
                : null
            : `group_${selectedGroup.value}`;
    folderDialog.value = true;
};

const openEditFolderDialog = (folder: Folder) => {
    folderFormData.id = folder.id;
    folderFormData.name = folder.name;
    folderFormData.ownerId = folder.user_id
        ? `user_${folder.user_id}`
        : folder.group_id
          ? `group_${folder.group_id}`
          : null;
    folderDialog.value = true;
};

const saveFolder = async () => {
    // const { valid } = await folderFormRef.value?.validate();
    // if (!valid) return;
    if (!isFolderFormValid.value) {
        showSnackbar('Bitte füllen Sie alle erforderlichen Felder aus.', 'warning');
        return;
    }

    savingFolder.value = true;
    const isUserOwner = folderFormData.ownerId?.startsWith('user_');
    const ownerIdNum = parseInt(folderFormData.ownerId?.split('_')[1] ?? '0');

    const payload = {
        id: folderFormData.id, // Will be null for new folders
        name: folderFormData.name,
        user_id: isUserOwner ? ownerIdNum : null,
        group_id: !isUserOwner ? ownerIdNum : null,
    };

    try {
        const action = isEditingFolder.value ? 'updateFolder' : 'addFolder';
        await apiClientAuth.post(`/message/?action=${action}`, payload);
        showSnackbar(
            isEditingFolder.value ? t('messageView.folderUpdated') : t('messageView.folderCreated'),
            'success'
        );
        await fetchFolders(); // Refresh folder list
        folderDialog.value = false;
    } catch (error: any) {
        console.error('Error saving folder:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.folderSaveError'),
            'error'
        );
    } finally {
        savingFolder.value = false;
    }
};

const openDeleteFolderDialog = (folder: Folder) => {
    itemToDelete.value = folder;
    deleteType.value = 'folder';
    deleteConfirmationDialog.value = true;
};

const deleteFolder = async (folder: Folder) => {
    deleting.value = true;
    try {
        await apiClientAuth.post('/message/?action=deleteFolder', { id: folder.id });
        showSnackbar(t('messageView.folderDeleted'), 'success');
        await fetchFolders(); // Refresh list
        deleteConfirmationDialog.value = false;
    } catch (error: any) {
        console.error('Error deleting folder:', error);
        showSnackbar(
            error.response?.data?.error || t('messageView.folderDeleteError'),
            'error'
        );
    } finally {
        deleting.value = false;
    }
};

// Delete Confirmation Handlers
const confirmDelete = () => {
    if (!itemToDelete.value || !deleteType.value) return;

    switch (deleteType.value) {
        case 'message':
            deleteMessage(itemToDelete.value as Message);
            break;
        case 'message_permanent':
            deleteMessagePermanently(itemToDelete.value as Message);
            break;
        case 'folder':
            deleteFolder(itemToDelete.value as Folder);
            break;
    }
    // Dialog is closed within the specific delete functions on success
};

const cancelDelete = () => {
    deleteConfirmationDialog.value = false;
    itemToDelete.value = null;
    deleteType.value = null;
};

// --- Utility Functions ---
const formatDate = (date?: Date | string | null): string => {
    if (!date) return '-';
    try {
        let dateObj: Date;
        if (date instanceof Date) {
            dateObj = date;
        } else {
            dateObj = new Date(date);
        }
        if (isNaN(dateObj.getTime())) return 'Ungültiges Datum';
        return dateObj.toLocaleString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch (e) {
        return 'Fehler Datum';
    }
};

// Validation Rules
const requiredRule = (value: any) => !!value || t('requiredField');
const requiredRuleMultiple = (value: any[]) =>
    (value && value.length > 0) || t('messageView.selectAtLeastOne');

// --- Lifecycle Hooks ---
onMounted(async () => {
    // Fetch initial necessary data
    await fetchUsers(); // Needed for currentUserId and options
    await fetchGroups(); // Needed for options and filtering
    await fetchDataForCurrentView(); // Fetch messages or folders based on default view
    
    // Check for ID from route query or props
    const messageId = route.query.id || props.id || props.meta?.id;
    
    if (messageId) {
        // Find the message with the specified ID
        const message = messages.value.find(msg => msg.id === Number(messageId));
        if (message) {
            // Open the read message dialog for this message
            openReadMessageDialog(message);
        }
    }
});

// Watch for view changes to potentially trigger fetches (though covered by changeView)
// watch(view, () => fetchDataForCurrentView());

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('MessageView', () => unref(messageHeaders) as any);


/**
 * Auswahl fuer die Massenaktionen. Ausgegeben wird die Auswahl - oder,
 * wenn nichts ausgewaehlt ist, die ganze sichtbare Liste. Und zwar mit
 * genau den Spalten, die gerade sichtbar sind.
 */
const kSelected = ref<any[]>([]);

function kExportSelection() {
    const rows = (unref(kFilters.filtered.value) as any[]) ?? [];
    const chosen = kSelected.value.length
        ? rows.filter((r: any) => kSelected.value.includes(r.id))
        : rows;
    exportRowsAsCsv(kCols.visible.value, chosen, { name: 'nachrichten' });
}

/**
 * Filter der Leiste. Schalter tragen eine feste Bedingung,
 * Facetten holen ihre Werte aus dem Bestand - nicht aus einer
 * gepflegten Liste, die am Tag ihrer Einfuehrung veraltet waere.
 */
const kFilters = useTableFilters(
    () => (unref(filteredMessages) as any[]) ?? [],
    [],
    [
        { field: 'folder_name', label: t('messageView.folder'), emptyLabel: t('messageView.withoutFolder') },
        { field: 'sender_name', label: t('messageView.sender'), emptyLabel: t('messageView.withoutSender') },
    ],
);

</script>

<template>
    <v-container fluid class="mail-system-container pa-0">
        <!-- Seitenleiste -->
        <v-navigation-drawer app rail permanent class="sidebar-drawer">
            <v-list density="compact" nav class="sidebar-list">
                <v-tooltip :text="$t('messageView.inbox')" location="end">
                    <template v-slot:activator="{ props }">
                        <v-list-item
                            v-bind="props"
                            :prepend-icon="view === 'inbox' ? 'mdi-inbox-full' : 'mdi-inbox'"
                            link
                            @click="viewInbox"
                            :active="view === 'inbox'"
                            value="inbox"
                            color="primary"
                            class="sidebar-item"
                        >
                            <template v-slot:append>
                                <v-badge
                                    v-if="unreadInboxCount > 0"
                                    color="error"
                                    :content="unreadInboxCount"
                                    inline
                                    floating
                                    class="nav-badge"
                                ></v-badge>
                            </template>
                        </v-list-item>
                    </template>
                </v-tooltip>
                <v-tooltip :text="$t('messageView.sent')" location="end">
                    <template v-slot:activator="{ props }">
                        <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-send"
                            link
                            @click="viewSent"
                            :active="view === 'sent'"
                            value="sent"
                            color="primary"
                            class="sidebar-item"
                        ></v-list-item>
                    </template>
                </v-tooltip>
                <v-tooltip :text="$t('messageView.folders')" location="end">
                    <template v-slot:activator="{ props }">
                        <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-folder-multiple-outline"
                            link
                            @click="viewFolders"
                            :active="view === 'folders'"
                            value="folders"
                            color="primary"
                            class="sidebar-item"
                        ></v-list-item>
                    </template>
                </v-tooltip>
                <v-tooltip :text="$t('messageView.trash')" location="end">
                    <template v-slot:activator="{ props }">
                        <v-list-item
                            v-bind="props"
                            prepend-icon="mdi-delete"
                            link
                            @click="viewDeleted"
                            :active="view === 'deleted'"
                            value="deleted"
                            color="primary"
                            class="sidebar-item"
                        ></v-list-item>
                    </template>
                </v-tooltip>
            </v-list>
        </v-navigation-drawer>

        <!-- Hauptinhalt -->
        <v-main style="padding-left: 56px">
            <div class="main-content">
                <v-container fluid class="pa-4">
                    <!-- Filter und Action Buttons -->
                    <v-row class="mb-4 align-center">
                        <v-col>
                            <v-toolbar
                                v-if="view !== 'folders'"
                                density="compact"
                                flat
                                color="transparent"
                                class="filter-toolbar px-0"
                            >
                                <v-btn
                                    @click="filterContentByGroup(null)"
                                    :variant="selectedGroup === null ? 'tonal' : 'text'"
                                    class="mr-2 filter-btn"
                                    color="primary"
                                >
                                    {{ $t('messageView.personal') }}
                                    <v-badge
                                        v-if="view === 'inbox' && getUnreadCount(null) > 0"
                                        color="error"
                                        :content="getUnreadCount(null)"
                                        inline
                                        class="ml-1"
                                    ></v-badge>
                                </v-btn>
                                <v-btn
                                    v-for="group in filteredGroups"
                                    :key="group.id"
                                    @click="filterContentByGroup(group.id)"
                                    :variant="selectedGroup === group.id ? 'tonal' : 'text'"
                                    class="mr-2 filter-btn"
                                    color="primary"
                                >
                                    {{ group.name }}
                                    <v-badge
                                        v-if="view === 'inbox' && getUnreadCount(group.id) > 0"
                                        color="error"
                                        :content="getUnreadCount(group.id)"
                                        inline
                                        class="ml-1"
                                    ></v-badge>
                                </v-btn>
                            </v-toolbar>
                        </v-col>
                        <v-col class="text-right" cols="auto">
                            <v-btn
                                v-if="view !== 'folders' && canCreate"
                                @click="openNewMessageDialog"
                                color="primary"
                                prepend-icon="mdi-plus"
                                class="action-button elevation-2"
                                >{{ t('messageView.newMessage') }}</v-btn
                            >
                            <v-btn
                                v-if="view === 'folders' && canEdit"
                                @click="openNewFolderDialog"
                                color="primary"
                                prepend-icon="mdi-folder-plus-outline"
                                class="action-button elevation-2"
                                >{{ t('messageView.newFolder') }}</v-btn
                            >
                        </v-col>
                    </v-row>

                    <!-- Nachrichten oder Ordner Tabelle -->
                    <v-card v-if="view !== 'folders' && !isReadingMessage" class="main-card elevation-4">
                        <v-toolbar flat density="compact" class="card-toolbar">
                            <v-toolbar-title class="text-h6">{{
                                currentViewTitle
                            }}</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-select
                                v-model="filterFolderSearch"
                                :items="currentViewFolders"
                                item-title="name"
                                item-value="id"
                                label="Filter nach Ordner"
                                clearable
                                hide-details
                                density="compact"
                                variant="outlined"
                                bg-color="grey-darken-3"
                                class="mx-4 folder-select"
                            />
                            <v-text-field
                                v-model="search"
                                label="Suche"
                                prepend-inner-icon="mdi-magnify"
                                hide-details
                                density="compact"
                                variant="outlined"
                                bg-color="grey-darken-3"
                                class="search-field"
                            />
                        </v-toolbar>

                        <v-divider></v-divider>

                        <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
                        <KTableToolbar
                :filters="kFilters"
                :columns="kCols"
                :shown="kFilters.filtered.value.length"
                :total="(filteredMessages || []).length"
                :noun="t('messageView.noun')"
            />
                        <v-data-table
                            :headers="kCols.visible.value"
                            :items="kFilters.filtered.value"
                            :search="search"
                            :items-per-page="25"
                            item-value="id"
                            :loading="loadingMessages"
                            hover
                            class="message-table"
                         density="compact"
                            v-model="kSelected"
                            show-select
                        >
                            <template v-slot:[`item.status`]="{ item }">
                                <div class="d-flex align-center">
                                    <v-icon
                                        v-if="item.pinned && view === 'inbox'"
                                        color="warning"
                                        size="small"
                                        class="mr-1"
                                        >mdi-pin</v-icon
                                    >
                                    <v-icon
                                        v-if="view === 'inbox'"
                                        :color="item.is_read ? 'grey-lighten-1' : 'primary'"
                                        size="small"
                                        >{{
                                            item.is_read
                                                ? 'mdi-email-open-outline'
                                                : 'mdi-email-outline'
                                        }}</v-icon
                                    >
                                </div>
                            </template>

                            <template v-slot:[`item.title`]="{ item }">
                                <span
                                    @click="openReadMessageDialog(item)"
                                    :class="[
                                        'message-title',
                                        { 'font-weight-bold': !item.is_read && view === 'inbox' },
                                    ]"
                                    >{{ item.title }}</span
                                >
                            </template>

                            <template v-slot:[`item.sender_name`]="{ item }">
                                <span :class="{ 'group-sender': item.sender_type === 'group' }">
                                    {{ item.sender_name }}
                                    <v-chip
                                        v-if="item.sender_type === 'group'"
                                        size="x-small"
                                        label
                                        color="purple"
                                        variant="tonal"
                                        class="ml-1"
                                        >Gruppe</v-chip
                                    >
                                </span>
                            </template>

                            <template v-slot:[`item.recipient_name`]="{ item }">
                                <template v-if="Array.isArray(item.recipient_name)">
                                    <span class="recipient-list">{{
                                        item.recipient_name.join(', ')
                                    }}</span>
                                </template>
                                <template v-else>
                                    <span
                                        :class="{
                                            'group-recipient': item.recipient_type === 'group',
                                        }"
                                    >
                                        {{ item.recipient_name }}
                                        <v-chip
                                            v-if="item.recipient_type === 'group'"
                                            size="x-small"
                                            label
                                            color="purple"
                                            variant="tonal"
                                            class="ml-1"
                                            >Gruppe</v-chip
                                        >
                                    </span>
                                </template>
                            </template>

                            <template v-slot:[`item.folder_name`]="{ item }">
                                <v-chip
                                    v-if="view === 'inbox' && item.recipient_folder_name"
                                    size="x-small"
                                    label
                                    color="info"
                                    variant="tonal"
                                    >{{ item.recipient_folder_name }}</v-chip
                                >
                                <v-chip
                                    v-else-if="view !== 'inbox' && item.sender_folder_name"
                                    size="x-small"
                                    label
                                    color="info"
                                    variant="tonal"
                                    >{{ item.sender_folder_name }}</v-chip
                                >
                                <span v-else>-</span>
                            </template>

                            <template v-slot:[`item.created_at`]="{ item }">
                                <span class="timestamp">{{ formatDate(item.created_at) }}</span>
                            </template>

                            <template v-slot:[`item.actions`]="{ item }">
                                <div class="actions-container">
                                    <v-tooltip
                                        v-if="view === 'inbox' && canEdit"
                                        :text="
                                            item.is_read
                                                ? $t('messageView.markAsUnread')
                                                : $t('messageView.markAsRead')
                                        "
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="toggleMessageReadStatus(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small">{{
                                                    item.is_read
                                                        ? 'mdi-email-outline'
                                                        : 'mdi-email-open-outline'
                                                }}</v-icon>
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip
                                        v-if="view === 'inbox' && canEdit"
                                        :text="item.pinned ? t('unpin') : t('pin')"
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="toggleMessagePinnedStatus(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon
                                                    size="small"
                                                    :color="item.pinned ? 'warning' : undefined"
                                                    >{{
                                                        item.pinned ? 'mdi-pin-off' : 'mdi-pin'
                                                    }}</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip
                                        v-if="view !== 'deleted' && canDelete"
                                        :text="$t('delete')"
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="openDeleteMessageDialog(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small" color="error"
                                                    >mdi-delete</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip
                                        v-if="view === 'deleted' && canEdit"
                                        :text="t('messageView.restore')"
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="restoreMessage(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small" color="success"
                                                    >mdi-restore</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip
                                        v-if="view === 'deleted' && canDelete"
                                        :text="t('messageView.permanentlyDelete')"
                                        location="top"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="deleteMessagePermanently(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small" color="red-darken-3"
                                                    >mdi-delete-forever</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                </div>
                            </template>

                            <template v-slot:no-data>
                                <div class="empty-state">
                                    <v-icon size="40" color="grey-darken-1" class="mb-2">
                                        {{
                                            view === 'inbox'
                                                ? 'mdi-inbox-arrow-down'
                                                : view === 'sent'
                                                  ? 'mdi-email-send-outline'
                                                  : view === 'deleted'
                                                    ? 'mdi-delete-empty'
                                                    : 'mdi-email-off-outline'
                                        }}
                                    </v-icon>
                                    <span>{{ t('messageView.noMessages') }}</span>
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
                                    <span>{{ t('messageView.loadingMessages') }}</span>
                                </div>
                            </template>
                        </v-data-table>
                        <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
                        <KBulkBar
                            :count="kSelected.length"
                            :shown="kFilters.filtered.value.length"
                            :total="(filteredMessages || []).length"
                            @clear="kSelected = []"
                        >
                            <template #actions>
                                <v-btn variant="outlined" size="small" @click="kExportSelection">
                                    {{ t('kTable.exportSelection') }}
                                </v-btn>
                            </template>
                        </KBulkBar>
                    </v-card>

                    <!-- Nachricht lesen Ansicht -->
                    <v-card v-if="isReadingMessage" class="main-card elevation-4">
                        <v-toolbar color="primary" class="dialog-header">
                            <v-btn icon @click="closeReadMessageDialog" class="mr-2">
                                <v-icon>mdi-arrow-left</v-icon>
                            </v-btn>
                            <v-toolbar-title class="text-h6">{{ readMessage.title }}</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-tooltip v-if="canCreate" :text="$t('messageView.reply')" location="bottom">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        @click="replyMessage(readMessage)"
                                        v-bind="props"
                                        class="toolbar-action"
                                    >
                                        <v-icon>mdi-reply</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip v-if="canCreate" :text="$t('messageView.forward')" location="bottom">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        @click="forwardMessage(readMessage)"
                                        v-bind="props"
                                        class="toolbar-action"
                                    >
                                        <v-icon>mdi-share</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                            <v-tooltip v-if="canEdit" :text="$t('messageView.updateFolderNote')" location="bottom">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon
                                        @click="updateMessage"
                                        v-bind="props"
                                        class="toolbar-action"
                                    >
                                        <v-icon>mdi-content-save-edit-outline</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </v-toolbar>

                        <v-card-text class="read-message-content">
                            <v-container>
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <div class="message-meta-item">
                                            <v-icon start color="info" class="mr-2">mdi-account</v-icon>
                                            <strong>{{ $t('messageView.sender') }}:</strong>
                                            <span class="meta-value">
                                                {{ readMessage.sender_name }}
                                                <v-chip
                                                    v-if="readMessage.sender_type === 'group'"
                                                    size="x-small"
                                                    label
                                                    color="purple"
                                                    variant="tonal"
                                                    class="ml-1"
                                                    >Gruppe</v-chip
                                                >
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <div class="message-meta-item">
                                            <v-icon start color="success" class="mr-2"
                                                >mdi-account-multiple</v-icon
                                            >
                                            <strong>{{ $t('messageView.recipient') }}:</strong>
                                            <span class="meta-value">
                                                <template v-if="Array.isArray(readMessage.recipient_name)">
                                                    {{ readMessage.recipient_name.join(', ') }}
                                                </template>
                                                <template v-else>
                                                    {{ readMessage.recipient_name }}
                                                    <v-chip
                                                        v-if="readMessage.recipient_type === 'group'"
                                                        size="x-small"
                                                        label
                                                        color="purple"
                                                        variant="tonal"
                                                        class="ml-1"
                                                        >Gruppe</v-chip
                                                    >
                                                </template>
                                            </span>
                                        </div>
                                    </v-col>
                                </v-row>
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <div class="message-meta-item">
                                            <v-icon start color="warning" class="mr-2"
                                                >mdi-clock-outline</v-icon
                                            >
                                            <strong>{{ $t('messageView.date') }}:</strong>
                                            <span class="meta-value">{{
                                                formatDate(readMessage.created_at)
                                            }}</span>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-if="view === 'inbox' || view === 'deleted'"
                                            label="Ordner (Empfänger)"
                                            v-model="readMessage.recipient_folder_id"
                                            :items="currentViewFolders"
                                            item-title="name"
                                            item-value="id"
                                            clearable
                                            hide-details
                                            density="compact"
                                            variant="outlined"
                                            bg-color="grey-darken-3"
                                            prepend-inner-icon="mdi-folder"
                                        ></v-select>
                                        <v-select
                                            v-if="view === 'sent'"
                                            label="Ordner (Absender)"
                                            v-model="readMessage.sender_folder_id"
                                            :items="currentViewFolders"
                                            item-title="name"
                                            item-value="id"
                                            clearable
                                            hide-details
                                            density="compact"
                                            variant="outlined"
                                            bg-color="grey-darken-3"
                                            prepend-inner-icon="mdi-folder"
                                        ></v-select>
                                    </v-col>
                                </v-row>
                                <v-divider class="my-4"></v-divider>
                                <v-row>
                                    <v-col cols="12">
                                        <div class="message-content-header">
                                            <v-icon start color="primary" class="mr-2"
                                                >mdi-message-text</v-icon
                                            >
                                            <strong>{{ $t('messageView.messageContent') }}:</strong>
                                        </div>
                                        <div class="message-body" v-html="readMessage.body"></div>
                                    </v-col>
                                </v-row>
                                <v-divider class="my-4"></v-divider>
                                <v-row>
                                    <v-col cols="12">
                                        <div class="note-header">
                                            <v-icon start color="amber" class="mr-2">mdi-note-text</v-icon>
                                            <strong>{{ $t('messageView.personalNotes') }}:</strong>
                                        </div>
                                        <v-textarea
                                            v-if="view === 'inbox' || view === 'deleted'"
                                            v-model="readMessage.recipient_note"
                                            rows="3"
                                            variant="outlined"
                                            hide-details
                                            :placeholder="t('messageView.notePlaceholder')"
                                            bg-color="grey-darken-3"
                                            class="note-textarea"
                                        ></v-textarea>
                                        <v-textarea
                                            v-if="view === 'sent'"
                                            v-model="readMessage.sender_note"
                                            rows="3"
                                            variant="outlined"
                                            hide-details
                                            :placeholder="t('messageView.notePlaceholder')"
                                            bg-color="grey-darken-3"
                                            class="note-textarea"
                                        ></v-textarea>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>

                        <v-divider></v-divider>

                        <v-card-actions class="pa-4">
                            <v-btn
                                v-if="view === 'inbox'"
                                color="warning"
                                variant="tonal"
                                prepend-icon="mdi-pin"
                                class="mr-auto"
                                @click="toggleMessagePinnedStatus(readMessage)"
                            >
                                {{ readMessage.pinned ? t('unpin') : t('pin') }}
                            </v-btn>

                            <v-spacer v-else></v-spacer>

                            <v-btn
                                color="error"
                                variant="tonal"
                                prepend-icon="mdi-delete"
                                class="mr-2"
                                @click="
                                    openDeleteMessageDialog(readMessage);
                                    closeReadMessageDialog();
                                "
                                v-if="view !== 'deleted'"
                            >
                                Löschen
                            </v-btn>

                            <v-btn
                                color="primary"
                                variant="elevated"
                                prepend-icon="mdi-content-save"
                                @click="updateMessage"
                                class="action-button"
                            >
                                Speichern
                            </v-btn>
                        </v-card-actions>
                    </v-card>

                    <!-- Ordneransicht -->
                    <v-card v-if="view === 'folders'" class="main-card elevation-4">
                        <v-toolbar flat density="compact" class="card-toolbar">
                            <v-toolbar-title class="text-h6">{{
                                currentViewTitle
                            }}</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-text-field
                                v-model="search"
                                label="Suche"
                                prepend-inner-icon="mdi-magnify"
                                hide-details
                                density="compact"
                                variant="outlined"
                                bg-color="grey-darken-3"
                                class="search-field"
                            />
                        </v-toolbar>

                        <v-divider></v-divider>

                        <v-data-table
                            :headers="folderHeaders"
                            :items="filteredFolders"
                            :search="search"
                            :items-per-page="25"
                            item-value="id"
                            :loading="loadingFolders"
                            hover
                            class="folder-table"
                         density="compact">
                            <template v-slot:[`item.name`]="{ item }">
                                <div class="d-flex align-center">
                                    <v-icon start color="info" class="mr-2">mdi-folder</v-icon>
                                    <span class="folder-name">{{ item.name }}</span>
                                </div>
                            </template>

                            <template v-slot:[`item.actions`]="{ item }">
                                <div class="actions-container">
                                    <v-tooltip v-if="canEdit" text="Bearbeiten" location="top">
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="openEditFolderDialog(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small" color="info"
                                                    >mdi-pencil</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                    <v-tooltip v-if="canDelete" text="Löschen" location="top">
                                        <template v-slot:activator="{ props }">
                                            <v-btn
                                                icon
                                                variant="text"
                                                size="small"
                                                @click="openDeleteFolderDialog(item)"
                                                v-bind="props"
                                                class="action-icon"
                                            >
                                                <v-icon size="small" color="error"
                                                    >mdi-delete</v-icon
                                                >
                                            </v-btn>
                                        </template>
                                    </v-tooltip>
                                </div>
                            </template>

                            <template v-slot:no-data>
                                <div class="empty-state">
                                    <v-icon size="40" color="grey-darken-1" class="mb-2"
                                        >mdi-folder-off-outline</v-icon
                                    >
                                    <span>{{ t('messageView.noFolders') }}</span>
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
                                    <span>{{ t('messageView.loadingFolders') }}</span>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>
                </v-container>
            </div>
        </v-main>

        <!-- Neue Nachricht Dialog -->
        <v-dialog v-model="newMessageDialog" max-width="70%" class="custom-dialog">
            <v-card class="dialog-card">
                <v-toolbar color="primary" class="dialog-header">
                    <v-toolbar-title class="text-h5">{{ t('messageView.newMessage') }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon @click="newMessageDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <v-card-text class="pa-4">
                    <v-form ref="newMessageFormRef" v-model="isNewMessageFormValid">
                        <v-select
                            :label="$t('messageView.sender')"
                            v-model="newMessage.sender_id"
                            :items="senderOptions"
                            item-title="label"
                            item-value="id"
                            required
                            :rules="[requiredRule]"
                            class="mb-3"
                            variant="outlined"
                            density="compact"
                            bg-color="grey-darken-3"
                        ></v-select>
                        <v-autocomplete
                            :label="$t('messageView.recipient')"
                            multiple
                            chips
                            closable-chips
                            v-model="newMessage.recipient_ids"
                            :items="recipientOptions"
                            item-title="label"
                            item-value="id"
                            :rules="[requiredRuleMultiple]"
                            clearable
                            required
                            class="mb-3"
                            variant="outlined"
                            density="compact"
                            bg-color="grey-darken-3"
                        ></v-autocomplete>
                        <v-text-field
                            :label="$t('messageView.subject')"
                            v-model="newMessage.title"
                            required
                            :rules="[requiredRule]"
                            class="mb-3"
                            variant="outlined"
                            density="compact"
                            bg-color="grey-darken-3"
                        ></v-text-field>

                        <TiptapEditor
                            v-model="newMessage.body"
                            :placeholder="t('messageView.bodyPlaceholder')"
                            :show-character-count="false"
                            :show-source-button="true"
                            :editable="true"
                            class="message-editor"
                        />
                        <div v-if="!newMessage.body" class="error-message">
                            <div class="v-messages__message">{{ t('requiredField') }}</div>
                        </div>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="tonal" @click="newMessageDialog = false" class="mr-2"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="addNewMessage"
                        :disabled="!isNewMessageFormValid || !newMessage.body"
                        class="action-button"
                    >
                        <v-icon start>mdi-send</v-icon>
                        Senden
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog
            v-model="deleteConfirmationDialog"
            max-width="500"
            persistent
            class="custom-dialog"
        >
            <v-card class="dialog-card">
                <v-toolbar :color="deleteConfirmationColor" class="dialog-header">
                    <v-icon start class="mr-2">
                        {{
                            deleteType === 'message_permanent'
                                ? 'mdi-delete-forever'
                                : deleteType === 'folder'
                                  ? 'mdi-folder-remove'
                                  : 'mdi-delete-alert'
                        }}
                    </v-icon>
                    <v-toolbar-title class="text-h5">{{ deleteConfirmationTitle }}</v-toolbar-title>
                </v-toolbar>

                <v-card-text class="pa-4 pt-5">
                    <div class="confirmation-text">{{ deleteConfirmationText }}</div>

                    <v-alert
                        v-if="deleteType === 'message_permanent'"
                        type="warning"
                        variant="tonal"
                        density="compact"
                        icon="mdi-alert"
                        class="mt-4"
                        border="start"
                    >
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </v-alert>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="tonal" @click="cancelDelete" class="mr-2">Abbrechen</v-btn>
                    <v-btn
                        :color="deleteConfirmationColor"
                        variant="elevated"
                        @click="confirmDelete"
                        :loading="deleting"
                        prepend-icon="mdi-check"
                        class="action-button"
                    >
                        {{ deleteConfirmationActionText }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Folder Dialog -->
        <v-dialog v-model="folderDialog" max-width="600" persistent class="custom-dialog">
            <v-card class="dialog-card">
                <v-toolbar color="info" class="dialog-header">
                    <v-icon start class="mr-2">
                        {{ isEditingFolder ? 'mdi-folder-edit' : 'mdi-folder-plus' }}
                    </v-icon>
                    <v-toolbar-title class="text-h5">{{
                        isEditingFolder ? 'Ordner bearbeiten' : 'Neuen Ordner erstellen'
                    }}</v-toolbar-title>
                </v-toolbar>

                <v-card-text class="pa-4">
                    <v-form ref="folderFormRef" v-model="isFolderFormValid">
                        <v-select
                            :label="$t('messageView.mailboxOwner')"
                            v-model="folderFormData.ownerId"
                            :items="folderOwnerOptions"
                            item-title="label"
                            item-value="id"
                            required
                            :rules="[requiredRule]"
                            :disabled="isEditingFolder"
                            class="mb-3"
                            variant="outlined"
                            density="compact"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-account-check"
                        ></v-select>
                        <v-text-field
                            :label="$t('messageView.folderName')"
                            v-model="folderFormData.name"
                            required
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-folder"
                        ></v-text-field>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="tonal" @click="folderDialog = false" class="mr-2"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="info"
                        variant="elevated"
                        @click="saveFolder"
                        :disabled="!isFolderFormValid"
                        :loading="savingFolder"
                        prepend-icon="mdi-content-save"
                        class="action-button"
                    >
                        Speichern
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar
            v-model="snackbar.visible"
            :color="snackbar.color"
            multi-line
            timeout="4000"
            class="custom-snackbar"
        >
            <div class="d-flex align-center">
                <v-icon start class="mr-2">
                    {{
                        snackbar.color === 'success'
                            ? 'mdi-check-circle'
                            : snackbar.color === 'error'
                              ? 'mdi-alert-circle'
                              : snackbar.color === 'warning'
                                ? 'mdi-alert'
                                : 'mdi-information'
                    }}
                </v-icon>
                {{ snackbar.message }}
            </div>
            <template v-slot:actions>
                <v-btn icon variant="text" @click="snackbar.visible = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </template>
        </v-snackbar>
    </v-container>
</template>

<style scoped>
/* Base Styles */
.mail-system-container {
    background-color: var(--k-canvas);
    min-height: 100vh;
    color: var(--k-ink);
}

/* Sidebar Styles */
.sidebar-drawer {
    background: var(--k-surface) !important;
    border-right: 1px solid var(--k-line);
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-list {
    padding-top: 16px;
}

.sidebar-item {
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.sidebar-item:hover {
    background: var(--k-accent-weak);
}

.nav-badge {
    position: absolute;
    top: 4px;
    right: 4px;
}

/* Main Content */
.main-content {
    padding: 16px 0;
}

/* Card Styles */
.main-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.card-toolbar {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--k-line);
}

/* Filter Toolbar */
.filter-toolbar {
    background: transparent !important;
    margin-bottom: 12px;
}

.filter-btn {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 20px;
}

.filter-btn:hover {
    transform: translateY(-2px);
}

/* Data Tables */
.message-table,
.folder-table {
    background: transparent !important;
}

.message-table :deep(th),
.folder-table :deep(th) {
    color: var(--k-ink-faint) !important;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
    background-color: var(--k-sunken) !important;
}

.message-table :deep(tr:hover),
.folder-table :deep(tr:hover) {
    background: var(--k-sunken) !important;
}

.message-table :deep(tr),
.folder-table :deep(tr) {
    border-bottom: 1px solid rgba(148, 163, 184, 0.1) !important;
    transition: background-color 0.2s ease;
}

/* Message Title */
.message-title {
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--k-ink);
    position: relative;
}

.message-title:hover {
    color: var(--k-accent);
}

.message-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 1px;
    background-color: var(--k-accent);
    transition: width 0.3s ease;
}

.message-title:hover::after {
    width: 100%;
}

.font-weight-bold {
    font-weight: bold !important;
    color: var(--k-accent);
}

/* Form Controls */
.search-field {
    max-width: 300px;
    transition: all 0.2s ease;
}

.search-field:focus-within {
    box-shadow: 0 0 0 2px var(--k-accent-line);
}

.folder-select {
    max-width: 250px;
}

/* Action Icons */
.action-icon {
    opacity: 0.7;
    transition: all 0.2s;
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

.actions-container {
    display: flex;
    gap: 4px;
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

/* Action Buttons */
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

/* Custom Dialogs */
.custom-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
}

.dialog-header {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover)) !important;
}

/* Message Editor */
.message-editor {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    min-height: 250px;
    margin-bottom: 8px;
}

.message-editor :deep(.ProseMirror) {
    min-height: 250px;
    background-color: var(--k-sunken);
    color: var(--k-ink);
    padding: 16px;
}

.error-message {
    color: rgb(244, 67, 54);
    font-size: 12px;
    margin-top: 4px;
    padding-left: 16px;
}

/* Read Message Dialog */
.read-message-content {
    padding: 24px 16px;
}

.message-meta-item {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
    font-size: 0.95rem;
}

.meta-value {
    margin-left: 8px;
}

.message-content-header,
.note-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 1rem;
}

.message-body {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    padding: 16px;
    background-color: var(--k-sunken);
    max-height: 50vh;
    overflow-y: auto;
    line-height: 1.6;
}

.note-textarea {
    background-color: var(--k-sunken);
    border-radius: 8px;
}

.toolbar-action {
    transition: all 0.2s ease;
}

.toolbar-action:hover {
    transform: scale(1.15);
}

/* Confirmation Dialog */
.confirmation-text {
    font-size: 1rem;
    line-height: 1.6;
}

/* Custom Snackbar */
.custom-snackbar {
    border-radius: 8px;
}

/* Special Typography */
.timestamp {
    font-size: 0.85rem;
    color: var(--k-ink-faint);
}

.group-sender,
.group-recipient {
    display: flex;
    align-items: center;
}

.folder-name {
    font-weight: 500;
}

/* Responsive Adjustments */
@media (max-width: 960px) {
    .search-field,
    .folder-select {
        max-width: 100%;
    }

    .filter-toolbar {
        flex-wrap: wrap;
    }

    .filter-btn {
        margin-bottom: 8px;
    }

    .message-meta-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .meta-value {
        margin-left: 26px;
        margin-top: 4px;
    }
}

/* Print-friendly adjustments */
@media print {
    .sidebar-drawer {
        display: none !important;
    }

    .main-content {
        padding-left: 0 !important;
    }

    .filter-toolbar,
    .card-toolbar,
    .actions-container {
        display: none !important;
    }

    .message-body {
        background-color: #fff !important;
        color: black !important;
        border: 1px solid #ddd;
    }
}
</style>
