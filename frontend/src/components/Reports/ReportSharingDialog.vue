<template>
    <v-dialog v-model="dialogVisible" max-width="700px">
        <v-card>
            <v-card-title class="headline">
                {{
                    isSharedReport
                        ? t('reportSharingDialog.sharedReportDetails')
                        : t('reportSharingDialog.reportSharingSettings')
                }}
            </v-card-title>

            <v-card-text>
                <v-alert v-if="errorMessage" type="error" density="compact" class="mb-4">
                    {{ errorMessage }}
                </v-alert>

                <!-- Loading state -->
                <div v-if="loading" class="d-flex justify-center align-center pa-4">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                    <span class="ml-2">{{ t('reportSharingDialog.loadingInfo') }}</span>
                </div>

                <div v-else>
                    <!-- Report info header -->
                    <div class="mb-4 pb-2 border-bottom">
                        <div v-if="sharingInfo?.report" class="d-flex align-center">
                            <div>
                                <h3 class="text-h6 mb-1">{{ sharingInfo.report.title }}</h3>
                                <div class="text-body-2 text-grey">
                                    <v-icon size="small" class="mr-1">mdi-calendar</v-icon>
                                    {{ formatDate(sharingInfo.report.created_at || '') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- For reports shared with this authority -->
                    <div v-if="isSharedReport && sharingInfo" class="mb-4">
                        <v-alert color="info" variant="tonal">
                            <div class="text-subtitle-2">
                                <v-icon size="small" class="mr-1">mdi-share</v-icon>
                                This report is shared by
                                {{ sharingInfo.shared_from?.owner_display_name }} ({{
                                    sharingInfo.shared_from?.owner_name
                                }})
                            </div>
                            <div class="text-body-2 mt-1">
                                {{ t('reportSharingDialog.accessLevel') }}
                                <strong>{{
                                    capitalizeFirstLetter(sharingInfo.shared_from?.access_level)
                                }}</strong>
                            </div>
                        </v-alert>
                    </div>

                    <!-- For reports owned by this authority -->
                    <template v-else>
                        <!-- Visibility settings -->
                        <v-card class="mb-4" variant="outlined">
                            <v-card-title class="text-subtitle-1 py-2">
                                <v-icon class="mr-2">mdi-eye</v-icon> Internal Visibility
                            </v-card-title>
                            <v-card-text>
                                <p class="text-body-2 mb-2">
                                    Control who can see this report within your authority:
                                </p>

                                <v-radio-group
                                    v-model="selectedVisibility"
                                    @update:model-value="visibilityChanged = true"
                                >
                                    <v-radio
                                        value="public"
                                        label="Public - Visible to all members of the authority"
                                    ></v-radio>
                                    <v-radio
                                        value="private"
                                        label="Private - Only visible to report creator and administrators"
                                    ></v-radio>
                                    <v-radio
                                        value="specific_roles"
                                        label="Specific Groups - Only visible to selected groups/roles"
                                    ></v-radio>
                                </v-radio-group>

                                <!-- Helper text for visibility -->
                                <v-alert v-if="visibilityChanged" density="compact" type="info" variant="tonal" class="mt-2 text-body-2">
                                    <strong>Note:</strong> Changing visibility will be applied immediately when saving changes.
                                    {{ selectedVisibility === 'specific_roles' ? 'Don\'t forget to select groups below.' : '' }}
                                </v-alert>

                                <!-- Group selection - Using kdd_roles -->
                                <div v-if="selectedVisibility === 'specific_roles'" class="mt-2">
                                    <div
                                        v-if="!sharingOptions.groups || sharingOptions.groups.length === 0"
                                        class="text-body-2 text-grey"
                                    >
                                        No groups/roles available. Please create roles first.
                                    </div>

                                    <template v-else>
                                        <p class="text-body-2 mb-2">
                                            Select roles who can access this report:
                                        </p>
                                        <v-chip-group
                                            v-model="selectedGroups"
                                            column
                                            multiple
                                            @update:model-value="groupsChanged = true"
                                        >
                                            <v-chip
                                                v-for="group in sharingOptions.groups"
                                                :key="group.id"
                                                :value="group.id"
                                                :title="group.description || ''"
                                                filter
                                                variant="outlined"
                                            >
                                                {{ group.name }}
                                            </v-chip>
                                        </v-chip-group>
                                    </template>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- External sharing with other authorities that have SHARE_REPORTS feature -->
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 py-2">
                                <v-icon class="mr-2">mdi-share-variant</v-icon> Share with Other
                                Authorities
                            </v-card-title>
                            <v-card-text>
                                <p class="text-body-2 mb-2">
                                    Share this report with other authorities:
                                </p>
                                
                                <!-- Helper text for authority sharing -->
                                <v-alert v-if="sharingChanged" density="compact" type="info" variant="tonal" class="mb-3 text-body-2">
                                    <strong>Note:</strong> When sharing a report, the recipient authority will be able to view it in their shared reports section.
                                    Setting access level to <strong>Edit</strong> allows them to make changes to the report.
                                </v-alert>

                                <div
                                    v-if="!sharingOptions.authorities || sharingOptions.authorities.length === 0"
                                    class="text-body-2 text-grey"
                                >
                                    No other authorities with sharing capability available.
                                </div>

                                <v-data-table
                                    v-else
                                    :headers="authorityHeaders"
                                    :items="sharingOptions.authorities"
                                    hide-default-footer
                                    class="elevation-1 mb-4"
                                >
                                    <template #[`item.display_name`]="{ item }">
                                        {{ item.display_name }}
                                        <div class="text-caption text-grey">{{ item.name }}</div>
                                    </template>

                                    <template #[`item.is_shared`]="{ item }">
                                        <v-checkbox
                                            :model-value="!!sharedAuthorities[item.id]"
                                            @update:model-value="(val) => {
                                                sharedAuthorities[item.id] = !!val;
                                                authorityShareChanged(item.id);
                                            }"
                                            hide-details
                                            density="compact"
                                        ></v-checkbox>
                                    </template>

                                    <template #[`item.access_level`]="{ item }">
                                        <v-select
                                            v-if="sharedAuthorities[item.id]"
                                            v-model="accessLevels[item.id]"
                                            :items="[
                                                { title: 'Read Only', value: 'read' },
                                                { title: 'Edit', value: 'edit' },
                                            ]"
                                            item-title="title"
                                            item-value="value"
                                            @update:model-value="accessLevelChanged(item.id)"
                                            density="compact"
                                            variant="outlined"
                                            hide-details
                                            class="access-level-select"
                                        ></v-select>
                                        <span v-else class="text-grey">-</span>
                                    </template>
                                </v-data-table>

                                <!-- Currently shared with -->
                                <div v-if="sharedAuthorityCount > 0" class="mt-4 pa-3 bg-grey-lighten-4 rounded">
                                    <div class="text-subtitle-2 mb-2 d-flex align-center">
                                        <v-icon size="small" class="mr-2">mdi-check-circle</v-icon>
                                        Currently shared with:
                                    </div>
                                    <v-chip
                                        v-for="authority in sharingInfo?.shared_with"
                                        :key="authority.id"
                                        class="ma-1"
                                        color="primary"
                                        variant="outlined"
                                    >
                                        <template #prepend>
                                            <v-icon size="x-small" class="mr-1">mdi-shield-{{ authority.access_level === 'read' ? 'check' : 'edit' }}</v-icon>
                                        </template>
                                        {{ authority.display_name }}
                                        <span class="text-caption ml-1">({{ capitalizeFirstLetter(authority.access_level) }})</span>
                                    </v-chip>
                                </div>
                            </v-card-text>
                        </v-card>
                    </template>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn variant="text" @click="close">Close</v-btn>
                <v-btn
                    v-if="!isSharedReport"
                    color="primary"
                    @click="saveAll"
                    :disabled="
                        loading ||
                        saving ||
                        (!visibilityChanged && !groupsChanged && !sharingChanged)
                    "
                    :loading="saving"
                >
                    {{ t('reportSharingDialog.saveChanges') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api';
import ReportService, { type SharingInfo, type SharingOptions } from '@/services/ReportService';

// Props
interface Props {
  modelValue?: boolean
  reportId?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false
});

// Emits
const emit = defineEmits(['update:modelValue', 'sharing-updated']);
const { t } = useI18n();

// Data
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref<string | null>(null);
const sharingInfo = ref<SharingInfo | null>(null);
const sharingOptions = ref<SharingOptions>({
    authorities: [],
    groups: [],
});

// Form state
const selectedVisibility = ref<'public' | 'private' | 'specific_roles'>('public');
const selectedGroups = ref<number[]>([]);
const sharedAuthorities = ref<Record<number, boolean>>({});
const accessLevels = ref<Record<number, string>>({});

// Track changes
const visibilityChanged = ref(false);
const groupsChanged = ref(false);
const sharingChanged = ref(false);

// Table headers
const authorityHeaders = [
    { title: 'Authority', key: 'display_name', align: 'start' as const },
    { title: 'Share', key: 'is_shared', align: 'center' as const, width: '100px' },
    { title: 'Access Level', key: 'access_level', align: 'center' as const, width: '150px' },
];

// Computed
const dialogVisible = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});

const isSharedReport = computed(() => {
    return !!sharingInfo.value?.shared_from;
});

const sharedAuthorityCount = computed(() => {
    return sharingInfo.value?.shared_with?.length || 0;
});

// Watchers
watch(
    () => props.modelValue,
    newVal => {
        if (newVal) {
            loadSharingData();
        }
    }
);

// Execute on component mount
onMounted(() => {
    if (props.modelValue) {
        loadSharingData();
    }
});

// Methods
async function loadSharingData() {
    loading.value = true;
    errorMessage.value = null;
    visibilityChanged.value = false;
    groupsChanged.value = false;
    sharingChanged.value = false;

    try {
        console.log('Loading sharing data for report ID:', props.reportId);
        
        // Initialize sharing options with empty arrays
        sharingOptions.value = {
            authorities: [],
            groups: []
        };
        
        // Get sharing options (authorities and groups) first
        console.log('Fetching sharing options...');
        try {
            const options = await ReportService.getSharingOptions();
            console.log('Component received sharing options:', options);
            
            // Check if we actually have authorities and groups
            console.log('Authorities count:', options.authorities?.length || 0);
            console.log('Groups count:', options.groups?.length || 0);
            
            // Make sure to assign all options properly
            if (options && typeof options === 'object') {
                if (Array.isArray(options.authorities) && options.authorities.length > 0) {
                    sharingOptions.value.authorities = [...options.authorities];
                }
                
                if (Array.isArray(options.groups) && options.groups.length > 0) {
                    sharingOptions.value.groups = [...options.groups];
                }
            }
            
            console.log('Sharing options after assignment:', sharingOptions.value);
        } catch (optionsError) {
            console.error('Error fetching sharing options:', optionsError);
            // Continue with report sharing info even if options failed
        }

        // Get sharing information for the report
        console.log('Fetching report sharing information...');
        try {
            const sharingData = await ReportService.getReportSharing(props.reportId);
            console.log('========== RECEIVED SHARING DATA ==========');
            console.log(JSON.stringify(sharingData, null, 2));
            console.log('==========================================');
            
            sharingInfo.value = sharingData;

            // Debug visibility field
            console.log('Visibility from backend:', sharingData.visibility);
            
            // Initialize form state with the correct visibility value
            if (sharingData.visibility) {
                selectedVisibility.value = sharingData.visibility as
                    | 'public'
                    | 'private'
                    | 'specific_roles';
                console.log('Selected visibility set to:', selectedVisibility.value);
            }

            // Extract group IDs from the groups array
            selectedGroups.value = sharingData.groups ? sharingData.groups.map((g: { id: number }) => g.id) : [];
            console.log('Selected groups:', selectedGroups.value);

            // Debug shared authorities
            if (Array.isArray(sharingData.shared_with)) {
                console.log(`Found ${sharingData.shared_with.length} shared authorities:`, 
                    sharingData.shared_with.map(a => `${a.display_name} (ID: ${a.id})`));
            } else {
                console.log('shared_with is not an array:', sharingData.shared_with);
            }

            // Reset shared authorities and access levels
            sharedAuthorities.value = {};
            accessLevels.value = {};

            // IMPORTANT: First set up the authorities that are already shared
            if (Array.isArray(sharingData.shared_with) && sharingData.shared_with.length > 0) {
                sharingData.shared_with.forEach((sharedAuth: any) => {
                    if (sharedAuth && sharedAuth.id) {
                        console.log(`✓ Setting ${sharedAuth.display_name} (ID: ${sharedAuth.id}) as shared with access level: ${sharedAuth.access_level}`);
                        sharedAuthorities.value[sharedAuth.id] = true;
                        accessLevels.value[sharedAuth.id] = sharedAuth.access_level || 'read';
                    }
                });
            } else {
                console.log('No existing shares found');
            }

            // Then set defaults for all available authorities if not already set
            if (Array.isArray(sharingOptions.value.authorities)) {
                sharingOptions.value.authorities.forEach(authority => {
                    console.log(`Checking authority ${authority.display_name} (ID: ${authority.id}): isShared=${sharedAuthorities.value[authority.id] === true}`);
                    
                    // Only set defaults if not already set
                    if (sharedAuthorities.value[authority.id] === undefined) {
                        sharedAuthorities.value[authority.id] = false;
                    }
                    if (accessLevels.value[authority.id] === undefined) {
                        accessLevels.value[authority.id] = 'read';
                    }
                });
            }
            
            console.log('Final shared authorities:', sharedAuthorities.value);
            console.log('Final access levels:', accessLevels.value);
        } catch (error) {
            console.error('Error fetching report sharing info:', error);
            errorMessage.value = 'Failed to load sharing information. Please try again.';
        }
    } catch (error) {
        console.error('Error loading sharing data:', error);
        errorMessage.value = 'Failed to load sharing information. Please try again.';
    } finally {
        loading.value = false;
    }
}

function authorityShareChanged(authorityId: number) {
    sharingChanged.value = true;
}

function accessLevelChanged(authorityId: number) {
    sharingChanged.value = true;
}

function close() {
    dialogVisible.value = false;
}

async function saveAll() {
    if (loading.value || saving.value) return;

    saving.value = true;
    errorMessage.value = null;

    try {
        // Prepare data for the update
        const updateData: {
            visibility?: 'public' | 'private' | 'specific_roles';
            groups?: number[];
            sharedAuthorities?: Record<number, boolean>;
            accessLevels?: Record<number, string>;
        } = {};

        // Only include changed data
        if (visibilityChanged.value) {
            updateData.visibility = selectedVisibility.value;
        }

        if (
            groupsChanged.value ||
            (visibilityChanged.value && selectedVisibility.value === 'specific_roles')
        ) {
            updateData.groups = selectedGroups.value;
        }

        if (sharingChanged.value) {
            updateData.sharedAuthorities = sharedAuthorities.value;
            updateData.accessLevels = accessLevels.value;
        }

        // Update sharing settings
        await ReportService.updateSharingSettings(props.reportId, updateData);

        // Emit success event
        emit('sharing-updated');

        // Close dialog
        dialogVisible.value = false;
    } catch (error) {
        console.error('Error saving sharing settings:', error);
        errorMessage.value = 'Failed to save sharing settings. Please try again.';
    } finally {
        saving.value = false;
    }
}

// Utility functions
function formatDate(dateString: string): string {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE');
    } catch (e) {
        return dateString;
    }
}

function capitalizeFirstLetter(string: string = ''): string {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
</script>

<style scoped>
.border-bottom {
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.access-level-select {
    max-width: 120px;
}
</style>
