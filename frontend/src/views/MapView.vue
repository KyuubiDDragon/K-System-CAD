<template>
    <div class="map-container">
        <div class="map-layout">
            <!-- Linke Spalte mit Karte -->
            <div class="map-main-area">
                <div class="map-style-controls">
                    <v-btn-group variant="outlined" density="comfortable" color="primary" rounded>
                        <v-btn
                            @click="changeMapStyle('styleAtlas')"
                            :color="activeStyle === 'styleAtlas' ? 'primary' : ''"
                            variant="tonal"
                            size="small"
                            prepend-icon="mdi-map"
                        >
                            {{ $t('mapView.atlas') }}
                        </v-btn>
                        <v-btn
                            @click="changeMapStyle('styleSatelite')"
                            :color="activeStyle === 'styleSatelite' ? 'primary' : ''"
                            variant="tonal"
                            size="small"
                            prepend-icon="mdi-satellite-variant"
                        >
                            {{ $t('mapView.satellite') }}
                        </v-btn>
                        <v-btn
                            @click="changeMapStyle('styleGrid')"
                            :color="activeStyle === 'styleGrid' ? 'primary' : ''"
                            variant="tonal"
                            size="small"
                            prepend-icon="mdi-grid"
                        >
                            {{ $t('mapView.grid') }}
                        </v-btn>
                    </v-btn-group>

                    <v-spacer></v-spacer>

                    <!-- Export Button -->
                    <v-btn
                        prepend-icon="mdi-image-export"
                        color="success"
                        variant="tonal"
                        size="small"
                        @click="openExportDialog"
                    >
                        {{ $t('mapView.exportAsImages') || 'Karten exportieren' }}
                    </v-btn>

                    <!-- Share Button -->
                    <v-btn
                        prepend-icon="mdi-share-variant"
                        color="info"
                        variant="tonal"
                        size="small"
                        @click="openShareDialog"
                        class="ml-2"
                    >
                        {{ $t('mapView.shareMap') || 'Karte teilen' }}
                    </v-btn>
                </div>

                <div class="map-wrapper">
                    <l-map
                        ref="map"
                        class="map-instance"
                        :zoom="4"
                        :center="center"
                        :options="mapOptions"
                        :crs="mapOptions.crs"
                        @dblclick="onMapClick"
                    >
                        <l-marker
                            v-for="marker in filteredMarkers"
                            :key="marker.id"
                            :lat-lng="[marker.y_coordinate, marker.x_coordinate]"
                            :icon="customIcon(marker.category_icon)"
                        >
                            <l-popup class="marker-popup">
                                <div class="popup-content">
                                    <div v-if="marker.category_id == 1">
                                        <h3 class="popup-title">{{ marker.name }}</h3>
                                        <p class="popup-detail">
                                            <strong>{{ $t('mapView.locationLabel') }}</strong>
                                            {{ marker.location }}
                                        </p>
                                    </div>
                                    <div v-else>
                                        <h3 class="popup-title">{{ marker.name }}</h3>
                                        <p class="popup-detail">
                                            <strong>{{ $t('mapView.ceoLabel') }}</strong>
                                            {{ marker.ceo }}
                                        </p>
                                        <p class="popup-detail">
                                            <strong>{{ $t('mapView.phoneLabel') }}</strong>
                                            {{ marker.phonenumber }}
                                        </p>
                                        <p class="popup-detail">
                                            <strong>{{ $t('mapView.locationLabel') }}</strong>
                                            {{ marker.location }}
                                        </p>
                                    </div>
                                    <div class="popup-actions">
                                        <v-btn
                                            variant="text"
                                            size="small"
                                            color="primary"
                                            @click="editMarker(marker)"
                                            v-if="canEdit"
                                            prepend-icon="mdi-pencil"
                                        >
                                            {{ $t('edit') }}
                                        </v-btn>
                                        <v-btn
                                            variant="text"
                                            size="small"
                                            color="error"
                                            @click="deleteMarker(marker)"
                                            v-if="canDelete"
                                            prepend-icon="mdi-delete"
                                        >
                                            {{ $t('delete') }}
                                        </v-btn>
                                    </div>
                                </div>
                            </l-popup>
                        </l-marker>
                    </l-map>
                </div>
            </div>

            <!-- Rechte Spalte mit Filtern und Liste -->
            <div class="map-sidebar">
                <div class="sidebar-header">
                    <h2 class="sidebar-title">
                        <v-icon icon="mdi-map-marker" class="mr-2"></v-icon>
                        {{ $t('mapView.locations') }}
                    </h2>
                </div>

                <div class="sidebar-filters">
                    <v-select
                        :items="categories"
                        item-title="name"
                        item-value="id"
                        :label="$t('mapView.category')"
                        v-model="selectedCategories"
                        multiple
                        chips
                        closable-chips
                        variant="outlined"
                        density="comfortable"
                        hide-details
                        color="primary"
                        class="mb-3"
                    />

                    <v-text-field
                        :label="$t('mapView.search')"
                        v-model="search"
                        variant="outlined"
                        density="comfortable"
                        prepend-inner-icon="mdi-magnify"
                        hide-details
                        color="primary"
                        class="mb-3"
                    />
                </div>

                <div class="location-list">
                    <v-expansion-panels v-model="expandedPanels" multiple>
                        <v-expansion-panel
                            v-for="category in visibleCategories"
                            :key="category.id"
                            class="map-category-panel"
                            :value="category.id"
                        >
                            <v-expansion-panel-title>
                                <div class="category-header">
                                    <v-icon icon="mdi-folder" size="small" class="mr-2"></v-icon>
                                    <span>{{ category.name }}</span>
                                    <v-chip
                                        size="x-small"
                                        class="ml-2"
                                        color="primary"
                                        variant="flat"
                                    >
                                        {{ filteredMarkersByCategory(category.id).length }}
                                    </v-chip>
                                </div>
                            </v-expansion-panel-title>

                            <v-expansion-panel-text>
                                <v-list
                                    density="compact"
                                    nav
                                    bg-color="transparent"
                                    class="location-item-list"
                                >
                                    <v-list-item
                                        v-for="marker in filteredMarkersByCategory(category.id)"
                                        :key="marker.id"
                                        @click="focusOnMarker(marker)"
                                        :value="marker.id"
                                        color="primary"
                                        class="location-item"
                                        rounded="lg"
                                    >
                                        <template v-slot:prepend>
                                            <v-icon
                                                icon="mdi-map-marker"
                                                size="small"
                                                class="mr-2"
                                            ></v-icon>
                                        </template>

                                        <v-list-item-title>{{ marker.name }}</v-list-item-title>
                                        <v-list-item-subtitle>{{
                                            marker.location
                                        }}</v-list-item-subtitle>
                                    </v-list-item>
                                </v-list>

                                <div
                                    v-if="filteredMarkersByCategory(category.id).length === 0"
                                    class="no-locations"
                                >
                                    {{ $t('mapView.noLocationsInCategory') }}
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </div>
            </div>
        </div>

        <!-- Neuen Marker Dialog -->
        <v-dialog v-model="showDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-map-marker-plus" class="mr-2"></v-icon>
                    {{ $t('mapView.addMarker') }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                :label="$t('mapView.nameRequired')"
                                v-model="markerName"
                                required
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-tag"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                :label="$t('mapView.ceo')"
                                v-model="ceo"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-account-tie"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                :label="$t('mapView.phone')"
                                v-model="phonenumber"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-phone"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                :label="$t('mapView.location')"
                                v-model="location"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-map-marker"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-select
                                :items="categories"
                                item-title="name"
                                item-value="id"
                                :label="$t('mapView.categoryRequired')"
                                v-model="selectedCategory"
                                required
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-shape"
                            />
                        </v-col>
                    </v-row>

                    <p class="required-field-notice">{{ $t('mapView.requiredFieldsNotice') }}</p>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeAddDialog"> {{ $t('cancel') }} </v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="addMarker"
                        :disabled="!markerName || !selectedCategory"
                    >
                        {{ $t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Marker bearbeiten Dialog -->
        <v-dialog v-model="editDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-map-marker-edit" class="mr-2"></v-icon>
                    {{ $t('mapView.editMarker') }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                label="Name*"
                                v-model="markerName"
                                required
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-tag"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                label="CEO"
                                v-model="ceo"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-account-tie"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                label="Telefonnummer"
                                v-model="phonenumber"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-phone"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-text-field
                                label="Standort"
                                v-model="location"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-map-marker"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-select
                                :items="categories"
                                item-title="name"
                                item-value="id"
                                :label="$t('mapView.categoryRequired')"
                                v-model="selectedCategory"
                                required
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-shape"
                            />
                        </v-col>
                    </v-row>

                    <p class="required-field-notice">{{ $t('mapView.requiredFieldsNotice') }}</p>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeEditDialog"> {{ $t('cancel') }} </v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="updateMarker"
                        :disabled="!markerName || !selectedCategory"
                    >
                        {{ $t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Export Dialog -->
        <v-dialog v-model="exportDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-image-export" class="mr-2"></v-icon>
                    {{ $t('mapView.exportMaps') || 'Karten exportieren' }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <div v-if="!exporting">
                        <p class="text-body-2 mb-4">
                            {{ $t('mapView.exportDescription') || 'Wählen Sie die Kategorien aus, die Sie in den exportierten Karten sehen möchten. Es werden automatisch alle 3 Karten-Stile (Atlas, Satellite, Grid) in hoher Auflösung exportiert.' }}
                        </p>

                        <v-select
                            :items="categories"
                            item-title="name"
                            item-value="id"
                            :label="$t('mapView.selectCategories') || 'Kategorien auswählen'"
                            v-model="exportCategoriesSelection"
                            multiple
                            chips
                            closable-chips
                            variant="outlined"
                            density="comfortable"
                            color="primary"
                            bg-color="grey-darken-3"
                            prepend-inner-icon="mdi-filter"
                        />

                        <v-alert type="info" variant="tonal" class="mt-4" density="compact">
                            <div class="text-body-2">
                                <strong>{{ $t('mapView.exportInfo') || 'Export-Information:' }}</strong>
                                <ul class="mt-2 ml-4">
                                    <li>{{ $t('mapView.exportInfo1') || '3 Bilder werden exportiert (Atlas, Satellite, Grid)' }}</li>
                                    <li>{{ $t('mapView.exportInfo2') || 'Sehr hohe Auflösung (4x Qualität)' }}</li>
                                    <li>{{ $t('mapView.exportInfo3') || 'Nur ausgewählte Kategorien werden angezeigt' }}</li>
                                    <li>{{ $t('mapView.exportInfo4') || 'Karte wird automatisch optimal gezoomt' }}</li>
                                </ul>
                            </div>
                        </v-alert>
                    </div>

                    <div v-else class="text-center py-8">
                        <v-progress-circular
                            :model-value="exportProgress"
                            :size="100"
                            :width="8"
                            color="success"
                        >
                            {{ exportProgress }}%
                        </v-progress-circular>
                        <p class="mt-4 text-body-1">
                            {{ $t('mapView.exportingInProgress') || 'Exportiere Karten...' }}
                        </p>
                        <p class="text-body-2 text-medium-emphasis">
                            {{ $t('mapView.pleaseWait') || 'Bitte warten Sie, dies kann einige Sekunden dauern.' }}
                        </p>
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        @click="closeExportDialog"
                        :disabled="exporting"
                    >
                        {{ $t('cancel') }}
                    </v-btn>
                    <v-btn
                        color="success"
                        variant="elevated"
                        @click="exportMapAsImages"
                        :disabled="exportCategoriesSelection.length === 0"
                        :loading="exporting"
                        prepend-icon="mdi-download"
                    >
                        {{ $t('mapView.startExport') || 'Export starten' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Share Dialog -->
        <v-dialog v-model="shareDialog" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-share-variant" class="mr-2"></v-icon>
                    {{ $t('mapView.shareMap') || 'Karte teilen' }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <div v-if="!shareLink">
                        <p class="text-body-2 mb-4">
                            {{ $t('mapView.shareDescription') || 'Erstellen Sie einen öffentlichen Link für diese Karte. Kein Login erforderlich!' }}
                        </p>

                        <!-- Show Sidebar Option -->
                        <v-checkbox
                            v-model="shareShowSidebar"
                            :label="$t('mapView.showCategorySidebar') || 'Kategorie-Sidebar anzeigen'"
                            color="primary"
                            density="comfortable"
                            hide-details
                        ></v-checkbox>

                        <!-- Category Selection -->
                        <div class="mt-4">
                            <v-radio-group v-model="shareAllCategories" hide-details>
                                <v-radio
                                    :label="$t('mapView.allCategories') || 'Alle Kategorien anzeigen'"
                                    :value="true"
                                    color="primary"
                                ></v-radio>
                                <v-radio
                                    :label="$t('mapView.selectedCategories') || 'Nur ausgewählte Kategorien'"
                                    :value="false"
                                    color="primary"
                                ></v-radio>
                            </v-radio-group>

                            <v-select
                                v-if="!shareAllCategories"
                                :items="categories"
                                item-title="name"
                                item-value="id"
                                :label="$t('mapView.selectCategories') || 'Kategorien auswählen'"
                                v-model="shareCategoriesSelection"
                                multiple
                                chips
                                closable-chips
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="grey-darken-3"
                                prepend-inner-icon="mdi-filter"
                                class="mt-3"
                            />
                        </div>

                        <v-alert type="info" variant="tonal" class="mt-4" density="compact">
                            <div class="text-body-2">
                                <strong>{{ $t('mapView.shareInfo') || 'Info:' }}</strong>
                                <ul class="mt-2 ml-4">
                                    <li>{{ $t('mapView.shareInfo1') || 'Kein Login erforderlich' }}</li>
                                    <li>{{ $t('mapView.shareInfo2') || 'Kein HUD (Header/Footer)' }}</li>
                                    <li>{{ $t('mapView.shareInfo3') || 'Nur reine Karte im Vollbild' }}</li>
                                </ul>
                            </div>
                        </v-alert>
                    </div>

                    <!-- Generated Link -->
                    <div v-else>
                        <v-alert type="success" variant="tonal" class="mb-4">
                            {{ $t('mapView.shareLinkCreated') || 'Share-Link erfolgreich erstellt!' }}
                        </v-alert>

                        <v-text-field
                            :model-value="shareLink"
                            readonly
                            variant="outlined"
                            density="comfortable"
                            :label="$t('mapView.shareLink') || 'Share-Link'"
                            append-inner-icon="mdi-content-copy"
                            @click:append-inner="copyShareLink"
                        ></v-text-field>

                        <div class="d-flex gap-2 mt-3">
                            <v-btn
                                color="primary"
                                variant="elevated"
                                prepend-icon="mdi-open-in-new"
                                @click="openShareLink"
                                block
                            >
                                {{ $t('mapView.openLink') || 'Link öffnen' }}
                            </v-btn>
                            <v-btn
                                color="success"
                                variant="elevated"
                                prepend-icon="mdi-content-copy"
                                @click="copyShareLink"
                                block
                            >
                                {{ $t('mapView.copyLink') || 'Link kopieren' }}
                            </v-btn>
                        </div>
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        @click="closeShareDialog"
                        :disabled="creatingShare"
                    >
                        {{ shareLink ? $t('close') : $t('cancel') }}
                    </v-btn>
                    <v-btn
                        v-if="!shareLink"
                        color="info"
                        variant="elevated"
                        @click="createShareLink"
                        :disabled="!shareAllCategories && shareCategoriesSelection.length === 0"
                        :loading="creatingShare"
                        prepend-icon="mdi-link"
                    >
                        {{ $t('mapView.createShareLink') || 'Link erstellen' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { apiClientAuth } from '@/api'; // <-- Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // <-- Import Pinia Auth Store
import type { Categories, Markers } from '@/types/Map'; // <-- Assuming types are defined like this
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import html2canvas from 'html2canvas';

// --- Pinia Store ---
const authStore = useAuthStore(); // Instantiate the store

// --- Route & Permissions ---
const route = useRoute();

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

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);
const mapType = ref((route.meta.mapType as string) || 'defaultMap'); // Get mapType from route meta

// --- Map State & Refs ---
const map = ref<typeof LMap | null>(null); // Ref for the LMap component instance
const mapInstance = computed(() => map.value?.leafletObject as L.Map | undefined); // Access the Leaflet map instance
const zoom = ref(4); // Initial zoom (will be set by map options)
// Using [number, number] type which is compatible with both LatLngExpression and PointExpression
const center = ref<[number, number]>([0, 0]); // Initial center
const attribution = ref('K-Systems');
const activeStyle = ref('styleAtlas');

// --- Leaflet CRS & Tile Layers ---
const center_x = 117.3;
const center_y = 172.8;
const scale_x = 0.02072;
const scale_y = 0.0205;

const transformation = new L.Transformation(scale_x, center_x, -scale_y, center_y);

const CUSTOM_CRS = L.extend({}, L.CRS.Simple, {
    projection: L.Projection.LonLat,
    transformation: transformation,
    scale: function (zoom: number) {
        return Math.pow(2, zoom);
    },
    zoom: function (sc: number) {
        return Math.log(sc) / 0.6931471805599453;
    },
    distance: function (pos1: L.LatLng, pos2: L.LatLng): number {
        const x_difference = pos2.lng - pos1.lng;
        const y_difference = pos2.lat - pos1.lat;
        return Math.sqrt(x_difference * x_difference + y_difference * y_difference);
    },
    infinite: true,
});

const commonTileOptions: L.TileLayerOptions = {
    minZoom: 0,
    maxZoom: 8,
    maxNativeZoom: 5,
    noWrap: false, // Changed from false, Simple CRS usually requires noWrap
    attribution: attribution.value, // Use the ref value
};

// Define Tile Layers (will be added/removed dynamically)
const AtlasStyle = L.tileLayer('/mapStyles/styleAtlas/{z}/{x}/{y}.jpg', {
    ...commonTileOptions,
    id: 'styleAtlas map',
});
const SateliteStyle = L.tileLayer('/mapStyles/styleSatelite/{z}/{x}/{y}.jpg', {
    ...commonTileOptions,
    id: 'styleSatelite map',
});
const GridStyle = L.tileLayer('/mapStyles/styleGrid/{z}/{x}/{y}.png', {
    ...commonTileOptions,
    id: 'styleGrid map',
    opacity: 0.5, // Example: Make grid slightly transparent if needed
    zIndex: 10, // Example: Ensure grid is on top
});

// Map Options for LMap component
const mapOptions = ref({
    crs: CUSTOM_CRS,
    minZoom: 2,
    maxZoom: 8,
    preferCanvas: true,
    zoom: 2, // Use zoom ref here if needed, but prefer setting via mapInstance later
    center: center.value, // Use center ref
    doubleClickZoom: false,
});

// --- Markers & Categories Data ---
const markers = ref<Markers[]>([]);
const categories = ref<Categories[]>([]);
const selectedCategories = ref<number[]>([]); // For filtering
const search = ref('');

// --- Dialog State & Form Data ---
const toast = useToast();
const { t } = useI18n();
const showDialog = ref(false); // Add marker dialog
const editDialog = ref(false); // Edit marker dialog
const markerName = ref('');
const ceo = ref('');
const location = ref('');
const phonenumber = ref('');
const selectedCategory = ref<number | null>(null); // For add/edit form
const clickedLatLng = ref<L.LatLng | null>(null); // LatLng from map click
const markerToEdit = ref<Markers | null>(null); // Marker being edited

// --- NEW - Panel Expansion Controls ---
const expandedPanels = ref<number[]>([]); // Track expanded panels by category ID

// --- Export State ---
const exportDialog = ref(false);
const exporting = ref(false);
const exportProgress = ref(0);
const exportCategoriesSelection = ref<number[]>([]);

// --- Share State ---
const shareDialog = ref(false);
const creatingShare = ref(false);
const shareShowSidebar = ref(false);
const shareAllCategories = ref(true);
const shareCategoriesSelection = ref<number[]>([]);
const shareLink = ref('');
const shareToken = ref('');

// --- Map Interaction Functions ---

const changeMapStyle = (style: 'styleAtlas' | 'styleSatelite' | 'styleGrid') => {
    if (!mapInstance.value) return;

    // Remove current style layer
    if (activeStyle.value === 'styleSatelite') mapInstance.value.removeLayer(SateliteStyle);
    else if (activeStyle.value === 'styleAtlas') mapInstance.value.removeLayer(AtlasStyle);
    // Grid can be an overlay, decide if it should be removed or toggled
    if (mapInstance.value.hasLayer(GridStyle)) mapInstance.value.removeLayer(GridStyle);

    // Add new style layer
    activeStyle.value = style;
    if (style === 'styleSatelite') {
        mapInstance.value.addLayer(SateliteStyle);
        SateliteStyle.bringToBack(); // Ensure base layer is at the back
    } else if (style === 'styleAtlas') {
        mapInstance.value.addLayer(AtlasStyle);
        AtlasStyle.bringToBack(); // Ensure base layer is at the back
    } else if (style === 'styleGrid') {
        // Decide if Grid replaces or overlays
        // To replace: add Atlas or Satelite first, then Grid
        // mapInstance.value.addLayer(AtlasStyle); // Or SateliteStyle
        // AtlasStyle.bringToBack();
        mapInstance.value.addLayer(GridStyle);
        GridStyle.bringToFront(); // Ensure grid is visible on top
    }
};

const onMapClick = (event: L.LeafletMouseEvent) => {
    // Use Leaflet's built-in event properties
    clickedLatLng.value = event.latlng;
    // Reset form fields for adding
    markerName.value = '';
    ceo.value = '';
    phonenumber.value = '';
    location.value = '';
    selectedCategory.value = null;
    markerToEdit.value = null;
    showDialog.value = true;
};

const customIcon = (icon: string) => {
    return L.icon({
        iconUrl: `/img/mapIcons/${icon}.png`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -10],
    });
};

const focusOnMarker = (marker: Markers) => {
    if (!mapInstance.value) return;
    const newZoomLevel = 5; // Or desired zoom level
    mapInstance.value.flyTo([marker.y_coordinate, marker.x_coordinate], newZoomLevel);
    // Optionally open the popup after flying
    nextTick(() => {
        map.value?.leafletObject.eachLayer((layer: L.Layer) => {
            if (layer instanceof L.Marker) {
                const markerLatLng = layer.getLatLng();
                if (
                    markerLatLng.lat === marker.y_coordinate &&
                    markerLatLng.lng === marker.x_coordinate
                ) {
                    layer.openPopup();
                }
            }
        });
    });
};

// --- API Interaction Functions ---

const fetchCategories = async () => {
    try {
        const response = await apiClientAuth.post<Categories[]>('/map/?action=getCategories', {
            mapType: mapType.value,
        });
        categories.value = response.data; // Data is directly in response.data, not nested
    } catch (error: any) {
        console.error('Error fetching categories:', error);
        toast.error(t('mapView.errorLoadCategories'));
    }
};

const fetchMarker = async () => {
    try {
        const response = await apiClientAuth.post<Markers[]>('/map/?action=getMarker', {
            mapType: mapType.value,
        });
        markers.value = response.data; // Data is directly in response.data, not nested
    } catch (error: any) {
        console.error('Error fetching markers:', error);
        toast.error(t('mapView.errorLoadMarkers'));
    }
};

const addMarker = async () => {
    if (!markerName.value || !selectedCategory.value || !clickedLatLng.value) {
        toast.error(t('mapView.fillRequired'));
        return;
    }
    try {
        await apiClientAuth.post('/map/?action=addMarker', {
            name: markerName.value,
            ceo: ceo.value,
            phonenumber: phonenumber.value,
            location: location.value,
            category_id: selectedCategory.value,
            x_coordinate: clickedLatLng.value.lng,
            y_coordinate: clickedLatLng.value.lat,
            mapType: mapType.value,
        });

        await fetchMarker(); // Refresh markers list
        closeAddDialog();
    } catch (error: any) {
        console.error('Error adding marker:', error);
        toast.error(t('mapView.errorAddMarker'));
    }
};

const editMarker = (marker: Markers) => {
    markerToEdit.value = marker;
    markerName.value = marker.name;
    ceo.value = marker.ceo;
    phonenumber.value = marker.phonenumber;
    location.value = marker.location;
    selectedCategory.value = marker.category_id;
    clickedLatLng.value = null; // Not needed for edit, clear it
    editDialog.value = true;
};

const updateMarker = async () => {
    if (!markerToEdit.value || !markerName.value || !selectedCategory.value) {
        toast.error(t('mapView.fillRequired'));
        return;
    }
    try {
        await apiClientAuth.post('/map/?action=updateMarker', {
            id: markerToEdit.value.id,
            name: markerName.value,
            ceo: ceo.value,
            phonenumber: phonenumber.value,
            location: location.value,
            category_id: selectedCategory.value,
            // Keep original coordinates when updating other details
            x_coordinate: markerToEdit.value.x_coordinate,
            y_coordinate: markerToEdit.value.y_coordinate,
            mapType: mapType.value,
        });

        await fetchMarker(); // Refresh markers list
        closeEditDialog();
    } catch (error: any) {
        console.error('Error updating marker:', error);
        toast.error(t('mapView.errorUpdateMarker'));
    }
};

const deleteMarker = async (markerToDelete: Markers) => {
    // Optional: Add a confirmation dialog here
    if (!confirm(t('mapView.deleteMarkerConfirm', { name: markerToDelete.name }))) {
        return;
    }

    try {
        await apiClientAuth.post('/map/?action=deleteMarker', {
            id: markerToDelete.id,
            mapType: mapType.value,
        });
        // Remove marker from local state immediately for better UX
        markers.value = markers.value.filter(m => m.id !== markerToDelete.id);
        // Optionally show a success message
    } catch (error: any) {
        console.error('Error deleting marker:', error);
        toast.error(t('mapView.errorDeleteMarker'));
        // Optionally re-fetch markers if delete failed to ensure consistency
        // await fetchMarker();
    }
};

// --- Dialog Close Functions ---
const closeAddDialog = () => {
    showDialog.value = false;
    // Optional: Reset fields if needed, though onMapClick does this before opening
    markerName.value = '';
    ceo.value = '';
    phonenumber.value = '';
    location.value = '';
    selectedCategory.value = null;
    clickedLatLng.value = null;
};

const closeEditDialog = () => {
    editDialog.value = false;
    markerToEdit.value = null;
    // Reset fields
    markerName.value = '';
    ceo.value = '';
    phonenumber.value = '';
    location.value = '';
    selectedCategory.value = null;
};

// --- Export Functions ---
const openExportDialog = () => {
    // Pre-select all categories
    exportCategoriesSelection.value = categories.value.map(cat => cat.id);
    exportDialog.value = true;
};

const closeExportDialog = () => {
    exportDialog.value = false;
    exportProgress.value = 0;
};

const exportMapAsImages = async () => {
    if (exportCategoriesSelection.value.length === 0) {
        toast.error(t('mapView.selectAtLeastOneCategory') || 'Bitte wählen Sie mindestens eine Kategorie aus');
        return;
    }

    exporting.value = true;
    exportProgress.value = 0;
    const styles: Array<'styleAtlas' | 'styleSatelite' | 'styleGrid'> = ['styleAtlas', 'styleSatelite', 'styleGrid'];
    const styleNames = {
        styleAtlas: 'Atlas',
        styleSatelite: 'Satellite',
        styleGrid: 'Grid'
    };

    // Base dimensions - NO zoom increase, just large canvas to show full map
    // Canvas will be exactly these dimensions (no scaling)
    // Using 15000x16000 to stay under browser limit and show complete map area
    const baseWidth = 15000;
    const baseHeight = 16000;

    try {
        toast.info(t('mapView.exportingMaps') || 'Exportiere Karten...');

        // Calculate bounds for filtered markers
        const markerBounds = filteredMarkers.value
            .filter(marker => exportCategoriesSelection.value.includes(marker.category_id))
            .map(marker => [marker.y_coordinate, marker.x_coordinate] as [number, number]);

        if (markerBounds.length === 0) {
            toast.error(t('mapView.noMarkersToExport') || 'Keine Marker zum Exportieren gefunden');
            exporting.value = false;
            return;
        }

        const bounds = L.latLngBounds(markerBounds);

        // Export each style
        for (let i = 0; i < styles.length; i++) {
            const style = styles[i];
            exportProgress.value = Math.round(((i) / styles.length) * 100);

            // NO zoom increase - use fitBounds zoom level as-is to show full map
            // Large canvas without zooming in ensures all corners are visible
            const zoomIncrease = 0;
            const scaleFactor = Math.pow(2, zoomIncrease); // 2^0 = 1
            const exportWidth = baseWidth * scaleFactor; // 15000 * 1 = 15000px
            const exportHeight = baseHeight * scaleFactor; // 16000 * 1 = 16000px

            // Create hidden container for export with calculated size
            const exportContainer = document.createElement('div');
            exportContainer.id = 'export-map-container';
            exportContainer.style.cssText = `
                position: fixed;
                top: -10000px;
                left: -10000px;
                width: ${exportWidth}px;
                height: ${exportHeight}px;
                z-index: -1000;
            `;
            document.body.appendChild(exportContainer);

            try {
                // Create new map instance for export
                const exportMap = L.map(exportContainer, {
                    crs: CUSTOM_CRS,
                    minZoom: 2,
                    maxZoom: 8,
                    preferCanvas: true,
                    zoomControl: false,
                    attributionControl: false
                });

                // Add appropriate tile layer
                let tileLayer: L.TileLayer;
                if (style === 'styleAtlas') {
                    tileLayer = L.tileLayer('/mapStyles/styleAtlas/{z}/{x}/{y}.jpg', commonTileOptions);
                } else if (style === 'styleSatelite') {
                    tileLayer = L.tileLayer('/mapStyles/styleSatelite/{z}/{x}/{y}.jpg', commonTileOptions);
                } else {
                    tileLayer = L.tileLayer('/mapStyles/styleGrid/{z}/{x}/{y}.png', {
                        ...commonTileOptions,
                        opacity: 0.5,
                        zIndex: 10
                    });
                }
                tileLayer.addTo(exportMap);

                // Fit bounds to markers first to get center point
                exportMap.fitBounds(bounds, {
                    padding: [30, 30],
                    animate: false
                });

                // Get the center and current zoom from fitBounds
                const centerPoint = exportMap.getCenter();
                const fittedZoom = exportMap.getZoom();

                // Use fitBounds zoom as-is (no additional zoom) to show complete map area
                const exportZoom = Math.min(fittedZoom + zoomIncrease, 8);

                // Keep the zoom level from fitBounds to ensure all markers/corners are visible
                exportMap.setView(centerPoint, exportZoom, { animate: false });

                // Add markers to export map
                const exportMarkersToShow = filteredMarkers.value.filter(marker =>
                    exportCategoriesSelection.value.includes(marker.category_id)
                );

                exportMarkersToShow.forEach(marker => {
                    // Larger icons for high-res export (60x60 vs 20x20 on normal map)
                    const markerIcon = L.icon({
                        iconUrl: `/img/mapIcons/${marker.category_icon}.png`,
                        iconSize: [60, 60],
                        iconAnchor: [30, 30]
                    });

                    L.marker([marker.y_coordinate, marker.x_coordinate], {
                        icon: markerIcon
                    }).addTo(exportMap);
                });

                // Wait for tiles to load (at higher zoom = more tiles!)
                await new Promise<void>((resolve) => {
                    let tilesLoaded = 0;
                    let tilesToLoad = 0;
                    let loadingComplete = false;

                    const checkComplete = () => {
                        if (!loadingComplete && tilesLoaded >= tilesToLoad && tilesToLoad > 0) {
                            loadingComplete = true;
                            setTimeout(() => resolve(), 2000); // Wait for tile rendering
                        }
                    };

                    tileLayer.on('tileloadstart', () => {
                        tilesToLoad++;
                    });

                    tileLayer.on('tileload', () => {
                        tilesLoaded++;
                        checkComplete();
                    });

                    tileLayer.on('tileerror', () => {
                        tilesLoaded++;
                        checkComplete();
                    });

                    // Fallback timeout (longer for more tiles at higher zoom)
                    setTimeout(() => {
                        if (!loadingComplete) {
                            loadingComplete = true;
                            resolve();
                        }
                    }, 10000); // 10 seconds for all tiles to load
                });

                // Wait a bit more for rendering to complete
                await nextTick();
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Capture the export map
                // Canvas is 15000x16000px with no additional zoom, showing complete map area with 60x60 icons
                // Using scale:1 to stay within browser limits
                console.log(`Capturing canvas: ${exportWidth}x${exportHeight}`);
                const canvas = await html2canvas(exportContainer, {
                    backgroundColor: '#111723',
                    scale: 1, // No additional scaling to stay under browser limits (15000x16000px final)
                    logging: false, // Disable logging for cleaner output
                    useCORS: true,
                    allowTaint: true,
                    imageTimeout: 90000, // Longer timeout for large canvas
                    removeContainer: false,
                    width: exportWidth,
                    height: exportHeight
                });
                console.log(`Canvas created: ${canvas.width}x${canvas.height}`);

                // Download image with better error handling
                await new Promise<void>((resolve, reject) => {
                    try {
                        canvas.toBlob((blob) => {
                            console.log(`toBlob callback called, blob:`, blob);
                            if (blob) {
                                console.log(`Blob size: ${blob.size} bytes`);
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `map-${styleNames[style]}-${new Date().toISOString().split('T')[0]}.png`;
                                a.click();
                                URL.revokeObjectURL(url);
                                console.log(`Download triggered for ${styleNames[style]}`);
                                resolve();
                            } else {
                                console.error('toBlob returned null - canvas may be too large');
                                reject(new Error('Failed to create blob - canvas may exceed browser limits'));
                            }
                        }, 'image/png');
                    } catch (err) {
                        console.error('Error in toBlob:', err);
                        reject(err);
                    }
                });

                // Cleanup: remove map and container
                exportMap.remove();
                document.body.removeChild(exportContainer);

                exportProgress.value = Math.round(((i + 1) / styles.length) * 100);

            } catch (err: any) {
                console.error(`Error exporting ${style}:`, err);
                toast.error(`${t('mapView.errorExporting') || 'Fehler beim Exportieren von'} ${styleNames[style]}`);

                // Cleanup on error
                const container = document.getElementById('export-map-container');
                if (container) {
                    document.body.removeChild(container);
                }
            }
        }

        toast.success(t('mapView.mapsExported') || 'Alle Karten erfolgreich exportiert!');
        closeExportDialog();
    } catch (err: any) {
        console.error('Export error:', err);
        toast.error(err.message || t('mapView.exportError') || 'Fehler beim Exportieren der Karten');
    } finally {
        exporting.value = false;
        exportProgress.value = 0;
    }
};

// --- Share Functions ---
const openShareDialog = () => {
    // Pre-select all categories
    shareCategoriesSelection.value = categories.value.map(cat => cat.id);
    shareShowSidebar.value = false;
    shareAllCategories.value = true;
    shareLink.value = '';
    shareToken.value = '';
    shareDialog.value = true;
};

const closeShareDialog = () => {
    shareDialog.value = false;
    shareLink.value = '';
    shareToken.value = '';
};

const createShareLink = async () => {
    try {
        creatingShare.value = true;

        const payload = {
            showSidebar: shareShowSidebar.value,
            categoryIds: shareAllCategories.value ? null : shareCategoriesSelection.value,
            mapType: mapType.value
        };

        const response = await apiClientAuth.post('/map/', payload, {
            params: {
                action: 'createShare',
                mapType: mapType.value
            }
        });

        if (response.data.success) {
            shareToken.value = response.data.token;
            // Build full URL
            const baseUrl = window.location.origin;
            shareLink.value = `${baseUrl}/map/shared/${response.data.token}`;
            toast.success(t('mapView.shareLinkCreated') || 'Share-Link erfolgreich erstellt!');
        } else {
            toast.error(t('mapView.shareError') || 'Fehler beim Erstellen des Share-Links');
        }
    } catch (error: any) {
        console.error('Error creating share link:', error);
        toast.error(error.message || t('mapView.shareError') || 'Fehler beim Erstellen des Share-Links');
    } finally {
        creatingShare.value = false;
    }
};

const copyShareLink = async () => {
    try {
        await navigator.clipboard.writeText(shareLink.value);
        toast.success(t('mapView.linkCopied') || 'Link in Zwischenablage kopiert!');
    } catch (error) {
        console.error('Failed to copy link:', error);
        toast.error(t('mapView.copyError') || 'Fehler beim Kopieren des Links');
    }
};

const openShareLink = () => {
    window.open(shareLink.value, '_blank');
};

// --- Computed Properties for Filtering ---

const filteredMarkers = computed(() => {
    const searchTermLower = search.value.toLowerCase();
    return markers.value.filter(marker => {
        const categoryMatch =
            selectedCategories.value.length === 0 ||
            selectedCategories.value.includes(marker.category_id);
        const searchMatch =
            !search.value ||
            marker.name.toLowerCase().includes(searchTermLower) ||
            (marker.location && marker.location.toLowerCase().includes(searchTermLower)) || // Check if location exists
            (marker.ceo && marker.ceo.toLowerCase().includes(searchTermLower)); // Check if ceo exists

        return categoryMatch && searchMatch;
    });
});

const filteredMarkersByCategory = (categoryId: number): Markers[] => {
    return filteredMarkers.value.filter(marker => marker.category_id === categoryId);
};

// --- NEW - Computed Properties for UI Enhancements ---

// Only show categories that have visible markers after filtering
const visibleCategories = computed(() => {
    return categories.value.filter(category => {
        return filteredMarkersByCategory(category.id).length > 0;
    });
});

// Update which panels should be expanded based on search and filters
const updateExpandedPanels = () => {
    if (!search.value) {
        // If no search, collapse all panels (or keep default expanded state)
        expandedPanels.value = [];
    } else {
        // If searching, expand all panels that have matches
        expandedPanels.value = visibleCategories.value.map(cat => cat.id);
    }
};

// --- Lifecycle Hooks ---

onMounted(async () => {
    await fetchCategories();
    await fetchMarker();

    // Pre-select categories (excluding 'Hydrant' if that logic is still needed)
    selectedCategories.value = categories.value
        // .filter((category) => category.name !== 'Hydrant') // Uncomment if Hydrant shouldn't be pre-selected
        .map(category => category.id);

    // Ensure map instance is ready before adding layers
    await nextTick();
    if (mapInstance.value) {
        mapInstance.value.addLayer(AtlasStyle); // Add initial style
        AtlasStyle.bringToBack();
        mapInstance.value.setView(center.value, 2); // Set initial view explicitly
    } else {
        console.error('Map instance not available on mount.');
    }
    
    // Check for ID from route query or props
    const markerId = route.query.id || props.id || props.meta?.id;
    
    if (markerId) {
        // Find the marker with the specified ID
        const marker = markers.value.find(m => m.id === Number(markerId));
        if (marker) {
            // Set the selected marker and open edit dialog
            selectedMarker.value = marker;
            editDialog.value = true;
            
            // Center map on the marker
            if (mapInstance.value) {
                mapInstance.value.setView([marker.x, marker.y], 10);
            }
        }
    }
});

// --- Watchers ---

// Watch for search input changes to auto-expand panels with results
watch(search, () => {
    updateExpandedPanels();
});

// Watch for category filter changes to ensure UI updates correctly
watch(selectedCategories, () => {
    // When changing category filters, we might need to update which panels are expanded
    if (search.value) {
        updateExpandedPanels();
    }
});

// Initialize expandedPanels when markers or categories change
watch([markers, categories], () => {
    // This is helpful when data is first loaded
    if (search.value) {
        updateExpandedPanels();
    }
}, { immediate: true });
</script>

<style scoped>
.v-list.v-list--nav.v-theme--dark.bg-transparent.v-list--density-compact.v-list--one-line.location-item-list {
  overflow-y: hidden !important;
  overflow-x: hidden !important;
}
.map-container {
    min-height: calc(100vh - 64px);
    height: calc(100vh - 64px);
    width: 100%;
    background-color: var(--k-canvas);
    position: relative;
    padding: 16px;
    display: flex;
    flex-direction: column;
}

.map-layout {
    display: flex;
    height: 100%;
    gap: 16px;
}

.map-main-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    height: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
}

.map-style-controls {
    padding: 12px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

.map-wrapper {
    flex: 1;
    width: 100%;
    min-height: 0;
    position: relative;
}

.map-instance {
    height: 100%;
    width: 100%;
    z-index: 1;
}

.map-sidebar {
    width: 320px;
    height: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 12px;
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    overflow: hidden;
}

.sidebar-header {
    padding: 16px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
}

.sidebar-title {
    display: flex;
    align-items: center;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--k-ink);
    margin: 0;
}

.sidebar-filters {
    padding: 16px;
    border-bottom: 1px solid var(--k-line);
}

.location-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.map-category-panel {
    border-radius: 8px;
    margin-bottom: 8px;
    background-color: var(--k-sunken) !important;
    border: 1px solid var(--k-line);
}

.map-category-panel :deep(.v-expansion-panel-title) {
    padding: 12px 16px;
    min-height: 0;
}

.map-category-panel :deep(.v-expansion-panel-text__wrapper) {
    padding: 0 8px 12px 8px;
}

.category-header {
    display: flex;
    align-items: center;
    font-weight: 500;
}

.location-item-list {
    padding: 0;
}

.location-item {
    border-radius: 8px !important;
    margin-bottom: 4px;
    transition: all 0.2s ease;
}

.location-item:hover {
    background-color: var(--k-accent-weak) !important;
    transform: translateX(4px);
}

.no-locations {
    padding: 12px;
    text-align: center;
    color: var(--k-ink-muted);
    font-size: 0.875rem;
}

.marker-popup :deep(.leaflet-popup-content-wrapper) {
    background-color: var(--k-sunken);
    color: var(--k-ink);
    border-radius: 8px;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.marker-popup :deep(.leaflet-popup-tip) {
    background-color: var(--k-sunken);
}

.popup-content {
    padding: 8px;
    min-width: 200px;
}

.popup-title {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--k-ink);
}

.popup-detail {
    margin: 4px 0;
    font-size: 0.875rem;
}

.popup-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 12px;
    gap: 8px;
}

.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
}

.dialog-title {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

.required-field-notice {
    font-size: 0.75rem;
    color: var(--k-ink-muted);
    margin-top: 8px;
}

@media (max-width: 768px) {
    .map-layout {
        flex-direction: column;
    }

    .map-sidebar {
        width: 100%;
        max-height: 300px;
    }

    .map-main-area {
        height: calc(100% - 316px);
    }
}
</style>