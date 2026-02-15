<template>
  <div class="group-manager">
    <v-card class="mb-4">
      <!-- Collapsible header with toggle button -->
      <v-card-title class="d-flex align-center collapsible-header" @click="toggleCollapsed">
        <v-icon class="mr-2">mdi-account-group</v-icon>
        {{ t('calendar.groups.title') }}
        <v-spacer></v-spacer>
        <v-btn
          v-if="!isCollapsed"
          color="primary"
          prepend-icon="mdi-plus"
          @click.stop="openCreateDialog"
          :disabled="!canCreateGroup"
          class="mr-2"
        >
          {{ t('calendar.groups.newGroup') }}
        </v-btn>
        <v-icon>{{ isCollapsed ? 'mdi-chevron-down' : 'mdi-chevron-up' }}</v-icon>
      </v-card-title>

      <!-- Collapsible content -->
      <v-expand-transition>
        <div v-show="!isCollapsed">
          <v-card-text>
            <v-row>
              <!-- Full width cards with reduced height -->
              <v-col v-for="group in groups" :key="group.id" cols="12" sm="12" md="12">
                <v-card variant="outlined" :style="{ borderLeft: `4px solid ${group.color}` }" class="group-card compact">
                  <div class="d-flex align-center">
                    <div class="flex-grow-1">
                      <v-card-title class="py-2">
                        <v-icon :color="group.color" class="mr-2">mdi-circle</v-icon>
                        {{ group.name }}
                      </v-card-title>
                    </div>
                    <div class="group-actions pr-2">
                      <v-btn
                        v-if="group.can_manage"
                        icon="mdi-pencil"
                        variant="text"
                        size="small"
                        @click="editGroup(group)"
                      ></v-btn>
                      <v-btn
                        v-if="group.can_manage"
                        icon="mdi-delete"
                        variant="text"
                        size="small"
                        color="error"
                        @click="confirmDeleteGroup(group)"
                      ></v-btn>
                    </div>
                  </div>
                  <v-card-text class="py-1">
                    <div class="d-flex justify-space-between align-center">
                      <div class="text-body-2 text-truncate description">
                        {{ group.description || t('calendar.groups.noDescription') }}
                      </div>
                      <div class="text-caption ml-2">
                        <v-chip size="x-small" color="primary" text-color="white">
                          {{ group.member_count || 0 }} {{ t('calendar.groups.members') }}
                        </v-chip>  
                      </div>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </div>
      </v-expand-transition>
    </v-card>

    <!-- Dialog für Erstellung/Bearbeitung -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          {{ isEditing ? t('calendar.groups.editGroup') : t('calendar.groups.createGroup') }}
        </v-card-title>
        <v-card-text>
          <v-form ref="form" v-model="isFormValid">
            <v-text-field
              v-model.trim="formName"
              :label="t('calendar.groups.groupName')"
              :rules="[v => !!v || t('calendar.groups.nameRequired')]"
              required
            ></v-text-field>

            <v-textarea
              v-model.trim="formDescription"
              :label="t('calendar.groups.description')"
              rows="3"
            ></v-textarea>

            <div class="color-selection mb-4">
              <div class="text-subtitle-1 mb-2">{{ t('calendar.groups.groupColor') }}</div>
              <div class="color-grid">
                <v-btn
                  v-for="color in predefinedColors"
                  :key="color"
                  :color="color"
                  class="color-btn"
                  :class="{ 'selected': formColor === color }"
                  @click="formColor = color"
                  variant="flat"
                  size="small"
                  icon
                >
                  <v-icon v-if="formColor === color" color="white">mdi-check</v-icon>
                </v-btn>
              </div>
            </div>

            <v-autocomplete
              v-model="formMembers"
              :items="availableUsers"
              item-title="username"
              item-value="id"
              :label="t('calendar.groups.members')"
              multiple
              chips
              closable-chips
            >
              <template v-slot:chip="{ props, item }">
                <v-chip
                  v-bind="props"
                  :color="formColor"
                  text-color="white"
                >
                  {{ item.raw.username }}
                </v-chip>
              </template>
            </v-autocomplete>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="dialog = false">
            {{ t('cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            :disabled="!isFormValid"
            @click="saveGroup"
          >
            {{ isEditing ? t('save') : t('calendar.groups.create') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Bestätigungsdialog für Löschen -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title>{{ t('calendar.groups.deleteGroup') }}</v-card-title>
        <v-card-text>
          {{ t('calendar.groups.deleteConfirm', { name: selectedGroup?.name }) }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteDialog = false">
            {{ t('cancel') }}
          </v-btn>
          <v-btn color="error" @click="deleteGroup">
            {{ t('delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'vue-toastification';
import { apiClientAuth } from '@/api';
import { useI18n } from 'vue-i18n';

const toast = useToast();
const { t } = useI18n();

// Define TS interfaces at top of script
interface GroupMember {
  user_id: number;
  username: string;
  can_edit: boolean;
  can_delete: boolean;
  can_manage: boolean;
  can_manage_members: boolean;
}

interface Group {
  id: number;
  name: string;
  description: string;
  color: string;
  created_by: number;
  created_at: string;
  is_member: boolean;
  can_manage: boolean;
  member_count: number;
  members: GroupMember[];
}

// State
const groups = ref<Group[]>([]);
const dialog = ref(false);
const deleteDialog = ref(false);
const isFormValid = ref(false);
const isEditing = ref(false);
const selectedGroup = ref<Group | null>(null);
const isCollapsed = ref(true); // Default to collapsed state

// Form fields as separate reactive variables
const formName = ref('');
const formDescription = ref('');
const formColor = ref('#3788d8');
const formMembers = ref<number[]>([]);

const availableUsers = ref<any[]>([]);

// Collapse toggle function
const toggleCollapsed = () => {
  isCollapsed.value = !isCollapsed.value;
};

// Computed
const canCreateGroup = computed(() => {
  // Hier können Sie die Berechtigungsprüfung implementieren
  return true;
});

// Methods
const loadGroups = async () => {
  try {
    const response = await apiClientAuth.get('/calendar?action=getGroups');
    groups.value = response.data;
  } catch (error) {
    toast.error(t('toast.loadGroupsError'));
  }
};

const loadUsers = async () => {
  try {
    const response = await apiClientAuth.get('/user?action=getUsersWithEmployee');
    availableUsers.value = response.data;
  } catch (error) {
    toast.error(t('toast.loadUsersError'));
  }
};

const openCreateDialog = () => {
  isEditing.value = false;
  formName.value = '';
  formDescription.value = '';
  formColor.value = '#3788d8';
  formMembers.value = [];
  dialog.value = true;
};

const editGroup = (group: Group) => {
  isEditing.value = true;
  selectedGroup.value = group;
  formName.value = group.name;
  formDescription.value = group.description || '';
  formColor.value = group.color || '#3788d8';
  formMembers.value = group.members?.map(m => m.user_id) || [];
  dialog.value = true;
};

const saveGroup = async () => {
  try {
    // Create the data object with proper type
    const data: {
      id?: number;
      name: string;
      description: string;
      color: string;
      members: {
        user_id: number;
        can_edit: boolean;
        can_delete: boolean;
        can_manage_members: boolean;
      }[];
    } = {
      name: formName.value,
      description: formDescription.value,
      color: formColor.value,
      members: formMembers.value.map(userId => {
        // If editing, find existing member permissions
        if (isEditing.value && selectedGroup.value && selectedGroup.value.members) {
          const existingMember = selectedGroup.value.members.find(m => m.user_id === userId);
          if (existingMember) {
            return {
              user_id: userId,
              can_edit: existingMember.can_edit || true,
              can_delete: existingMember.can_delete || false,
              can_manage_members: existingMember.can_manage_members || false
            };
          }
        }
        // Default permissions for new members
        return {
          user_id: userId,
          can_edit: true,
          can_delete: false,
          can_manage_members: false
        };
      })
    };

    if (isEditing.value && selectedGroup.value) {
      data.id = selectedGroup.value.id;
      await apiClientAuth.post('/calendar?action=updateGroup', data);
      toast.success(t('toast.groupUpdateSuccess'));
    } else {
      await apiClientAuth.post('/calendar?action=createGroup', data);
      toast.success(t('toast.groupCreateSuccess'));
    }

    dialog.value = false;
    loadGroups();
  } catch (error) {
    toast.error(t('toast.groupSaveError'));
  }
};

const confirmDeleteGroup = (group: Group) => {
  selectedGroup.value = group;
  deleteDialog.value = true;
};

const deleteGroup = async () => {
  try {
    await apiClientAuth.post('/calendar?action=deleteGroup', {
      id: selectedGroup.value?.id
    });
    toast.success(t('toast.groupDeleteSuccess'));
    deleteDialog.value = false;
    loadGroups();
  } catch (error) {
    toast.error(t('toast.groupDeleteError'));
  }
};

// Lifecycle
onMounted(() => {
  loadGroups();
  loadUsers();
});

// In the script section, update the predefined colors and add the selectColor method:
const predefinedColors = [
  // Primärfarben
  '#3788d8', // Blau
  '#2c3e50', // Dunkelblau
  '#42b983', // Grün
  '#ff7675', // Rot
  '#00b894', // Türkis
  '#0984e3', // Hellblau
  '#6c5ce7', // Lila
  '#e84393', // Pink
  '#fdcb6e', // Gelb
  '#e17055', // Orange
  '#d63031', // Dunkelrot
  '#636e72', // Grau
  // Zusätzliche Farben
  '#00cec9', // Mint
  '#74b9ff', // Himmelblau
  '#a29bfe', // Lavendel
  '#fd79a8', // Rosa
  '#ffeaa7', // Hellgelb
  '#fab1a0', // Pfirsich
  '#81ecec', // Aqua
  '#55efc4', // Mintgrün
  '#ffeaa7', // Creme
  '#dfe6e9', // Hellgrau
  '#b2bec3', // Mittelgrau
  '#2d3436'  // Anthrazit
];
</script>

<style scoped>
.group-manager {
  padding: 16px;
}

.v-card {
  transition: transform 0.2s;
}

.v-card:hover {
  transform: translateY(-2px);
}

/* Group card styling */
.group-card {
  margin-bottom: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
  border-width: 4px !important;
}

.group-card.compact {
  min-height: auto;
}

.group-card .v-card-title {
  font-size: 1.1rem;
  font-weight: 500;
  padding: 8px 16px;
}

.group-card .v-card-text {
  padding-top: 0;
  padding-bottom: 8px;
}

.description {
  max-width: 70%;
}

/* Collapsible header styling */
.collapsible-header {
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background-color: rgba(0, 0, 0, 0.02);
}

.collapsible-header:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

/* Color selection */
.color-selection {
  margin-bottom: 16px;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 8px;
  padding: 8px;
  border-radius: 8px;
}

.color-btn {
  width: 40px !important;
  height: 40px !important;
  min-width: 40px !important;
  padding: 0 !important;
  border: 2px solid transparent !important;
}

.color-btn.selected {
  border: 2px solid #000 !important;
  transform: scale(1.1);
}

@media (max-width: 600px) {
  .color-grid {
    grid-template-columns: repeat(6, 1fr);
  }
}

@media (max-width: 400px) {
  .color-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style> 