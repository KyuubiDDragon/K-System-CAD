<template>
  <div class="report-field-manager">
    <h2>{{ t('admin.manageReportFields') }}</h2>
    
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <span>{{ t('admin.customFields') }}</span>
        <v-btn color="primary" @click="openAddDialog">
          <v-icon left>mdi-plus</v-icon> {{ t('admin.newField') }}
        </v-btn>
      </v-card-title>
      
      <v-card-text>
        <v-select
          v-model="selectedCategory"
          :items="categoryOptions"
          :label="t('admin.filterByCategory')"
          item-title="text"
          item-value="value"
          clearable
        ></v-select>
        
        <v-data-table
          :headers="headers"
          :items="filteredFields"
          :loading="loading"
          class="elevation-1"
         density="compact">
          <template v-slot:item.field_type="{ item }">
            {{ getFieldTypeLabel(item.field_type) }}
          </template>
          
          <template v-slot:item.category_id="{ item }">
            <span v-if="item.category_id">{{ getCategoryName(item.category_id) }}</span>
            <span v-else class="font-italic">{{ t('admin.general') }}</span>
          </template>
          
          <template v-slot:item.options="{ item }">
            <span v-if="item.options && item.options.length">
              {{ item.options.join(', ') }}
            </span>
            <span v-else>-</span>
          </template>

          <template v-slot:item.is_required="{ item }">
            <v-icon v-if="item.is_required" color="success">mdi-check</v-icon>
            <v-icon v-else color="grey">mdi-minus</v-icon>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-icon small class="mr-2" @click="editField(item)">mdi-pencil</v-icon>
            <v-icon small @click="confirmDelete(item)">mdi-delete</v-icon>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>
    
    <!-- Dialog zum Hinzufügen/Bearbeiten eines Feldes -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span>{{ isEditing ? t('admin.editField') : t('admin.addField') }}</span>
        </v-card-title>
        
        <v-card-text>
          <v-form ref="form" v-model="isFormValid">
            <v-container>
              <v-row>
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="currentField.category_id"
                    :items="categoryItems"
                    :label="t('admin.category')"
                    item-title="name"
                    item-value="id"
                    clearable
                    :hint="currentField.category_id ? t('admin.categorySpecificHint') : t('admin.generalFieldHint')"
                    persistent-hint
                  ></v-select>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="currentField.field_name"
                    :label="t('admin.fieldName')"
                    :rules="fieldNameRules"
                    :disabled="isEditing"
                    required
                    :hint="t('admin.fieldNameHint')"
                    persistent-hint
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model="currentField.field_label"
                    :label="t('admin.fieldLabel')"
                    :rules="[v => !!v || t('admin.fieldLabelRequired')]"
                    required
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="currentField.field_type"
                    :items="fieldTypeOptions"
                    :label="t('admin.fieldType')"
                    item-title="text"
                    item-value="value"
                    :rules="[v => !!v || t('admin.fieldTypeRequired')]"
                    required
                  ></v-select>
                </v-col>
                
                <v-col cols="12" v-if="hasOptions">
                  <v-textarea
                    v-model="optionsText"
                    :label="t('admin.options')"
                    rows="3"
                    :hint="t('admin.optionsHint')"
                    persistent-hint
                    :rules="[v => hasOptions ? !!v : true || t('admin.optionsRequired')]"
                  ></v-textarea>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model.number="currentField.sort_order"
                    :label="t('admin.sortOrder')"
                    type="number"
                  ></v-text-field>
                </v-col>
                
                <v-col cols="12" sm="6">
                  <v-switch
                    v-model="currentField.is_required"
                    :label="t('admin.requiredField')"
                    color="primary"
                  ></v-switch>
                </v-col>
              </v-row>
            </v-container>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" text @click="dialog = false">{{ t('admin.cancel') }}</v-btn>
          <v-btn color="primary" @click="saveField" :disabled="!isFormValid">{{ t('admin.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Dialog zum Löschen bestätigen -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">{{ t('admin.deleteFieldTitle') }}</v-card-title>
        <v-card-text>
          {{ t('admin.deleteFieldConfirm', { name: fieldToDelete?.field_label }) }}
          <p class="text-subtitle-1 mt-2">{{ t('admin.deleteWarning') }}</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" text @click="deleteDialog = false">{{ t('admin.cancel') }}</v-btn>
          <v-btn color="error" @click="deleteField">{{ t('admin.delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import ReportFieldsService from '@/services/ReportFieldsService';
import { useToast } from 'vue-toastification';
import { useI18n } from "vue-i18n";

export default {
  name: 'ReportFieldManager',
  
  setup() {
    const toast = useToast();
    const fields = ref([]);
    const { t } = useI18n();
    const categories = ref([]);
    const loading = ref(true);
    const dialog = ref(false);
    const deleteDialog = ref(false);
    const isEditing = ref(false);
    const isFormValid = ref(false);
    const form = ref(null);
    const fieldToDelete = ref(null);
    const selectedCategory = ref(null);
    const optionsText = ref('');
    
    const currentField = ref({
      field_name: '',
      field_label: '',
      field_type: 'text',
      category_id: null,
      options: [],
      sort_order: 0,
      is_required: false
    });
    
    const resetCurrentField = () => {
      currentField.value = {
        field_name: '',
        field_label: '',
        field_type: 'text',
        category_id: null,
        options: [],
        sort_order: 0,
        is_required: false
      };
      optionsText.value = '';
    };
    
    const fetchData = async () => {
      try {
        loading.value = true;
        const [fieldsData, categoriesData] = await Promise.all([
          ReportFieldsService.getReportFields(),
          ReportFieldsService.getReportCategories()
        ]);
        fields.value = fieldsData;
        categories.value = categoriesData;
      } catch (error) {
        toast.error(t('admin.errorLoadingData'));
        console.error(error);
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(() => {
      fetchData();
    });
    
    const headers = [
      { title: t('admin.headerFieldName'), key: 'field_name' },
      { title: t('admin.headerLabel'), key: 'field_label' },
      { title: t('admin.headerType'), key: 'field_type' },
      { title: t('admin.headerCategory'), key: 'category_id' },
      { title: t('admin.headerOptions'), key: 'options' },
      { title: t('admin.headerRequired'), key: 'is_required' },
      { title: t('admin.headerOrder'), key: 'sort_order', align: 'end' },
      { title: t('admin.headerActions'), key: 'actions', sortable: false }
    ];
    
    const fieldTypeOptions = [
      { text: t('admin.typeText'), value: 'text' },
      { text: t('admin.typeNumber'), value: 'number' },
      { text: t('admin.typeDate'), value: 'date' },
      { text: t('admin.typeBoolean'), value: 'boolean' },
      { text: t('admin.typeSelect'), value: 'select' },
      { text: t('admin.typeTextarea'), value: 'textarea' },
      { text: t('admin.typeMultiselect'), value: 'multiselect' }
    ];
    
    const categoryOptions = computed(() => {
      return [
        { text: t('admin.allFields'), value: null },
        { text: t('admin.generalFields'), value: 'general' },
        ...categories.value.map(cat => ({ text: cat.name, value: cat.id }))
      ];
    });
    
    const categoryItems = computed(() => {
      return [
        { id: null, name: t('admin.generalAllReports') },
        ...categories.value
      ];
    });
    
    const filteredFields = computed(() => {
      if (!selectedCategory.value) return fields.value;
      
      if (selectedCategory.value === 'general') {
        return fields.value.filter(field => field.category_id === null);
      }
      
      return fields.value.filter(field => field.category_id === selectedCategory.value);
    });
    
    const hasOptions = computed(() => {
      return ['select', 'multiselect'].includes(currentField.value.field_type);
    });
    
    watch(() => currentField.value.field_type, (newType) => {
      if (!['select', 'multiselect'].includes(newType)) {
        currentField.value.options = [];
        optionsText.value = '';
      }
    });
    
    const fieldNameRules = [
      v => !!v || t('admin.fieldNameRequired'),
      v => /^[a-zA-Z0-9_]+$/.test(v) || t('admin.fieldNamePattern')
    ];
    
    const getFieldTypeLabel = (type) => {
      const option = fieldTypeOptions.find(opt => opt.value === type);
      return option ? option.text : type;
    };
    
    const getCategoryName = (id) => {
      const category = categories.value.find(cat => cat.id === id);
      return category ? category.name : t('admin.unknown');
    };
    
    const parseOptions = () => {
      if (!optionsText.value) return [];
      
      // Versuchen, nach Zeilen zu trennen
      if (optionsText.value.includes('\n')) {
        return optionsText.value.split('\n')
          .map(opt => opt.trim())
          .filter(opt => opt);
      }
      
      // Ansonsten nach Kommas trennen
      return optionsText.value.split(',')
        .map(opt => opt.trim())
        .filter(opt => opt);
    };
    
    const formatOptions = (options) => {
      if (!options || !Array.isArray(options)) return '';
      return options.join('\n');
    };
    
    const openAddDialog = () => {
      resetCurrentField();
      isEditing.value = false;
      dialog.value = true;
      
      if (selectedCategory.value && selectedCategory.value !== 'general') {
        currentField.value.category_id = selectedCategory.value;
      }
    };
    
    const editField = (field) => {
      currentField.value = { ...field };
      optionsText.value = formatOptions(field.options);
      isEditing.value = true;
      dialog.value = true;
    };
    
    const confirmDelete = (field) => {
      fieldToDelete.value = field;
      deleteDialog.value = true;
    };
    
    const saveField = async () => {
      try {
        if (hasOptions.value) {
          currentField.value.options = parseOptions();
        }
        
        if (isEditing.value) {
          await ReportFieldsService.updateReportField(currentField.value);
          toast.success(t('admin.fieldUpdated'));
        } else {
          await ReportFieldsService.addReportField(currentField.value);
          toast.success(t('admin.fieldAdded'));
        }
        
        dialog.value = false;
        fetchData();
      } catch (error) {
        toast.error(`Fehler: ${error.response?.data?.error || error.message}`);
      }
    };
    
    const deleteField = async () => {
      try {
        await ReportFieldsService.deleteReportField(fieldToDelete.value.id);
        toast.success(t('admin.fieldDeleted'));
        deleteDialog.value = false;
        fetchData();
      } catch (error) {
        toast.error(`Fehler: ${error.response?.data?.error || error.message}`);
      }
    };
    
    return {
      fields,
      categories,
      loading,
      dialog,
      deleteDialog,
      currentField,
      isEditing,
      isFormValid,
      form,
      fieldToDelete,
      headers,
      fieldTypeOptions,
      selectedCategory,
      categoryOptions,
      categoryItems,
      filteredFields,
      hasOptions,
      optionsText,
      fieldNameRules,
      openAddDialog,
      editField,
      confirmDelete,
      saveField,
      deleteField,
      t,
      getFieldTypeLabel,
      getCategoryName
    };
  }
};
</script>

<style scoped>
.report-field-manager {
  padding: 16px;
}
</style> 