<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, reactive, nextTick } from 'vue';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import { useI18n } from 'vue-i18n';
import html2canvas from 'html2canvas';
import { useToast } from 'vue-toastification'; // Import toast

// --- Import Static Assets ---
import pdfTopImage from '@/assets/pdf_top.png'; // Adjust path as needed
import logoImage from '@/assets/logo.png'; // Adjust path as needed
import pdfBottomImage from '@/assets/pdf_bottom.png'; // Adjust path as needed

// --- Store ---
const authStore = useAuthStore();
const toast = useToast(); // Create toast instance
const { t } = useI18n();

// Component mounted state
const isMounted = ref(true);

// --- Interfaces ---
interface UploadedFile {
    name: string;
    link: string;
    // Add other properties if available
}

// --- Component State ---
const formRef = ref<any>(null); // For v-form
const isFormValid = ref(false);
const isLoading = ref(false); // Loading state for image generation/upload
const loadingFiles = ref(false);

// Form Inputs
const issuedOn = ref<string>(new Date().toISOString().substring(0, 10)); // Default to today
const performedFrom = ref<string>('Fire Department'); // Default value
const objectFireProtection = ref<string>('');
const location = ref<string>('');
const creator = ref<string>('');
const office = ref<string>('Fire Protection Bureau'); // Default value
const signatureUrl = ref<string>(''); // Model for the input field, prefilled from store
const reportNumber = ref<string>('');
const selectedType = ref<string>('Unternehmen'); // Default type
const validFrom = ref<string>(''); // Only for 'Event'
const validTo = ref<string>(''); // Only for 'Event'
const typeOptions: string[] = ['Unternehmen', 'Behörde', 'Event'];

// Result Display
const shareableLink = ref<string>('');
const shareableLinkFileName = ref<string>('');
const uploadedFiles = ref<UploadedFile[]>([]);

// --- Table Headers ---
const uploadedFilesHeaders = computed(() => [
    { title: t('fireprotection.headers.fileName'), key: 'name', sortable: false },
    { title: t('fireprotection.headers.link'), key: 'link', sortable: false },
]);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || 'Feld ist erforderlich.';

// --- Computed Properties ---
const validityDateDisplay = computed(() => {
    /* ... Implementation unchanged ... */
});
const typeLabel = computed(() => (selectedType.value === 'Event' ? 'Objekt | Event' : 'Objekt'));
const validityLabel = computed(() =>
    selectedType.value === 'Event' ? 'Gültig von - bis' : 'Gültig bis'
);
const validityText = computed(() => {
    /* ... Implementation unchanged ... */
});
const updateText = computed(() => {
    /* ... Implementation unchanged ... */
});
const reportNumberDisplay = computed(() => {
    /* ... Implementation unchanged ... */
});
const shareableLinkTag = computed(
    () => `<img alt="" src="${shareableLink.value}" style="height:849px; width:600px" />`
);
const mailTemplate = computed(() => {
    const subject = `Brandschutzbestätigung für ${objectFireProtection.value}`;
    const body = `Sehr geehrte Damen und Herren,

anbei übersende ich Ihnen die Brandschutzbestätigung für ${objectFireProtection.value}.

${shareableLink.value ? `Link zur Bestätigung: ${shareableLink.value}` : ''}

Mit freundlichen Grüßen
${creator.value}
${office.value}`;

    return `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
});

// --- Toast Helper ---
function showSnackbarHelper(
    message: string,
    color: 'success' | 'error' | 'info' | 'warning' = 'info'
) {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Methods ---

const fetchLastFiles = async () => {
    loadingFiles.value = true;
    try {
        const response = await apiClientAuth.post<{ data: UploadedFile[] }>(
            'fireprotection/?action=getLastFiles'
        ); // Adjust path/method if needed
        uploadedFiles.value = response.data.data || response.data || [];
    } catch (error: any) {
        console.error('Error fetching last files:', error);
        toast.error(
            error.response?.data?.error || t('toast.loadLastFilesError')
        );
    } finally {
        loadingFiles.value = false;
    }
};

const loadSignatureImage = (src: string): Promise<HTMLImageElement> => {
    return new Promise(resolve => {
        if (!src) {
            resolve(new Image());
            return;
        }
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = () => resolve(img);
        img.onerror = err => {
            console.error('Error loading signature image:', src, err);
            resolve(new Image());
        };
        img.src = src;
    });
};

const generateImage = async (): Promise<Blob | null> => {
    if (!isMounted.value) {
        console.warn('Component unmounted, skipping image generation');
        return null;
    }

    const a4Page = document.getElementById('a4-page');
    if (!a4Page) {
        console.warn('a4-page element not found');
        if (isMounted.value) {
            toast.error(t('toast.previewElementNotFound'));
        }
        return null;
    }

    // Check if element is still in DOM and has parentNode
    if (!a4Page.parentNode || !document.body.contains(a4Page)) {
        console.warn('a4-page element not in DOM or has no parentNode');
        return null;
    }

    await loadSignatureImage(signatureUrl.value);

    if (!isMounted.value) {
        console.warn('Component unmounted during signature loading');
        return null;
    }

    // Double-check element is still valid before html2canvas
    if (!a4Page.parentNode || !document.body.contains(a4Page)) {
        console.warn('a4-page element removed from DOM before canvas generation');
        return null;
    }

    try {
        const canvas = await html2canvas(a4Page, {
            useCORS: true,
            scale: 2,
            logging: false,
            windowWidth: a4Page.scrollWidth,
            windowHeight: a4Page.scrollHeight
        });

        if (!isMounted.value) {
            console.warn('Component unmounted during canvas generation');
            return null;
        }

        return await new Promise<Blob | null>(resolve => canvas.toBlob(resolve, 'image/png', 1.0));
    } catch (error) {
        console.error('Error generating canvas:', error);
        if (isMounted.value) {
            toast.error(t('toast.imageCreateError'));
        }
        return null;
    }
};

const sendImageToServer = async (imageData: Blob, filename: string) => {
    if (!imageData) return;
    isLoading.value = true;
    const currentYear = issuedOn.value
        ? new Date(issuedOn.value).getFullYear().toString()
        : new Date().getFullYear().toString();
    const isEvent = selectedType.value === 'Event';

    const formData = new FormData();
    formData.append('image', imageData, filename);
    formData.append('isEvent', String(isEvent));
    formData.append('year', currentYear);
    formData.append('issuedOn', issuedOn.value);
    formData.append('performedFrom', performedFrom.value);
    formData.append('objectFireProtection', objectFireProtection.value);
    formData.append('location', location.value);
    formData.append('creator', creator.value);
    formData.append('office', office.value);
    formData.append('reportNumber', reportNumber.value);
    formData.append('selectedType', selectedType.value);
    if (isEvent) {
        if (validFrom.value) formData.append('validFrom', validFrom.value);
        if (validTo.value) formData.append('validTo', validTo.value);
    }

    try {
        const response = await apiClientAuth.post<{
            status: string;
            fileName: string;
            shareableLink: string;
            message?: string;
        }>('fireprotection?action=submitDocument', formData);
        if (response.data.status === 'success') {
            shareableLinkFileName.value = response.data.fileName;
            shareableLink.value = response.data.shareableLink;
            toast.success(t('toast.documentUploadSuccess'));
            await fetchLastFiles();
        } else {
            toast.error(response.data.message || t('toast.uploadError'));
        }
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('toast.uploadFailed'));
    } finally {
        isLoading.value = false;
    }
};

const submit = async () => {
    const { valid } = await formRef.value?.validate();
    if (!valid) {
        showSnackbarHelper(t('toast.requiredFieldsError'), 'warning');
        return;
    }
    isLoading.value = true;
    const imageData = await generateImage();
    if (imageData) {
        const timestamp = Date.now();
        const filenameBase =
            replaceSpacesWithUnderscores(objectFireProtection.value) || 'Zertifikat';
        const filenameDate = formatDate(issuedOn.value, false, true) || 'DatumUnbekannt';
        const filename = `${filenameBase}_${filenameDate}_${timestamp}.png`;
        await sendImageToServer(imageData, filename);
    } else {
        isLoading.value = false;
    }
};

const downloadImage = async () => {
    if (!shareableLink.value) return;
    try {
        const response = await fetch(shareableLink.value);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = shareableLinkFileName.value || 'zertifikat.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading image:', error);
        showSnackbarHelper(t('toast.downloadImageError'), 'error');
    }
};

const copyToClipboard = async (
    text: string | null,
    successMessage: string = t('toast.copySuccess')
) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        showSnackbarHelper(successMessage, 'success');
    } catch (err) {
        console.error('Failed to copy: ', err);
        showSnackbarHelper(t('toast.copyFailed'), 'error');
    }
};

const copyMail = () => {
    copyToClipboard(mailTemplate.value, t('toast.copyMailSuccess'));
};

// --- Utility Functions ---
const replaceSpacesWithUnderscores = (str: string): string => (str ? str.replace(/\s+/g, '_') : '');

const formatDateDisplay = (dateString?: string | null): string => {
    if (!dateString) return '-';
    // Assume input is YYYY-MM-DD, format to DD.MM.YYYY
    const parts = dateString.split('-');
    if (parts.length === 3) return `${parts[2]}.${parts[1]}.${parts[0]}`;
    return dateString; // Fallback
};

const formatDate = (
    dateInput: string | Date | null,
    getYear = false,
    withoutDots = false
): string => {
    if (!dateInput) return '';
    try {
        const date = new Date(dateInput instanceof Date ? dateInput : dateInput.split('T')[0]);
        if (isNaN(date.getTime())) return '';
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        if (getYear) return String(year);
        if (withoutDots) return `${day}${month}${year}`;
        return `${day}.${month}.${year}`;
    } catch {
        return '';
    }
};

const addMonthsToDate = (dateInput: string | null, numberOfMonths: number): string => {
    if (!dateInput) return '-';
    try {
        const inputDate = new Date(dateInput);
        if (isNaN(inputDate.getTime())) return '-';
        const resultDate = new Date(inputDate);
        resultDate.setMonth(resultDate.getMonth() + numberOfMonths);
        if (resultDate.getDate() < inputDate.getDate()) {
            resultDate.setDate(0);
        }
        return formatDate(resultDate); // Format as DD.MM.YYYY
    } catch {
        return '-';
    }
};

const getInitials = (value: string): string => {
    if (!value) return 'XX';
    // Improved initials: handle multiple spaces, take first and last word's first letter
    const words = value.trim().split(/\s+/);
    if (words.length === 0 || words[0] === '') return 'XX';
    if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
    return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
};

    // --- Lifecycle Hooks ---
onMounted(() => {
    fetchLastFiles();
    signatureUrl.value = authStore.user?.signature || '';
    creator.value = authStore.user?.username || '';
    // Initial form validation check
    nextTick(() => {
        formRef.value?.validate();
    });
});

// Cleanup before component unmounts
onBeforeUnmount(() => {
    isMounted.value = false;
    console.log('FireprotectionView: Component unmounting, canceling any pending operations');
});
</script>

<template>
    <v-container fluid>
        <v-row>
            <v-col cols="12" md="5" lg="4">
                <v-card class="pa-4" elevation="2" theme="dark">
                    <v-card-title class="text-h6 mb-3">{{ t('fireprotectionView.formTitle') }}</v-card-title>
                    <v-form ref="formRef" v-model="isFormValid">
                        <v-select
                            v-model="selectedType"
                            :items="typeOptions"
                            :label="t('fireprotectionView.type')"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-select>
                        <v-text-field
                            v-model="issuedOn"
                            :label="t('fireprotectionView.issuedOn')"
                            type="date"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="performedFrom"
                            :label="t('fireprotectionView.performedFrom')"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="creator"
                            :label="t('fireprotectionView.creator')"
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="office"
                            :label="t('fireprotectionView.office')"
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="signatureUrl"
                            :label="t('fireprotectionView.signatureUrl')"
                            hint="URL zum Bild der Unterschrift (optional, Standard aus Profil)"
                            persistent-hint
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="objectFireProtection"
                            :label="t('fireprotectionView.objectName')"
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="location"
                            :label="t('fireprotectionView.location')"
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-text-field
                            v-model="reportNumber"
                            :label="t('fireprotectionView.reportNumber')"
                            :rules="[requiredRule]"
                            variant="outlined"
                            density="compact"
                            class="mb-4"
                        ></v-text-field>
                        <v-row v-if="selectedType === 'Event'" dense>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="validFrom"
                                    :label="t('fireprotectionView.validFrom')"
                                    type="date"
                                    :rules="[requiredRule]"
                                    variant="outlined"
                                    density="compact"
                                    class="mb-sm-0"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="validTo"
                                    :label="t('fireprotectionView.validTo')"
                                    type="date"
                                    :rules="[requiredRule]"
                                    variant="outlined"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-btn
                            @click="submit"
                            :loading="isLoading"
                            :disabled="!isFormValid"
                            color="primary"
                            block
                            size="large"
                            class="mt-4 action-button"
                            prepend-icon="mdi-file-image-plus-outline"
                        >
                            {{ t('fireprotectionView.generateUpload') }}
                        </v-btn>

                        <div
                            v-if="shareableLink"
                            class="mt-6 pa-3 border rounded bg-grey-darken-3"
                        >
                            <div class="text-subtitle-1 mb-2">{{ t('fireprotectionView.generatedLink') }}</div>
                            <p class="text-caption">Dateiname: {{ shareableLinkFileName }}</p>
                            <v-text-field
                                :model-value="
                                    '<img alt=&quot;&quot; src=&quot;' +
                                    shareableLink +
                                    '&quot; style=&quot;height:849px; width:600px&quot; />'
                                "
                                readonly
                                variant="outlined"
                                density="compact"
                                class="mt-2"
                                append-inner-icon="mdi-content-copy"
                                @click:append-inner="
                                    copyToClipboard(shareableLinkTag, t('toast.copyHtmlTagSuccess'))
                                "
                                hide-details
                                bg-color="grey-darken-3"
                            >
                                <template v-slot:label
                                    >HTML Tag
                                    <span class="text-caption">(zum Kopieren)</span></template
                                >
                            </v-text-field>
                            <v-text-field
                                :model-value="shareableLink"
                                readonly
                                variant="outlined"
                                density="compact"
                                class="mt-2"
                                append-inner-icon="mdi-content-copy"
                                @click:append-inner="
                                    copyToClipboard(shareableLink, t('toast.copyLinkSuccess'))
                                "
                                hide-details
                                bg-color="grey-darken-3"
                            >
                                <template v-slot:label>
                                    {{ t('fireprotectionView.directLink') }}
                                    <span class="text-caption">(zum Kopieren)</span>
                                </template>
                            </v-text-field>
                            <div class="mt-3">
                                <v-btn
                                    @click="downloadImage"
                                    size="small"
                                    variant="tonal"
                                    prepend-icon="mdi-download"
                                    class="mr-2 action-button"
                                    >{{ t('fireprotectionView.downloadImage') }}</v-btn
                                >
                                <v-btn
                                    @click="copyMail"
                                    size="small"
                                    variant="tonal"
                                    prepend-icon="mdi-email-fast-outline"
                                    class="action-button"
                                    >{{ t('fireprotectionView.copyMail') }}</v-btn
                                >
                            </div>
                        </div>
                    </v-form>
                </v-card>

                <v-card class="mt-5 pa-0" elevation="2" theme="dark">
                    <v-toolbar density="compact" flat color="grey-darken-3" class="card-toolbar">
                        <v-toolbar-title class="text-subtitle-1">
                            {{ t('fireprotectionView.lastCertificates') }}
                        </v-toolbar-title>
                    </v-toolbar>
                    <v-divider></v-divider>
                    <v-data-table
                        :headers="uploadedFilesHeaders"
                        :items="uploadedFiles"
                        density="compact"
                        :items-per-page="5"
                        :loading="loadingFiles"
                        class="custom-table-no-footer"
                        hover
                        item-value="name"
                    >
                        <template v-slot:[`item.link`]="{ item }">
                            <v-btn
                                :href="item.link"
                                target="_blank"
                                variant="text"
                                size="small"
                                color="primary"
                                prepend-icon="mdi-open-in-new"
                                class="action-button"
                                >{{ t('open') }}</v-btn
                            >
                        </template>
                        <template v-slot:no-data>
                            <div class="empty-state">
                                <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-file-document-outline</v-icon>
                                <span>{{ t('fireprotectionView.noFiles') }}</span>
                            </div>
                        </template>
                        <template v-slot:loading>
                            <div class="loading-state">
                                <v-progress-circular indeterminate color="primary" size="24" class="mr-2"></v-progress-circular>
                                <span>{{ t('fireprotectionView.loadingFiles') }}</span>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>

            <v-col cols="12" md="7" lg="8">
                <div class="preview-container">
                    <div id="a4-page" class="fireprotection-doc-background elevation-5">
                        <div class="header-section">
                            <div class="fireprotection-doc-header-text">
                                Los Santos Fire Department
                            </div>
                            <img :src="pdfTopImage" class="header-image" alt="Header" />
                            <div class="fireprotection-doc-header-subtext">
                                Ausgestellt durch das Los Santos Fire Department
                            </div>
                        </div>

                        <div class="watermark-container">
                            <img :src="logoImage" class="watermark-logo" alt="Logo Wasserzeichen" />
                        </div>

                        <div class="content-section">
                            <v-container class="pa-0">
                                <v-row class="fireprotection-doc-table font-weight-bold">
                                    <v-col cols="6" class="text-right">Ausgestellt am:</v-col>
                                    <v-col cols="6" class="text-left">{{
                                        formatDateDisplay(issuedOn)
                                    }}</v-col>
                                    <v-col cols="6" class="text-right">Durchgeführt von:</v-col>
                                    <v-col cols="6" class="text-left">{{
                                        performedFrom || '-'
                                    }}</v-col>
                                    <v-col cols="6" class="text-right">{{ typeLabel }}:</v-col>
                                    <v-col cols="6" class="text-left">{{
                                        objectFireProtection || '-'
                                    }}</v-col>
                                    <v-col cols="6" class="text-right">Standort:</v-col>
                                    <v-col cols="6" class="text-left">{{ location || '-' }}</v-col>
                                    <v-col cols="6" class="text-right">Gutachten-Nr:</v-col>
                                    <v-col cols="6" class="text-left">{{
                                        reportNumberDisplay
                                    }}</v-col>
                                    <v-col cols="6" class="text-right">{{ validityLabel }}:</v-col>
                                    <v-col cols="6" class="text-left">{{
                                        validityDateDisplay
                                    }}</v-col>
                                </v-row>

                                <div class="main-text">
                                    <p>Brandschutzmaßnahmen wurden vorgenommen und bestätigt.</p>
                                    <p class="pt-1">Keine Mängel festgestellt.</p>
                                    <p class="font-weight-bold highlight-text">
                                        Der Brandschutz ist gewährleistet.
                                    </p>
                                    <p class="fine-print pt-2">{{ validityText }}</p>
                                    <p class="fine-print pt-1">{{ updateText }}</p>
                                </div>

                                <div class="signature-section">
                                    <p>Mit freundlichen Grüßen</p>
                                    <p class="font-weight-bold signature-name">
                                        {{ creator || '-' }}
                                    </p>
                                    <p class="signature-office">{{ office || '-' }}</p>
                                    <img
                                        v-if="signatureUrl"
                                        :src="signatureUrl"
                                        class="signature-image"
                                        alt="Unterschrift"
                                    />
                                    <div v-else class="signature-placeholder">
                                        [ Digitale Signatur ]
                                    </div>
                                </div>
                            </v-container>
                        </div>

                        <div class="footer-section">
                            <img :src="pdfBottomImage" class="footer-image" alt="Footer" />
                            <div class="fireprotection-doc-footer-text">
                                Brandschutzdienststelle des LSFD
                            </div>
                        </div>
                    </div>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped>  
/* Base Styles */
.preview-container {
    display: flex;
    justify-content: center;
    padding: 20px;
    background-color: #e0e0e0;
    overflow-y: auto;
    height: calc(100vh - 100px);
}

/* Preview Document */
#a4-page {
    width: 210mm;
    min-height: 297mm;
    height: max-content;
    margin: 0;
    border: 1px solid #ccc;
    background-color: #fff9ed;
    color: black;
    display: flex;
    flex-direction: column;
    font-family: 'PT Serif', serif;
    position: relative;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Header and Footer Sections */
.header-section,
.footer-section {
    width: 100%;
    text-align: center;
    flex-shrink: 0;
}

.header-image,
.footer-image {
    width: 100%;
    height: auto;
    display: block;
}

.fireprotection-doc-header-text {
    font-size: 35px;
    font-weight: bold;
    margin-top: 10px;
    margin-bottom: 5px;
}

.fireprotection-doc-header-subtext {
    font-size: 11px;
    padding-top: 10px;
}

.fireprotection-doc-footer-text {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 8px;
    margin-top: 4px;
}

/* Watermark Styling */
.watermark-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 0;
    overflow: hidden;
}

.watermark-logo {
    height: auto;
    width: 500px;
    opacity: 0.08;
    user-select: none;
    pointer-events: none;
}

/* Content Section */
.content-section {
    flex-grow: 1;
    padding: 20mm 20mm 5mm 20mm;
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Document Table and Text */
.fireprotection-doc-table {
    font-size: 16px;
}

.fireprotection-doc-table > .v-col {
    padding: 4px 8px;
}

.text-right {
    text-align: right;
}

.text-left {
    text-align: left;
}

.main-text {
    text-align: center;
    margin-top: 30mm;
    flex-grow: 1;
}

.main-text p {
    margin-bottom: 2mm;
}

.highlight-text {
    margin-top: 10mm;
    margin-bottom: 10mm;
    font-size: 1.1em;
}

.fine-print {
    font-size: 11px;
    margin-top: 3mm;
}

/* Signature Section */
.signature-section {
    text-align: center;
    margin-top: 15mm;
    flex-shrink: 0;
}

.signature-name {
    margin-top: 3mm;
    margin-bottom: 0;
}

.signature-office {
    margin-top: 1mm;
    margin-bottom: 6mm;
    font-size: 0.9em;
}

.signature-image {
    height: 54px;
    width: auto;
    max-width: 200px;
    margin-left: auto;
    margin-right: auto;
    display: block;
}

.signature-placeholder {
    height: 54px;
    line-height: 54px;
    font-style: italic;
    color: #aaa;
}

/* Table Footer Styling */
.custom-table-no-footer :deep(.v-data-table-footer) {
    display: none;
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

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

/* Loading & Empty States */
.loading-state, .empty-state {
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

/* Responsive Adjustments */
@media (max-width: 960px) {
    .preview-container {
        height: auto;
    }
    
    #a4-page {
        width: 100%;
    }
}
</style>