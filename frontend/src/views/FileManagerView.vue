<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute } from "vue-router";
import { useToast } from "vue-toastification";
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import type { Folder, File } from "@/types/FileManager"; // Adjust path if needed

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false
});

// Check permissions from both route.meta and props
const route = useRoute();
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Component State ---
const search = ref("");
const folders = ref<Folder[]>([]);
const files = ref<File[]>([]);
const uploadedFiles = ref<any[]>([]);
const currentFolderId = ref<number | null>(null);
const folderHistory = ref<(number | null)[]>([]);
const currentFolderName = ref<string | null>(null);
const loadingFiles = ref(false);
const viewMode = ref("grid"); // Add missing viewMode ref with default value "grid"

// Add breadcrumb navigation tracking
const folderBreadcrumbs = ref<Array<{ id: number | null; name: string }>>([
	{ id: null, name: "Root" },
]);

// --- Dialog States ---
const showFolderDialog = ref(false);
const newFolderName = ref("");

// --- Allowed File Types ---
const allowedFileTypes = ref(
	"image/*,application/pdf,application/msword,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/vnd.adobe.photoshop"
);

// --- Toastification ---
const toast = useToast();
const { t } = useI18n();

// --- Computed Properties ---
const filteredFiles = computed(() => {
	return files.value.filter((file) =>
		file.name.toLowerCase().includes(search.value.toLowerCase())
	);
});

const filteredFolders = computed(() => {
	return folders.value.filter((folder) =>
		folder.name.toLowerCase().includes(search.value.toLowerCase())
	);
});

// --- Data Fetching ---
const fetchFilesAndFolders = async () => {
	loadingFiles.value = true;

	try {
		const response = await apiClientAuth.post(
			"filemanager/?action=getFilesAndFolders",
			{
				folderId: currentFolderId.value,
			}
		);

		folders.value = response.data.folders || [];
		files.value = response.data.files || [];
		currentFolderName.value = response.data.currentFolderName || "Root";
	} catch (error: any) {
		console.error("Error fetching files and folders:", error);
		toast.error(
			error.response?.data?.error ||
				"Fehler beim Laden der Dateien und Ordner."
		);
		folders.value = [];
		files.value = [];
	} finally {
		loadingFiles.value = false;
	}
};

// --- Folder Navigation ---
const navigateToFolder = (folderId: number) => {
	folderHistory.value.push(currentFolderId.value);
	currentFolderId.value = folderId;

	// Add the new folder to breadcrumbs
	const folder = folders.value.find((f: Folder) => f.id === folderId);
	if (folder) {
		folderBreadcrumbs.value.push({
			id: folder.id,
			name: folder.name,
		});
	}

	fetchFilesAndFolders();
};

const goBack = () => {
	if (folderHistory.value.length > 0) {
		currentFolderId.value = folderHistory.value.pop() || null;

		// Remove the last breadcrumb item
		if (folderBreadcrumbs.value.length > 1) {
			folderBreadcrumbs.value.pop();
		}

		fetchFilesAndFolders();
	}
};

// New direct navigation function
const navigateToBreadcrumb = (index: number) => {
	if (index >= 0 && index < folderBreadcrumbs.value.length) {
		// Get the target folder
		const target = folderBreadcrumbs.value[index];

		// If clicking on the current folder, do nothing
		if (target.id === currentFolderId.value) return;

		// If going back to a previous folder
		folderHistory.value.push(currentFolderId.value);
		currentFolderId.value = target.id;

		// Update breadcrumbs - remove all after index
		folderBreadcrumbs.value = folderBreadcrumbs.value.slice(0, index + 1);

		fetchFilesAndFolders();
	}
};

// Reset all navigation when component is mounted or reset
const resetNavigation = () => {
	currentFolderId.value = null;
	folderHistory.value = [];
	folderBreadcrumbs.value = [{ id: null, name: "Root" }];
	fetchFilesAndFolders();
};

// --- File Operations ---
const uploadFiles = async () => {
	if (uploadedFiles.value.length <= 0) return;

	const formData = new FormData();
	uploadedFiles.value.forEach((file) => {
		formData.append("files[]", file);
	});
	formData.append(
		"folderId",
		currentFolderId.value ? String(currentFolderId.value) : ""
	);

	try {
		await apiClientAuth.post("/filemanager/?action=uploadFiles", formData, {
			headers: { "Content-Type": "multipart/form-data" },
		});
		uploadedFiles.value = [];
		fetchFilesAndFolders();
		toast.success("Dateien erfolgreich hochgeladen.");
	} catch (error: any) {
		console.error("Error uploading files:", error);
		toast.error(
			error.response?.data?.error || "Fehler beim Hochladen der Dateien."
		);
	}
};

const downloadFile = (path: string) => {
	window.open(path, "_blank");
};

const copyLink = (path: string) => {
	navigator.clipboard
		.writeText(path)
		.then(() => {
			toast.success("Link wurde in die Zwischenablage kopiert!");
		})
		.catch((error) => {
			console.error("Error copying link:", error);
			toast.error("Fehler beim Kopieren des Links.");
		});
};

const deleteFile = async (id: number) => {
        if (!confirm(t('fileManager.deleteFileConfirm'))) return;

	try {
		await apiClientAuth.post("/filemanager/?action=deleteFile", { id });
		fetchFilesAndFolders();
		toast.success("Datei erfolgreich gelöscht.");
	} catch (error: any) {
		console.error("Error deleting file:", error);
		toast.error(
			error.response?.data?.error || "Fehler beim Löschen der Datei."
		);
	}
};

// --- Folder Operations ---
const openFolderDialog = () => {
	showFolderDialog.value = true;
	newFolderName.value = "";
};

const createFolder = async () => {
	if (!newFolderName.value.trim()) return;

	try {
		await apiClientAuth.post("/filemanager/?action=createFolder", {
			name: newFolderName.value,
			parentId: currentFolderId.value,
		});
		showFolderDialog.value = false;
		newFolderName.value = "";
		fetchFilesAndFolders();
		toast.success("Ordner erfolgreich erstellt.");
	} catch (error: any) {
		console.error("Error creating folder:", error);
		toast.error(
			error.response?.data?.error || "Fehler beim Erstellen des Ordners."
		);
	}
};

// --- Utility Functions ---
const formatFileSize = (size: number): string => {
	if (size < 1024) return `${size} B`;
	if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
	if (size < 1024 * 1024 * 1024)
		return `${(size / (1024 * 1024)).toFixed(1)} MB`;
	return `${(size / (1024 * 1024 * 1024)).toFixed(1)} GB`;
};

const isImage = (path: string): boolean => {
	return /\.(jpg|jpeg|png|gif|bmp)$/i.test(path);
};

const getFileIcon = (fileName: string): string => {
	const extension = fileName.split(".").pop()?.toLowerCase() || "";

	if (/pdf/i.test(extension)) return "mdi-file-pdf-box";
	if (/docx?/i.test(extension)) return "mdi-file-word-box";
	if (/xlsx?/i.test(extension)) return "mdi-file-excel-box";
	if (/pptx?/i.test(extension)) return "mdi-file-powerpoint-box";
	if (/txt/i.test(extension)) return "mdi-file-document-outline";
	if (/zip|rar|7z/i.test(extension)) return "mdi-zip-box-outline";
	if (/psd/i.test(extension)) return "mdi-file-image-box";
	if (/jpe?g|png|gif|bmp|svg/i.test(extension)) return "mdi-file-image-box";

	return "mdi-file-outline";
};

const getFileColorClass = (fileName: string): string => {
	const extension = fileName.split(".").pop()?.toLowerCase() || "";

	if (/pdf/i.test(extension)) return "file-pdf";
	if (/docx?/i.test(extension)) return "file-doc";
	if (/xlsx?|csv/i.test(extension)) return "file-xls";
	if (/jpe?g|png|gif|bmp|svg|psd/i.test(extension)) return "file-img";

	return "file-other";
};

const getFileTypeColor = (fileName: string): string => {
	const extension = fileName.split(".").pop()?.toLowerCase() || "";

	if (/pdf/i.test(extension)) return "error";
	if (/docx?/i.test(extension)) return "primary";
	if (/xlsx?|csv/i.test(extension)) return "success";
	if (/jpe?g|png|gif|bmp|svg|psd/i.test(extension)) return "purple";

	return "grey";
};

const getFileType = (fileName: string): string => {
	const extension = fileName.split(".").pop()?.toLowerCase() || "Unbekannt";
	const typeMap: { [key: string]: string } = {
		pdf: "PDF",
		jpg: "JPG",
		jpeg: "JPEG",
		png: "PNG",
		gif: "GIF",
		bmp: "BMP",
		doc: "Word",
		docx: "Word",
		xls: "Excel",
		xlsx: "Excel",
		txt: "Text",
		zip: "ZIP",
		rar: "RAR",
		psd: "PSD",
		ppt: "PPT",
		pptx: "PPT",
	};

	return typeMap[extension] || extension.toUpperCase();
};

// --- Lifecycle Hooks ---
onMounted(() => {
	resetNavigation(); // Initialize with breadcrumbs
});
</script>

<template>
	<div class="file-manager-container">
		<v-container fluid class="pa-4">
			<!-- Header mit Suchfeld und Ordner-Pfad -->
			<div class="section-header mb-5">
				<div class="d-flex align-center header-title">
					<v-icon
						icon="mdi-folder-multiple"
						size="28"
						class="mr-3 text-primary header-icon"
					></v-icon>
                                        <h1 class="text-h5 font-weight-medium mb-0">
                                                {{ $t('tabs.fileManager') }}
                                        </h1>
				</div>

				<div class="header-actions">
                                        <v-text-field
                                                v-model="search"
                                                :label="$t('fileManager.searchFiles')"
						prepend-inner-icon="mdi-magnify"
						variant="outlined"
						density="compact"
						hide-details
						clearable
						
						color="primary"
						class="search-field"
					></v-text-field>
				</div>
			</div>

			<!-- Aktionsleiste mit Navigation und Datei-Upload -->
			<v-card class="action-bar mb-5" variant="outlined">
				<v-card-text class="py-3 px-4">
					<!-- Navigation und aktuelle Position -->
					<div class="path-navigator d-flex align-center flex-wrap">
						<v-btn
							variant="tonal"
							size="small"
							:disabled="!currentFolderId"
							@click="goBack"
							class="mr-2 mb-2 mb-md-0"
							color="blue-grey"
						>
                                                        <v-icon start>mdi-arrow-left</v-icon>
                                                        {{ $t('fileManager.back') }}
						</v-btn>

						<div
							class="folder-path d-flex align-center flex-grow-1 flex-wrap"
						>
							<v-icon class="mx-2 text-grey"
								>mdi-chevron-right</v-icon
							>
							<v-chip
								v-for="(breadcrumb, index) in folderBreadcrumbs"
								:key="index"
								class="breadcrumb-chip mb-2 mb-md-0 ml-2"
								color="primary"
								variant="flat"
								@click="navigateToBreadcrumb(index)"
							>
								<v-icon start size="small"
									>mdi-folder-open</v-icon
								>
								{{ breadcrumb.name }}
							</v-chip>
						</div>

						<!-- Aktions-Buttons für berechtigte Benutzer -->
						<div
							class="actions-group d-flex align-center flex-wrap"
							v-if="canEdit"
						>
							<v-divider
								vertical
								class="mx-3 d-none d-md-block"
							></v-divider>

                                                        <v-btn
                                                                v-if="canCreate"
                                                                color="primary"
                                                                variant="tonal"
                                                                size="small"
                                                                prepend-icon="mdi-folder-plus"
                                                                @click="openFolderDialog"
                                                                class="mr-2 mb-2 mb-md-0"
                                                        >
                                                                {{ $t('fileManager.createFolder') }}
                                                        </v-btn>

                                                        <v-file-input
                                                                v-model="uploadedFiles"
                                                                multiple
                                                                :label="$t('fileManager.selectFiles')"
								:accept="allowedFileTypes"
								variant="outlined"
								density="compact"
								hide-details
								
								color="primary"
								class="file-input mr-2 mb-2 mb-md-0"
								prepend-icon=""
								append-inner-icon="mdi-upload"
							></v-file-input>

                                                        <v-btn
                                                                color="primary"
                                                                :disabled="!uploadedFiles.length"
                                                                @click="uploadFiles"
                                                                variant="elevated"
                                                                size="small"
                                                                class="mb-2 mb-md-0"
                                                        >
                                                                {{ $t('fileManager.upload') }}
                                                        </v-btn>
						</div>
					</div>
				</v-card-text>
			</v-card>

			<!-- Inhaltsbereich mit Ladezustand -->
			<div class="content-wrapper">
				<v-fade-transition>
					<div
						v-if="loadingFiles"
						class="loading-overlay d-flex flex-column align-center justify-center"
					>
						<v-progress-circular
							indeterminate
							color="primary"
							size="64"
						></v-progress-circular>
                                                <span class="mt-4 text-medium-emphasis"
                                                        >{{ $t('fileManager.loadingItems') }}</span
                                                >
					</div>
				</v-fade-transition>

				<!-- Haupt-Content-Bereich -->
				<v-card variant="outlined" class="content-area">
					<!-- Leerstandsanzeige -->
					<v-card-text
						v-if="!filteredFolders.length && !filteredFiles.length"
						class="empty-state pa-8"
					>
						<div class="d-flex flex-column align-center">
							<v-icon
								icon="mdi-folder-search"
								size="64"
								color="grey-darken-1"
								class="mb-4"
							></v-icon>
                                                        <span class="text-h6 text-grey-darken-1"
                                                                >{{ $t('fileManager.noItems') }}</span
                                                        >
                                                        <span class="text-body-2 text-grey-darken-3 mt-2">
                                                                {{ $t('fileManager.noItemsHint') }}
                                                        </span>

                                                        <v-btn
                                                                v-if="canEdit"
                                                                color="primary"
                                                                variant="tonal"
                                                                class="mt-5"
                                                                prepend-icon="mdi-folder-plus"
                                                                @click="openFolderDialog"
                                                        >
                                                                {{ $t('fileManager.newFolder') }}
                                                        </v-btn>
						</div>
					</v-card-text>

					<v-card-text v-else class="pb-0">
						<!-- Ordner-Bereich -->
						<div v-if="filteredFolders.length" class="mb-6">
							<div class="section-title d-flex align-center mb-3">
								<v-icon
									icon="mdi-folder-multiple"
									size="small"
									class="mr-2"
								></v-icon>
                                                                <span class="text-subtitle-1">{{ $t('fileManager.folders') }}</span>
								<v-chip
									size="x-small"
									class="ml-2"
									color="primary"
									variant="flat"
									>{{ filteredFolders.length }}</v-chip
								>
							</div>

							<v-row>
								<v-col
									v-for="folder in filteredFolders"
									:key="folder.id"
									cols="12"
									sm="6"
									md="4"
									lg="3"
									xl="2"
									class="d-flex"
								>
									<v-hover v-slot="{ isHovering, props }">
										<v-card
											v-bind="props"
											class="folder-card flex-grow-1"
											@click="navigateToFolder(folder.id)"
											:elevation="isHovering ? 8 : 1"
										>
											<div
												class="folder-card-content d-flex flex-column align-center justify-center py-5"
											>
												<v-icon
													size="64"
													class="folder-icon mb-3"
													:color="
														isHovering
															? 'primary'
															: undefined
													"
												>
													{{
														isHovering
															? "mdi-folder-open"
															: "mdi-folder"
													}}
												</v-icon>
												<div
													class="folder-name truncate px-3 text-center"
												>
													{{ folder.name }}
												</div>
											</div>

											<v-fade-transition>
												<div
													v-if="isHovering"
													class="folder-hover-hint"
												>
													<v-icon
														icon="mdi-chevron-right"
														class="folder-hover-icon"
													></v-icon>
												</div>
											</v-fade-transition>
										</v-card>
									</v-hover>
								</v-col>
							</v-row>
						</div>

						<!-- Dateien-Bereich mit Grid/Liste Toggle -->
						<div v-if="filteredFiles.length" class="mb-6">
							<div
								class="section-title-with-options d-flex align-center flex-wrap mb-3"
							>
								<div class="d-flex align-center">
									<v-icon
										icon="mdi-file-multiple"
										size="small"
										class="mr-2"
									></v-icon>
                                                                        <span class="text-subtitle-1">{{ $t('fileManager.files') }}</span>
									<v-chip
										size="x-small"
										class="ml-2"
										color="primary"
										variant="flat"
										>{{ filteredFiles.length }}</v-chip
									>
								</div>

								<v-spacer></v-spacer>

								<div class="view-toggle">
									<v-btn-toggle
										v-model="viewMode"
										color="primary"
										density="comfortable"
										rounded="pill"
									>
										<v-btn
											value="grid"
											icon="mdi-view-grid"
										></v-btn>
										<v-btn
											value="list"
											icon="mdi-view-list"
										></v-btn>
									</v-btn-toggle>
								</div>
							</div>

							<!-- Grid View -->
							<v-row v-if="viewMode === 'grid'">
								<v-col
									v-for="file in filteredFiles"
									:key="file.id"
									cols="12"
									sm="6"
									md="4"
									lg="3"
									xl="2"
									class="d-flex"
								>
									<v-hover v-slot="{ isHovering, props }">
										<v-card
											v-bind="props"
											class="file-card flex-grow-1"
											:elevation="isHovering ? 8 : 1"
										>
											<div
												class="file-preview d-flex align-center justify-center position-relative"
											>
												<v-img
													v-if="isImage(file.path)"
													:src="file.path"
													class="file-image"
													height="160"
													cover
												></v-img>
												<div
													v-else
													class="file-icon-wrapper d-flex align-center justify-center"
													:class="
														getFileColorClass(
															file.name
														)
													"
												>
													<v-icon size="48">{{
														getFileIcon(file.name)
													}}</v-icon>
												</div>

												<v-fade-transition>
													<div
														v-if="isHovering"
														class="file-overlay"
													>
														<div
															class="file-preview-actions d-flex flex-column gap-2"
														>
															<v-btn
																variant="text"
																color="white"
																class="preview-button"
																prepend-icon="mdi-eye"
																@click.stop="
																	downloadFile(
																		file.path
																	)
																"
                                                                               >
                                                                               {{ $t('fileManager.open') }}
                                                                               </v-btn>
															<div
																class="d-flex justify-center"
															>
																<v-btn
																	icon="mdi-download"
																	size="small"
																	variant="tonal"
																	class="mx-1"
																	@click.stop="
																		downloadFile(
																			file.path
																		)
																	"
																></v-btn>
																<v-btn
																	icon="mdi-content-copy"
																	size="small"
																	variant="tonal"
																	class="mx-1"
																	@click.stop="
																		copyLink(
																			file.path
																		)
																	"
																></v-btn>
																<v-btn
																	v-if="
																		canDelete
																	"
																	icon="mdi-delete"
																	size="small"
																	variant="tonal"
																	color="error"
																	class="mx-1"
																	@click.stop="
																		deleteFile(
																			file.id
																		)
																	"
																></v-btn>
															</div>
														</div>
													</div>
												</v-fade-transition>
											</div>

											<v-card-text class="pa-3">
												<div
													class="file-name truncate text-subtitle-2"
												>
													{{ file.name }}
												</div>
												<div
													class="d-flex align-center justify-space-between mt-2"
												>
													<v-chip
														size="x-small"
														variant="flat"
														:color="
															getFileTypeColor(
																file.name
															)
														"
													>
														{{
															getFileType(
																file.name
															)
														}}
													</v-chip>
													<span
														class="text-caption text-grey"
														>{{
															formatFileSize(
																file.size
															)
														}}</span
													>
												</div>
											</v-card-text>
										</v-card>
									</v-hover>
								</v-col>
							</v-row>

							<!-- List View -->
							<v-card
								v-else
								variant="flat"
								class="file-list-container"
							>
								<v-list lines="two">
									<v-list-item
										v-for="file in filteredFiles"
										:key="file.id"
										class="file-list-item"
									>
										<template v-slot:prepend>
											<div
												class="list-icon-container"
												:class="
													getFileColorClass(file.name)
												"
											>
												<v-icon
													:icon="
														getFileIcon(file.name)
													"
													color="white"
												></v-icon>
											</div>
										</template>

										<v-list-item-title
											class="text-subtitle-2"
											>{{ file.name }}</v-list-item-title
										>
										<v-list-item-subtitle
											class="d-flex align-center mt-1"
										>
											<v-chip
												size="x-small"
												variant="flat"
												:color="
													getFileTypeColor(file.name)
												"
												class="mr-2"
											>
												{{ getFileType(file.name) }}
											</v-chip>
											<span class="text-caption">{{
												formatFileSize(file.size)
											}}</span>
										</v-list-item-subtitle>

										<template v-slot:append>
											<div class="list-actions">
												<v-btn
													icon="mdi-eye"
													size="small"
													variant="text"
													class="mx-1"
													@click.stop="
														downloadFile(file.path)
													"
												></v-btn>
												<v-btn
													icon="mdi-download"
													size="small"
													variant="text"
													class="mx-1"
													@click.stop="
														downloadFile(file.path)
													"
												></v-btn>
												<v-btn
													icon="mdi-content-copy"
													size="small"
													variant="text"
													class="mx-1"
													@click.stop="
														copyLink(file.path)
													"
												></v-btn>
												<v-btn
													v-if="canDelete"
													icon="mdi-delete"
													size="small"
													variant="text"
													color="error"
													class="mx-1"
													@click.stop="
														deleteFile(file.id)
													"
												></v-btn>
											</div>
										</template>
									</v-list-item>
								</v-list>
							</v-card>
						</div>
					</v-card-text>
				</v-card>
			</div>

			<!-- Ordner erstellen Dialog -->
			<v-dialog v-model="showFolderDialog" width="500" persistent>
				<v-card class="dialog-card">
					<v-toolbar
						color="primary"
						class="dialog-toolbar"
						density="compact"
					>
                                                <v-toolbar-title class="text-subtitle-1">
                                                        <v-icon
                                                                icon="mdi-folder-plus"
                                                                class="mr-2"
                                                                size="small"
                                                        ></v-icon>
                                                        {{ $t('fileManager.newFolder') }}
                                                </v-toolbar-title>
						<v-spacer></v-spacer>
						<v-btn
							icon="mdi-close"
							@click="showFolderDialog = false"
							size="small"
						></v-btn>
					</v-toolbar>

					<v-card-text class="pt-4">
                                                <v-text-field
                                                        :label="$t('fileManager.folderName')"
							v-model="newFolderName"
							variant="outlined"
							density="compact"
							
							color="primary"
							autofocus
							hide-details
                                                        :placeholder="$t('fileManager.folderPlaceholder')"
						></v-text-field>
					</v-card-text>

					<v-card-actions class="pa-4 pt-0">
						<v-spacer></v-spacer>
                                                <v-btn
                                                        variant="text"
                                                        @click="showFolderDialog = false"
                                                        class="mr-2"
                                                        >{{ $t('cancel') }}</v-btn
                                                >
                                                <v-btn
                                                        v-if="canCreate"
                                                        color="primary"
                                                        variant="elevated"
                                                        @click="createFolder"
                                                        :disabled="!newFolderName"
                                                        >{{ $t('create') }}</v-btn
                                                >
					</v-card-actions>
				</v-card>
			</v-dialog>
		</v-container>
	</div>
</template>

<style scoped>
.file-manager-container {
	min-height: 90vh;
	background-color: var(--k-canvas);
	position: relative;
}

/* Header Styles */
.section-header {
	display: flex;
	align-items: center;
	padding-bottom: 20px;
	border-bottom: 1px solid var(--k-line);
}

.header-title {
	animation: fadeIn 0.5s ease-out;
}

.header-icon {
	filter: drop-shadow(0 2px 6px var(--k-accent-line));
}

.header-actions {
	margin-left: auto;
}

.search-field {
	min-width: 280px;
	transition: all 0.3s ease;
}

.search-field:focus-within {
	box-shadow: 0 0 0 1px var(--k-accent-line);
}

/* Action Bar */
.action-bar {
	background: var(--k-surface) !important;
	border: 1px solid var(--k-line);
	backdrop-filter: blur(8px);
	border-radius: 12px;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	animation: fadeIn 0.5s ease-out;
	animation-delay: 0.1s;
}

.folder-path {
	background: var(--k-sunken);
	padding: 4px 8px;
	border-radius: 8px;
}

.file-input {
	max-width: 220px;
}

.current-folder-chip {
	border-radius: 20px;
	font-weight: 500;
}

.breadcrumb-chip {
	cursor: pointer;
	transition: background-color 0.2s ease;
}

.breadcrumb-chip:hover {
	background-color: var(--k-accent-weak);
}

/* Loading State */
.content-wrapper {
	position: relative;
}

.loading-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: var(--k-surface);
	z-index: 10;
	border-radius: 12px;
	backdrop-filter: blur(4px);
}

/* Content Area */
.content-area {
	background: var(--k-surface) !important;
	border: 1px solid var(--k-line);
	backdrop-filter: blur(8px);
	border-radius: 12px;
	min-height: 400px;
	animation: fadeIn 0.5s ease-out;
	animation-delay: 0.2s;
}

.section-title,
.section-title-with-options {
	color: var(--k-ink-muted);
	margin: 8px 8px 12px;
	padding-bottom: 8px;
	border-bottom: 1px solid var(--k-line);
}

.view-toggle {
	margin-right: 8px;
}

/* Folder Cards */
.folder-card {
	position: relative;
	cursor: pointer;
	border: 1px solid var(--k-line);
	border-radius: 12px;
	background: var(--k-sunken) !important;
	transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
	height: 100%;
	min-height: 160px;
	overflow: hidden;
}

.folder-card:hover {
	transform: translateY(-6px);
	background: var(--k-sunken) !important;
	border-color: var(--k-accent-line);
}

.folder-card-content {
	height: 100%;
}

.folder-name {
	font-weight: 500;
	font-size: 0.95rem;
	transition: color 0.2s ease;
}

.folder-card:hover .folder-name {
	color: var(--k-accent);
}

.folder-icon {
	filter: drop-shadow(0 4px 3px rgba(0, 0, 0, 0.2));
	transition: all 0.3s ease;
}

.folder-hover-hint {
	position: absolute;
	bottom: 12px;
	right: 12px;
	width: 26px;
	height: 26px;
	border-radius: 50%;
	background: var(--k-accent-weak);
	display: flex;
	align-items: center;
	justify-content: center;
}

.folder-hover-icon {
	color: var(--k-accent);
	font-size: 18px;
	animation: pulse 1.5s infinite;
}

/* File Cards */
.file-card {
	position: relative;
	cursor: pointer;
	border: 1px solid var(--k-line);
	border-radius: 12px;
	background: var(--k-sunken) !important;
	transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
	height: 100%;
	overflow: hidden;
}

.file-card:hover {
	transform: translateY(-6px);
	background: var(--k-sunken) !important;
	border-color: var(--k-accent-line);
}

.file-preview {
	height: 160px;
	background: var(--k-surface);
	overflow: hidden;
}

.file-image {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.file-icon-wrapper {
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 16px;
}

.file-icon-wrapper.file-pdf {
	background: linear-gradient(
		135deg,
		rgba(239, 68, 68, 0.1),
		rgba(239, 68, 68, 0.2)
	);
}

.file-icon-wrapper.file-doc {
	background: linear-gradient(
		135deg,
		var(--k-accent-weak),
		var(--k-accent-weak)
	);
}

.file-icon-wrapper.file-xls {
	background: linear-gradient(
		135deg,
		rgba(16, 185, 129, 0.1),
		rgba(16, 185, 129, 0.2)
	);
}

.file-icon-wrapper.file-img {
	background: linear-gradient(
		135deg,
		rgba(139, 92, 246, 0.1),
		rgba(139, 92, 246, 0.2)
	);
}

.file-icon-wrapper.file-other {
	background: linear-gradient(
		135deg,
		rgba(100, 116, 139, 0.1),
		rgba(100, 116, 139, 0.2)
	);
}

.file-name {
	font-weight: 500;
	font-size: 0.9rem;
}

.file-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: var(--k-surface);
	display: flex;
	align-items: center;
	justify-content: center;
}

.file-preview-actions {
	width: 100%;
	padding: 0 16px;
}

.preview-button {
	width: 100%;
	border-radius: 8px;
	margin-bottom: 8px;
	background: var(--k-accent-weak);
	backdrop-filter: blur(4px);
	border: 1px solid var(--k-accent-line);
}

.preview-button:hover {
	background: var(--k-accent-line);
}

/* List View Styles */
.file-list-container {
	background: transparent !important;
	border: 1px solid var(--k-line);
	border-radius: 12px;
	overflow: hidden;
}

.file-list-item {
	border-bottom: 1px solid var(--k-line);
	transition: background-color 0.2s ease;
}

.file-list-item:hover {
	background: var(--k-sunken) !important;
}

.file-list-item:last-child {
	border-bottom: none;
}

.list-icon-container {
	width: 38px;
	height: 38px;
	border-radius: 8px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.list-icon-container.file-pdf {
	background: linear-gradient(135deg, #f43f5e, #ef4444);
}

.list-icon-container.file-doc {
	background: linear-gradient(135deg, var(--k-accent), var(--k-accent-hover));
}

.list-icon-container.file-xls {
	background: linear-gradient(135deg, #10b981, #059669);
}

.list-icon-container.file-img {
	background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.list-icon-container.file-other {
	background: linear-gradient(135deg, #64748b, #475569);
}

.list-actions {
	display: flex;
	align-items: center;
}

/* Dialog Styling */
.dialog-card {
	background: var(--k-canvas) !important;
	border-radius: 12px;
	overflow: hidden;
}

.dialog-toolbar {
	background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover)) !important;
}

/* Empty State */
.empty-state {
	min-height: 300px;
	display: flex;
	align-items: center;
	justify-content: center;
}

/* Utility Classes */
.truncate {
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* Animations */
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

@keyframes pulse {
	0% {
		transform: scale(0.95);
		opacity: 0.7;
	}
	50% {
		transform: scale(1.05);
		opacity: 1;
	}
	100% {
		transform: scale(0.95);
		opacity: 0.7;
	}
}

/* Responsive Styles */
@media (max-width: 960px) {
	.section-header {
		flex-direction: column;
		align-items: stretch;
	}

	.header-actions {
		margin-top: 16px;
		margin-left: 0;
		width: 100%;
	}

	.search-field {
		width: 100%;
		min-width: auto;
	}

	.actions-group {
		margin-top: 12px;
		width: 100%;
		justify-content: flex-start;
	}
}

@media (max-width: 600px) {
	.action-bar .v-card-text {
		flex-direction: column;
		align-items: stretch;
		gap: 16px;
	}

	.file-input {
		max-width: 100%;
	}

	.view-toggle {
		margin-top: 8px;
	}

	.section-title-with-options {
		flex-direction: column;
		align-items: flex-start;
	}

	.view-toggle {
		margin-top: 8px;
		margin-left: 0;
		align-self: flex-end;
	}
}
</style>
