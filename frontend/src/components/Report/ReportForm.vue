<template>
  <v-form ref="form" v-model="valid">
    <!-- Allgemeine Berichtsfelder -->
    <v-text-field
      v-model="report.title"
      :label="t('reportForm.title')"
      required
      :rules="[v => !!v || t('reportForm.titleRequired')]"
    ></v-text-field>

    <v-select
      v-model="report.category_id"
      :items="categories"
      item-title="name"
      item-value="id"
      :label="t('reportForm.category')"
      required
      :rules="[v => !!v || t('reportForm.categoryRequired')]"
      @update:modelValue="loadCategoryFields"
    ></v-select>

    <!-- Weitere Standardfelder (status, priority, etc.) -->
    
    <!-- Benutzerdefinierte Felder dynamisch rendern -->
    <div v-if="customFields.length > 0" class="custom-fields-section">
      <h3 class="text-h6 mt-4 mb-2">{{ t('reportForm.additionalInfo') }}</h3>
      
      <div v-for="field in customFields" :key="field.field_name">
        <!-- Text-Feld -->
        <v-text-field
          v-if="field.field_type === 'text'"
          v-model="customFieldValues[field.field_name]"
          :label="field.field_label"
          :required="field.is_required"
          :rules="field.is_required ? [v => !!v || `${field.field_label} ist erforderlich`] : []"
        ></v-text-field>

        <!-- Zahl-Feld -->
        <v-text-field
          v-else-if="field.field_type === 'number'"
          v-model.number="customFieldValues[field.field_name]"
          :label="field.field_label"
          type="number"
          :required="field.is_required"
          :rules="field.is_required ? [v => !!v || `${field.field_label} ist erforderlich`] : []"
        ></v-text-field>

        <!-- Datum-Feld -->
        <v-date-picker
          v-else-if="field.field_type === 'date'"
          v-model="customFieldValues[field.field_name]"
          :label="field.field_label"
          :required="field.is_required"
          :rules="field.is_required ? [v => !!v || `${field.field_label} ist erforderlich`] : []"
        ></v-date-picker>

        <!-- Boolean-Feld (Checkbox) -->
        <v-checkbox
          v-else-if="field.field_type === 'boolean'"
          v-model="customFieldValues[field.field_name]"
          :label="field.field_label"
        ></v-checkbox>

        <!-- Select-Feld (Dropdown) -->
        <v-select
          v-else-if="field.field_type === 'select'"
          v-model="customFieldValues[field.field_name]"
          :items="field.options"
          :label="field.field_label"
          :required="field.is_required"
          :rules="field.is_required ? [v => !!v || `${field.field_label} ist erforderlich`] : []"
        ></v-select>

        <!-- Multiselect-Feld -->
        <v-select
          v-else-if="field.field_type === 'multiselect'"
          v-model="customFieldValues[field.field_name]"
          :items="field.options"
          :label="field.field_label"
          multiple
          chips
          :required="field.is_required"
          :rules="field.is_required ? [v => (v && v.length) || `${field.field_label} ist erforderlich`] : []"
        ></v-select>

        <!-- Textarea-Feld -->
        <v-textarea
          v-else-if="field.field_type === 'textarea'"
          v-model="customFieldValues[field.field_name]"
          :label="field.field_label"
          :required="field.is_required"
          :rules="field.is_required ? [v => !!v || `${field.field_label} ist erforderlich`] : []"
        ></v-textarea>
      </div>
    </div>

    <!-- Hauptinhalt des Berichts -->
    <v-textarea
      v-model="report.content"
      :label="t('reportForm.content')"
      rows="6"
      required
      :rules="[v => !!v || t('reportForm.contentRequired')]"
    ></v-textarea>

    <div class="d-flex justify-end mt-4">
      <v-btn color="primary" @click="submitForm" :disabled="!valid">
        {{ isEdit ? t('reportForm.update') : t('reportForm.create') }}
      </v-btn>
    </div>
  </v-form>
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ReportService from '@/services/ReportService';
import ReportFieldsService from '@/services/ReportFieldsService';
import { useSnackbar } from '@/composables/useSnackbar';

export default {
  name: 'ReportForm',
  
  props: {
    reportId: {
      type: [Number, String],
      default: null
    },
    initialData: {
      type: Object,
      default: () => ({})
    }
  },
  
  emits: ['saved', 'cancelled'],
  
  setup(props, { emit }) {
    const { showSnackbar } = useSnackbar();
    const { t } = useI18n();
    const form = ref(null);
    const valid = ref(false);
    const loading = ref(false);
    const categories = ref([]);
    const customFields = ref([]);
    const isEdit = computed(() => !!props.reportId);
    
    // Report Daten
    const report = reactive({
      title: '',
      content: '',
      category_id: null,
      status: 'open',
      priority: 'medium',
      due_date: null,
      assigned_to: null,
      tags: [],
      location: '',
      ...props.initialData
    });
    
    // Werte für benutzerdefinierte Felder
    const customFieldValues = reactive({});
    
    // Lade Kategorien
    const loadCategories = async () => {
      try {
        categories.value = await ReportFieldsService.getReportCategories();
      } catch (error) {
        showSnackbar(t('reportForm.loadCategoriesError'), 'error');
      }
    };
    
    // Lade benutzerdefinierte Felder für eine Kategorie
    const loadCategoryFields = async () => {
      if (!report.category_id) {
        customFields.value = [];
        return;
      }
      
      try {
        customFields.value = await ReportFieldsService.getFieldsByCategory(report.category_id);
        
        // Initialisiere Werte, wenn noch nicht vorhanden
        customFields.value.forEach(field => {
          if (!(field.field_name in customFieldValues)) {
            if (field.field_type === 'boolean') {
              customFieldValues[field.field_name] = false;
            } else if (field.field_type === 'multiselect') {
              customFieldValues[field.field_name] = [];
            } else {
              customFieldValues[field.field_name] = null;
            }
          }
        });
      } catch (error) {
        showSnackbar(t('reportForm.loadFieldsError'), 'error');
      }
    };
    
    // Lade vorhandenen Bericht und seine Felder
    const loadReport = async () => {
      if (!props.reportId) return;
      
      try {
        loading.value = true;
        const reportData = await ReportService.getReport(props.reportId);
        
        // Fülle Standardfelder
        Object.keys(report).forEach(key => {
          if (key in reportData) {
            report[key] = reportData[key];
          }
        });
        
        // Lade benutzerdefinierte Felder
        await loadCategoryFields();
        
        // Fülle benutzerdefinierte Feldwerte
        Object.keys(reportData).forEach(key => {
          if (customFields.value.some(field => field.field_name === key)) {
            customFieldValues[key] = reportData[key];
          }
        });
      } catch (error) {
        showSnackbar(t('reportForm.loadReportError'), 'error');
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(async () => {
      await loadCategories();
      if (isEdit.value) {
        await loadReport();
      } else if (report.category_id) {
        await loadCategoryFields();
      }
    });
    
    // Wenn die Kategorie sich ändert, lade die entsprechenden Felder
    watch(() => report.category_id, loadCategoryFields);
    
    // Formular absenden
    const submitForm = async () => {
      if (!form.value.validate()) return;
      
      try {
        loading.value = true;
        
        // Kombiniere Standardfelder und benutzerdefinierte Felder
        const reportData = { ...report };
        
        // Füge benutzerdefinierte Felder hinzu
        const customFieldsData = [];
        Object.keys(customFieldValues).forEach(key => {
          if (customFieldValues[key] !== null && customFieldValues[key] !== undefined) {
            customFieldsData.push({
              field_name: key,
              field_value: customFieldValues[key]
            });
          }
        });
        reportData.custom_fields = customFieldsData;
        
        let result;
        if (isEdit.value) {
          result = await ReportService.updateReport(props.reportId, reportData);
        } else {
          result = await ReportService.createReport(reportData);
        }
        
        showSnackbar(
          isEdit.value ? t('reportForm.savedUpdate') : t('reportForm.savedCreate'),
          'success'
        );
        
        emit('saved', result);
      } catch (error) {
        showSnackbar(
          t('reportForm.error', { msg: error.response?.data?.error || error.message }),
          'error'
        );
      } finally {
        loading.value = false;
      }
    };
    
    return {
      form,
      valid,
      report,
      categories,
      customFields,
      customFieldValues,
      isEdit,
      loading,
      submitForm
    };
  }
};
</script>

<style scoped>
.custom-fields-section {
  background-color: rgba(0, 0, 0, 0.02);
  padding: 16px;
  border-radius: 4px;
  margin: 16px 0;
}
</style> 