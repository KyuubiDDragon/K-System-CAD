<script setup lang="ts">
import { defineComponent, ref, watch, watchEffect, onBeforeUnmount, onMounted, defineAsyncComponent } from 'vue';
import { type Document, type Category, type ViewType, type DefaultView } from '@/types/Document';
import { type Employee } from '@/types/Members';
import TiptapEditor from '@/components/TiptapEditor.vue';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';
import { importXLSXToUniver, exportUniverToXLSX } from '@/utils/xlsxToUniver';

// Lazy load SpreadsheetEditor to reduce initial bundle size
const SpreadsheetEditor = defineAsyncComponent(
  () => import('@/components/document/SpreadsheetEditor.vue')
);

const props = defineProps<{
    selectedDocument: Document | null;
    selectedDocumentPreview: string | null;
    selectedDocumentEmployeeDocument: Employee | null;
    categories: Category[];
    areaId?: string | number;
    areaKey?: string;
}>();

const selectedCategory = ref<number>(1);
const title = ref('');
const content = ref('');
const notes = ref('');
const sort_order = ref(0);
const editorDialog = ref(false);
const showModal = ref(false);
const employeeToEditId = ref(-1);
const employeeToEditName = ref('');

// New fields for spreadsheet support
const viewType = ref<ViewType>('document');
const spreadsheetData = ref<any>(null);
const defaultView = ref<DefaultView>('document');
const currentView = ref<'document' | 'spreadsheet'>('document');

// Spreadsheet display options
const isDarkMode = ref(true); // Default to dark mode
const isFullscreen = ref(false);

// Fullscreen preview mode for documents
const isDocumentFullscreenPreview = ref(false);

// XLSX import/export
const fileInputRef = ref<HTMLInputElement | null>(null);

const emit = defineEmits<{
    (e: 'update:selectedDocument', payload: Document): void;
    (e: 'update:selectedDocumentEmployeeDocument', payload: Employee): void;
    (e: 'close-document'): void;
}>();

const toolbarRef = ref<HTMLElement | null>(null);

watch(
    () => props.selectedDocument,
    newVal => {
        editorDialog.value = newVal != null;
    }
);

watch(
    () => props.selectedDocumentPreview,
    newVal => {
        editorDialog.value = newVal != null;
    }
);

watch(
    () => props.selectedDocumentEmployeeDocument,
    newVal => {
        editorDialog.value = newVal != null;
    }
);

watch(
    () => props.categories,
    newCategories => {
        if (newCategories.length > 0) {
            selectedCategory.value = newCategories[0].id;
        }
    },
    { immediate: true }
);

const save = () => {
    if (props.selectedDocument) {
        emit('update:selectedDocument', {
            ...props.selectedDocument,
            category_id: selectedCategory.value,
            title: title.value,
            content: content.value,
            notes: notes.value,
            sort_order: sort_order.value,
            view_type: viewType.value,
            spreadsheet_data: spreadsheetData.value,
            default_view: defaultView.value,
        });
        closeEditor();
    } else if (props.selectedDocumentEmployeeDocument) {
        emit('update:selectedDocumentEmployeeDocument', {
            ...props.selectedDocumentEmployeeDocument,
            notes: content.value
        });
        closeEditor();
    }
};

watchEffect(() => {
    if (props.selectedDocument && props.selectedDocument.id != -1) {
        title.value = props.selectedDocument.title;
        content.value = props.selectedDocument.content;
        notes.value = props.selectedDocument.notes;
        sort_order.value = props.selectedDocument.sort_order;

        // Load new fields
        viewType.value = props.selectedDocument.view_type || 'document';
        spreadsheetData.value = props.selectedDocument.spreadsheet_data || null;
        defaultView.value = props.selectedDocument.default_view || 'document';
        currentView.value = defaultView.value;

        // Finde die Kategorie in props.categories, die der category_id entspricht
        const category = props.categories.find(
            category => category.id == props.selectedDocument?.category_id
        );
        if (category) {
            selectedCategory.value = category.id;
        }
        showModal.value = true;
        editorDialog.value = true;
    } else if (props.selectedDocumentPreview) {
        content.value = props.selectedDocumentPreview;
        showModal.value = true;
        editorDialog.value = true;
    } else if (props.selectedDocumentEmployeeDocument) {
        content.value = props.selectedDocumentEmployeeDocument.notes;
        employeeToEditId.value = props.selectedDocumentEmployeeDocument.id;
        employeeToEditName.value = props.selectedDocumentEmployeeDocument.name;
        showModal.value = true;
        editorDialog.value = true;
    } else if (props.selectedDocument && props.selectedDocument.id == -1) {
        title.value = '';
        content.value = '';
        notes.value = '';
        sort_order.value = 0;

        // Initialize new fields with defaults
        viewType.value = 'document';
        spreadsheetData.value = null;
        defaultView.value = 'document';
        currentView.value = 'document';

        // Aktualisiere selectedCategory nur, wenn props.categories definiert ist und mindestens ein Element enthält
        if (props.categories && props.categories.length > 0) {
            selectedCategory.value = props.categories[0].id;
        }
        showModal.value = true;
        editorDialog.value = true;
    } else {
        showModal.value = false;
        editorDialog.value = false;
    }
});

// Toggle between document and spreadsheet view
const toggleView = () => {
    if (viewType.value === 'both') {
        currentView.value = currentView.value === 'document' ? 'spreadsheet' : 'document';
    }
};

// ... setup function ...
const updateEditorContent = () => {
	if (props.selectedDocument) {
		content.value = props.selectedDocument.content;
	} else if (props.selectedDocumentPreview) {
		content.value = props.selectedDocumentPreview;
	} else if (props.selectedDocumentEmployeeDocument) {
		content.value = props.selectedDocumentEmployeeDocument.notes;
	} else {
		content.value = '';
	}
};

watch(
    () => props.selectedDocument,
    () => {
        updateEditorContent();
    }
);

const closeEditor = () => {
    editorDialog.value = false;
    emit('close-document');
};

// PDF Export Function
const exportToPDF = async () => {
    if (!content.value) {
        alert('Kein Inhalt zum Exportieren vorhanden.');
        return;
    }

    try {
        // Dynamically import html2pdf
        const html2pdfModule = await import('html2pdf.js');
        const html2pdf = html2pdfModule.default || html2pdfModule;

        // Create a temporary container for PDF content
        const pdfContainer = document.createElement('div');
        pdfContainer.style.width = '210mm'; // A4 width
        pdfContainer.style.maxWidth = '210mm';
        pdfContainer.style.padding = '20mm';
        pdfContainer.style.backgroundColor = '#ffffff';
        pdfContainer.style.color = '#000000';
        pdfContainer.style.fontFamily = 'Arial, sans-serif';
        pdfContainer.style.fontSize = '12pt';
        pdfContainer.style.lineHeight = '1.6';

        // Add title if present
        if (title.value) {
            const titleElement = document.createElement('h1');
            titleElement.textContent = title.value;
            titleElement.style.fontSize = '18pt';
            titleElement.style.marginBottom = '10mm';
            titleElement.style.color = '#000000';
            pdfContainer.appendChild(titleElement);
        }

        // Add content
        const contentElement = document.createElement('div');
        contentElement.innerHTML = content.value;

        // Adjust images and tables for PDF
        contentElement.querySelectorAll('img').forEach((img: HTMLImageElement) => {
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
        });

        contentElement.querySelectorAll('table').forEach((table: HTMLTableElement) => {
            table.style.width = '100%';
            table.style.maxWidth = '100%';
            table.style.fontSize = '10pt';
            table.style.borderCollapse = 'collapse';
        });

        contentElement.querySelectorAll('td, th').forEach((cell: HTMLElement) => {
            cell.style.padding = '5px';
            cell.style.border = '1px solid #000';
            cell.style.wordWrap = 'break-word';
        });

        // Ensure all content fits within page width
        contentElement.style.maxWidth = '100%';
        contentElement.style.overflow = 'hidden';
        contentElement.style.wordWrap = 'break-word';

        pdfContainer.appendChild(contentElement);

        // Add notes if present
        if (notes.value) {
            const notesElement = document.createElement('div');
            notesElement.style.marginTop = '15mm';
            notesElement.style.padding = '10px';
            notesElement.style.border = '1px solid #ccc';
            notesElement.style.backgroundColor = '#f9f9f9';

            const notesTitle = document.createElement('strong');
            notesTitle.textContent = 'Notizen:';
            notesElement.appendChild(notesTitle);

            const notesContent = document.createElement('p');
            notesContent.textContent = notes.value;
            notesContent.style.marginTop = '5px';
            notesElement.appendChild(notesContent);

            pdfContainer.appendChild(notesElement);
        }

        // PDF export options
        const opt = {
            margin: [10, 10, 10, 10],
            filename: `${title.value || 'document'}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                letterRendering: true,
                width: 794 // A4 width in pixels at 96 DPI (210mm)
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };

        // Generate PDF
        await html2pdf().set(opt).from(pdfContainer).save();

    } catch (error) {
        console.error('Error exporting PDF:', error);
        alert('Fehler beim Exportieren des PDFs. Bitte versuchen Sie es erneut.');
    }
};

// XLSX Import/Export Functions
const triggerImportXLSX = () => {
    fileInputRef.value?.click();
};

const handleFileImport = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    try {
        // Import XLSX file and convert to Univer format
        const univerData = await importXLSXToUniver(file);

        // Update spreadsheet data
        spreadsheetData.value = univerData;

        // If title is empty, use the filename
        if (!title.value) {
            title.value = file.name.replace('.xlsx', '').replace('.xls', '');
        }

        console.log('XLSX file imported successfully');
    } catch (error) {
        console.error('Error importing XLSX:', error);
        alert('Fehler beim Importieren der XLSX-Datei. Bitte stellen Sie sicher, dass es sich um eine gültige Excel-Datei handelt.');
    } finally {
        // Reset file input
        input.value = '';
    }
};

const handleExportXLSX = () => {
    if (!spreadsheetData.value) {
        alert('Keine Tabellendaten zum Exportieren vorhanden.');
        return;
    }

    try {
        const filename = `${title.value || 'spreadsheet'}.xlsx`;
        exportUniverToXLSX(spreadsheetData.value, filename);
        console.log('XLSX file exported successfully');
    } catch (error) {
        console.error('Error exporting XLSX:', error);
        alert('Fehler beim Exportieren der XLSX-Datei.');
    }
};

// Fullscreen preview functions
const openFullscreenPreview = () => {
    isDocumentFullscreenPreview.value = true;
    document.body.style.overflow = 'hidden';
};

const closeFullscreenPreview = () => {
    isDocumentFullscreenPreview.value = false;
    document.body.style.overflow = '';
};

// Close fullscreen preview on ESC key
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isDocumentFullscreenPreview.value) {
        closeFullscreenPreview();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>
<template>
    <div v-show="editorDialog" class="editor-overlay">
      <div class="editor-content">
        <!-- Standard Dokumenteneditor -->
        <v-card v-if="selectedDocument" class="editor-card">
          <v-toolbar dense class="editor-toolbar" ref="toolbarRef">
            <v-icon icon="mdi-file-document-edit" class="ml-4"></v-icon>
            <v-toolbar-title>{{ title || $t('documentEditor.newDocument') }}</v-toolbar-title>
            <v-spacer></v-spacer>

            <!-- Spreadsheet Controls (only show when spreadsheet is visible) -->
            <template v-if="viewType === 'spreadsheet' || (viewType === 'both' && currentView === 'spreadsheet')">
              <v-btn
                icon
                @click="triggerImportXLSX"
                variant="text"
                width="48"
                height="48"
                title="XLSX importieren"
              >
                <v-icon>mdi-file-excel-box</v-icon>
              </v-btn>
              <v-btn
                icon
                @click="handleExportXLSX"
                variant="text"
                width="48"
                height="48"
                title="Als XLSX exportieren"
              >
                <v-icon>mdi-download</v-icon>
              </v-btn>
              <v-divider vertical class="mx-2"></v-divider>
              <v-btn
                icon
                @click="isDarkMode = !isDarkMode"
                variant="text"
                width="48"
                height="48"
              >
                <v-icon>{{ isDarkMode ? 'mdi-white-balance-sunny' : 'mdi-weather-night' }}</v-icon>
              </v-btn>
              <v-btn
                icon
                @click="isFullscreen = !isFullscreen"
                variant="text"
                width="48"
                height="48"
              >
                <v-icon>{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
              </v-btn>
              <v-divider vertical class="mx-2"></v-divider>
            </template>

            <add-shortcut-button
              v-if="selectedDocument && selectedDocument.id"
              type="document"
              :resource-id="selectedDocument.id"
              :title="title || 'Unbenanntes Dokument'"
              :subtitle="categories.find(c => c.id === selectedCategory)?.name || undefined"
              icon="mdi-file-document"
              color="accent"
              :metadata="props.areaKey ? { areaKey: props.areaKey, areaId: props.areaId } : undefined"
            />
            <v-btn
              v-if="selectedDocument && (viewType === 'document' || viewType === 'both')"
              icon
              @click="openFullscreenPreview"
              variant="text"
              width="48"
              height="48"
              title="Vollbild-Vorschau"
            >
              <v-icon>mdi-eye</v-icon>
            </v-btn>
            <v-btn
              v-if="selectedDocument && (viewType === 'document' || viewType === 'both')"
              color="secondary"
              variant="elevated"
              prepend-icon="mdi-file-pdf-box"
              @click="exportToPDF"
              class="mx-2"
            >
              PDF
            </v-btn>
            <v-btn
              color="primary"
              variant="elevated"
              prepend-icon="mdi-content-save"
              @click="save"
              class="mx-2"
            >
              {{ $t('save') }}
            </v-btn>
            <v-btn icon="mdi-close" variant="text" @click="closeEditor">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-toolbar>
          
          <v-container class="pa-0 document-container">
            <v-card class="mb-6 document-settings-card" variant="outlined">
              <v-card-text class="pa-4">
                <v-row>
                  <v-col cols="12" sm="6" md="3">
                    <v-select
                      :items="categories"
                      item-title="name"
                      item-value="id"
                      v-model="selectedCategory"
                      :label="$t('category')"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      prepend-inner-icon="mdi-folder"
                    ></v-select>
                  </v-col>

                  <v-col cols="12" sm="6" md="3">
                    <v-text-field
                      v-model="title"
                      :label="$t('title')"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      prepend-inner-icon="mdi-format-title"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" sm="6" md="2">
                    <v-select
                      :items="[
                        { value: 'document', title: $t('documentEditor.documentOnly') },
                        { value: 'spreadsheet', title: $t('documentEditor.spreadsheetOnly') },
                        { value: 'both', title: $t('documentEditor.both') }
                      ]"
                      v-model="viewType"
                      :label="$t('documentEditor.viewType')"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      prepend-inner-icon="mdi-view-split-vertical"
                    ></v-select>
                  </v-col>

                  <!-- View Toggle Buttons (only show when viewType is 'both') -->
                  <v-col v-if="viewType === 'both'" cols="12" sm="6" md="1">
                    <div class="view-toggle-buttons">
                      <v-btn-toggle
                        v-model="currentView"
                        mandatory
                        color="primary"
                        density="comfortable"
                        divided
                      >
                        <v-btn value="document" width="48" height="48" icon>
                          <v-icon>mdi-file-document</v-icon>
                        </v-btn>
                        <v-btn value="spreadsheet" width="48" height="48" icon>
                          <v-icon>mdi-table-large</v-icon>
                        </v-btn>
                      </v-btn-toggle>
                    </div>
                  </v-col>

                  <v-col cols="12" sm="6" md="2">
                    <v-text-field
                      v-model="sort_order"
                      :label="$t('sortOrder')"
                      type="number"
                      variant="outlined"
                      density="comfortable"
                      color="primary"
                      prepend-inner-icon="mdi-sort"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
            
            <div class="editor-container">
              <!-- Document Editor (shown when viewType is 'document' or when viewType is 'both' and currentView is 'document') -->
              <template v-if="viewType === 'document' || (viewType === 'both' && currentView === 'document')">
                <div class="editor-label">
                  <v-icon icon="mdi-text-box-edit" size="small" class="ml-4"></v-icon>
                  {{ $t('documentEditor.content') }}
                </div>

                <TiptapEditor
                  v-model="content"
                  :show-character-count="false"
                  :show-source-button="true"
                  :show-table-of-contents="true"
                  :editable="true"
                  class="document-editor expanded-editor"
                />
              </template>

              <!-- Spreadsheet Editor (shown when viewType is 'spreadsheet' or when viewType is 'both' and currentView is 'spreadsheet') -->
              <template v-if="viewType === 'spreadsheet' || (viewType === 'both' && currentView === 'spreadsheet')">
                <div class="editor-label">
                  <v-icon icon="mdi-table-large" size="small" class="ml-4"></v-icon>
                  {{ $t('documentEditor.spreadsheetContent') }}
                </div>

                <SpreadsheetEditor
                  v-model="spreadsheetData"
                  :editable="true"
                  :title="title"
                  :dark-mode="isDarkMode"
                  :fullscreen="isFullscreen"
                  class="spreadsheet-editor expanded-editor"
                />
              </template>
            </div>
          </v-container>
        </v-card>

        <!-- Notes section moved to editor-content level (one level higher) -->
        <div v-if="selectedDocument" class="notes-section">
          <v-textarea
            v-model="notes"
            variant="outlined"
            color="primary"
            :label="$t('documentEditor.notes')"
            rows="3"
            auto-grow
            class="notes-textarea"
          ></v-textarea>
        </div>

        <!-- Vorschaumodus -->
        <v-card v-else-if="selectedDocumentPreview" class="editor-card">
          <v-toolbar dense class="editor-toolbar" ref="toolbarRef">
            <v-icon icon="mdi-eye" class="ml-4"></v-icon>
            <v-toolbar-title>{{ $t('documentEditor.preview') }}</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn icon="mdi-close" variant="text" @click="closeEditor">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-toolbar>

          <v-container class="pa-0 document-container">
            <div class="preview-container">
              <TiptapEditor
                v-model="content"
                :show-character-count="false"
                :show-source-button="true"
                :editable="false"
                class="preview-editor expanded-editor"
              />
            </div>
          </v-container>
        </v-card>
        
        <!-- Mitarbeiterdokument-Editor -->
        <v-card v-else-if="selectedDocumentEmployeeDocument" class="editor-card">
          <v-toolbar dense class="editor-toolbar" ref="toolbarRef">
            <v-icon icon="mdi-account-edit" class="ml-4"></v-icon>
            <v-toolbar-title>{{ employeeToEditName }}</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn 
              color="primary" 
              variant="elevated" 
              prepend-icon="mdi-content-save" 
              @click="save" 
              class="mx-2"
            >
              {{ $t('save') }}
            </v-btn>
            <v-btn icon="mdi-close" variant="text" @click="closeEditor">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-toolbar>

          <v-container class="pa-0 document-container">
            <div class="editor-container">
              <div class="editor-label">
                <v-icon icon="mdi-account-details" size="small" class="ml-4"></v-icon>
                {{ $t('documentEditor.employeeInfo') }}
              </div>

              <TiptapEditor
                v-model="content"
                :show-character-count="false"
                :show-source-button="true"
                :show-table-of-contents="true"
                :editable="true"
                class="document-editor expanded-editor"
              />
            </div>
          </v-container>
        </v-card>

        <!-- Notes section moved to editor-content level (one level higher) -->
        <div v-if="selectedDocumentEmployeeDocument" class="notes-section">
          <v-textarea
            v-model="notes"
            variant="outlined"
            color="primary"
            :label="$t('documentEditor.notes')"
            rows="3"
            auto-grow
            class="notes-textarea"
          ></v-textarea>
        </div>

        <!-- Hidden file input for XLSX import -->
        <input
          ref="fileInputRef"
          type="file"
          accept=".xlsx,.xls"
          @change="handleFileImport"
          style="display: none"
        />
      </div>
    </div>

    <!-- Fullscreen Preview Overlay -->
    <Teleport to="body">
      <div v-if="isDocumentFullscreenPreview" class="fullscreen-preview-overlay" @click.self="closeFullscreenPreview">
        <div class="fullscreen-preview-container">
          <div class="fullscreen-preview-header">
            <h2 class="fullscreen-preview-title">{{ title }}</h2>
            <div class="fullscreen-preview-actions">
              <v-btn
                icon
                variant="text"
                @click="exportToPDF"
                title="Als PDF exportieren"
              >
                <v-icon>mdi-file-pdf-box</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                @click="closeFullscreenPreview"
                title="Schließen (ESC)"
              >
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </div>
          </div>
          <div class="fullscreen-preview-content">
            <div class="fullscreen-document-view" v-html="content"></div>
          </div>
        </div>
      </div>
    </Teleport>
  </template>
  
  <style scoped>
  @import '../../scss/ckeditor-dark-theme.css';
  
  .editor-overlay {
    /* Inline container - no special styling needed */
    display: contents;
  }

  .editor-content {
    /* Direct passthrough - no special styling needed */
    display: contents;
  }

  .editor-card {
    /* Simplified card - let parent handle sizing */
    display: flex;
    flex-direction: column;
    height: calc(100dvh - 144px);
    max-height: calc(100dvh - 144px);
  }

  .document-container {
    /* height, max-height and min-height removed to prevent scrolling issues */
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    padding: 0 16px 120px 16px; /* Increased bottom padding to 120px to prevent 3 lines from being cut off */
  }
  
  .editor-toolbar {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover)) !important;
    color: var(--k-ink);
    position: sticky;
    top: 0;
    z-index: 10;
  }
  
  .document-settings-card {
    background: var(--k-sunken) !important;
    border: 1px solid var(--k-line);
  }
  
  .editor-container {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden; /* Prevent container from growing beyond bounds */
  }
  
  .editor-label {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--k-ink);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
  }

  /* Notes section - separated from editor container */
  .notes-section {
    margin-top: 16px;
    padding: 0 16px;
    flex-shrink: 0;
  }

  .notes-textarea {
    width: 100%;
  }

  .view-toggle-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .view-toggle-label {
    font-size: 0.75rem;
    color: var(--k-ink-muted);
    margin-bottom: 4px;
  }

  .display-options-buttons {
    display: flex;
    gap: 8px;
  }

  .document-editor {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    background-color: var(--k-sunken);
  }
  
  /* Make the editor fill available space */
  .expanded-editor {
    width: 100%;
    flex: 1;
    min-height: 0; /* Important for flex children to allow shrinking */
  }
  
  .preview-container {
    background-color: var(--k-sunken);
    border-radius: 8px;
    border: 1px solid var(--k-line);
    padding: 20px;
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .preview-editor {
    height: 100%;
    width: 100%;
    flex: 1;
  }
  
  /* Verhindert horizontale und vertikale Scrollbars in Tabellenzellen (CKEditor) für Chrome */
  .ck.ck-content table td,
  .ck.ck-content table th {
    overflow: hidden;
  }
  
  /* Behält die vertikale Scrollbar bei, falls erforderlich */
  .ck.ck-content table td > *,
  .ck.ck-content table th > * {
    resize: none;
  }
  
  .ck-editor__editable {
    resize: vertical;
    overflow: auto;
    min-height: 800px; /* Double the default height from 500px to 800px */
    padding: 30px;
    background-color: var(--k-sunken) !important;
    color: var(--k-ink) !important;
    width: 100% !important; /* Ensure full width */
  }
  
  /* Add visual clue for resizing */
  .document-editor::after,
  .preview-container::after {
    content: '';
    position: absolute;
    right: 0;
    bottom: 0;
    width: 10px;
    height: 10px;
    background: linear-gradient(135deg, transparent 50%, rgba(255, 255, 255, 0.3) 50%);
    cursor: nwse-resize;
    pointer-events: none;
  }
  
  /* Animation */
  .editor-card {
    animation: slideIn 0.3s ease-out forwards;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  @media (max-width: 768px) {
    .editor-overlay {
      padding: 10px;
    }

    .editor-content {
      width: 100%;
      margin: 10px 0;
    }

    .ck-editor__editable {
      min-height: 300px;
    }
  }
  </style>

  <!-- Unscoped styles for Teleport content -->
  <style>
  .fullscreen-preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .fullscreen-preview-container {
    width: 100%;
    height: 100%;
    max-width: 900px;
    max-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--k-surface);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  }

  .fullscreen-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: #f8fafc;
    border-bottom: 1px solid var(--k-line);
    flex-shrink: 0;
  }

  .fullscreen-preview-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    margin-right: 16px;
  }

  .fullscreen-preview-actions {
    display: flex;
    gap: 8px;
  }

  .fullscreen-preview-actions .v-btn {
    color: var(--k-ink-muted);
  }

  .fullscreen-preview-actions .v-btn:hover {
    color: #1e293b;
    background: rgba(0, 0, 0, 0.05);
  }

  .fullscreen-preview-content {
    flex: 1;
    overflow-y: auto;
    padding: 40px;
    background: var(--k-surface);
  }

  .fullscreen-document-view {
    max-width: 100%;
    color: #1e293b;
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 16px;
    line-height: 1.8;
  }

  .fullscreen-document-view h1,
  .fullscreen-document-view h2,
  .fullscreen-document-view h3,
  .fullscreen-document-view h4,
  .fullscreen-document-view h5,
  .fullscreen-document-view h6 {
    font-family: 'Arial', sans-serif;
    color: var(--k-ink);
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    line-height: 1.3;
  }

  .fullscreen-document-view h1 {
    font-size: 2em;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.3em;
  }

  .fullscreen-document-view h2 {
    font-size: 1.5em;
    border-bottom: 1px solid var(--k-line);
    padding-bottom: 0.2em;
  }

  .fullscreen-document-view h3 {
    font-size: 1.25em;
  }

  .fullscreen-document-view p {
    margin-bottom: 1em;
  }

  .fullscreen-document-view img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1em 0;
  }

  .fullscreen-document-view table {
    width: 100%;
    border-collapse: collapse;
    margin: 1em 0;
  }

  .fullscreen-document-view table th,
  .fullscreen-document-view table td {
    border: 1px solid #cbd5e1;
    padding: 12px;
    text-align: left;
  }

  .fullscreen-document-view table th {
    background: #f1f5f9;
    font-weight: 600;
  }

  .fullscreen-document-view table tr:nth-child(even) {
    background: #f8fafc;
  }

  .fullscreen-document-view ul,
  .fullscreen-document-view ol {
    margin: 1em 0;
    padding-left: 2em;
  }

  .fullscreen-document-view li {
    margin-bottom: 0.5em;
  }

  .fullscreen-document-view blockquote {
    border-left: 4px solid var(--k-accent);
    margin: 1em 0;
    padding: 0.5em 1em;
    background: #f1f5f9;
    color: #475569;
    font-style: italic;
  }

  .fullscreen-document-view pre {
    background: var(--k-sunken);
    color: var(--k-ink);
    padding: 1em;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1em 0;
  }

  .fullscreen-document-view code {
    font-family: 'Consolas', 'Monaco', monospace;
    background: #f1f5f9;
    padding: 0.2em 0.4em;
    border-radius: 4px;
    font-size: 0.9em;
  }

  .fullscreen-document-view pre code {
    background: transparent;
    padding: 0;
  }

  .fullscreen-document-view a {
    color: var(--k-accent);
    text-decoration: none;
  }

  .fullscreen-document-view a:hover {
    text-decoration: underline;
  }

  .fullscreen-document-view hr {
    border: none;
    border-top: 1px solid var(--k-line);
    margin: 2em 0;
  }

  @media (max-width: 768px) {
    .fullscreen-preview-container {
      max-width: 100%;
      border-radius: 0;
    }

    .fullscreen-preview-content {
      padding: 20px;
    }

    .fullscreen-document-view {
      font-size: 14px;
    }
  }
  </style>