<template>
    <div class="shared-map-container">
        <!-- No HUD - just the map -->
        <div class="map-content">
            <!-- Map Style Switcher (minimal, top-right) -->
            <div class="map-style-switcher">
                <v-btn-group density="compact" variant="elevated">
                    <v-btn
                        size="small"
                        :color="activeStyle === 'styleAtlas' ? 'primary' : 'default'"
                        @click="changeMapStyle('styleAtlas')"
                    >
                        Atlas
                    </v-btn>
                    <v-btn
                        size="small"
                        :color="activeStyle === 'styleSatelite' ? 'primary' : 'default'"
                        @click="changeMapStyle('styleSatelite')"
                    >
                        Satellite
                    </v-btn>
                    <v-btn
                        size="small"
                        :color="activeStyle === 'styleGrid' ? 'primary' : 'default'"
                        @click="changeMapStyle('styleGrid')"
                    >
                        Grid
                    </v-btn>
                </v-btn-group>
            </div>

            <!-- Optional Sidebar -->
            <v-navigation-drawer
                v-if="showSidebar && categories.length > 0"
                permanent
                location="right"
                width="300"
                class="category-sidebar"
            >
                <v-list density="compact">
                    <v-list-subheader>{{ $t('mapView.categories') || 'Kategorien' }}</v-list-subheader>
                    <v-list-item
                        v-for="category in categories"
                        :key="category.id"
                        @click="toggleCategory(category.id)"
                        :active="selectedCategories.includes(category.id)"
                    >
                        <template v-slot:prepend>
                            <v-avatar size="24">
                                <v-img :src="`/img/mapIcons/${category.icon}.png`"></v-img>
                            </v-avatar>
                        </template>
                        <v-list-item-title>{{ category.name }}</v-list-item-title>
                        <template v-slot:append>
                            <v-chip size="x-small" color="primary">
                                {{ getMarkerCountForCategory(category.id) }}
                            </v-chip>
                        </template>
                    </v-list-item>
                </v-list>
            </v-navigation-drawer>

            <!-- Map -->
            <div class="map-wrapper">
                <l-map
                    ref="map"
                    class="map-instance"
                    :zoom="4"
                    :center="center"
                    :options="mapOptions"
                    @ready="onMapReady"
                >
                    <!-- Tile Layers - Only show active style -->
                    <l-tile-layer
                        v-if="activeStyle === 'styleAtlas'"
                        :url="atlasUrl"
                        :options="commonTileOptions"
                    />
                    <l-tile-layer
                        v-if="activeStyle === 'styleSatelite'"
                        :url="sateliteUrl"
                        :options="commonTileOptions"
                    />
                    <l-tile-layer
                        v-if="activeStyle === 'styleGrid'"
                        :url="gridUrl"
                        :options="{ ...commonTileOptions, opacity: 0.5, zIndex: 10 }"
                    />

                    <!-- Markers -->
                    <l-marker
                        v-for="marker in filteredMarkers"
                        :key="marker.id"
                        :lat-lng="[marker.y_coordinate, marker.x_coordinate]"
                        :icon="createMarkerIcon(marker.category_icon)"
                    >
                        <l-popup>
                            <div class="marker-popup">
                                <h3>{{ marker.name }}</h3>
                                <p v-if="marker.ceo"><strong>CEO:</strong> {{ marker.ceo }}</p>
                                <p v-if="marker.phonenumber"><strong>Tel:</strong> {{ marker.phonenumber }}</p>
                                <p v-if="marker.location"><strong>Ort:</strong> {{ marker.location }}</p>
                                <p><strong>Kategorie:</strong> {{ marker.category_name }}</p>
                            </div>
                        </l-popup>
                    </l-marker>
                </l-map>
            </div>
        </div>

        <!-- Loading Overlay -->
        <v-overlay v-model="loading" class="align-center justify-center" contained>
            <v-progress-circular indeterminate size="64"></v-progress-circular>
            <p class="mt-4">{{ $t('loading') || 'Lädt...' }}</p>
        </v-overlay>

        <!-- Error Message -->
        <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="error-alert"
        >
            {{ error }}
        </v-alert>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import type { Categories, Markers } from '@/types/Map';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const { t } = useI18n();

// Get token from route
const token = route.params.token as string;

// State
const loading = ref(true);
const error = ref('');
const categories = ref<Categories[]>([]);
const markers = ref<Markers[]>([]);
const selectedCategories = ref<number[]>([]);
const showSidebar = ref(false);
const mapType = ref<string>('global');
const activeStyle = ref<'styleAtlas' | 'styleSatelite' | 'styleGrid'>('styleAtlas');

// Map references
const map = ref();
const mapInstance = ref<L.Map | null>(null);
const center = ref<L.LatLngExpression>([50.0, 10.0]);

// Custom CRS configuration (same as MapView.vue)
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
});

const mapOptions = {
    crs: CUSTOM_CRS,
    minZoom: 2,
    maxZoom: 8,
    zoomControl: true,
    attributionControl: false,
};

// Tile layer URLs
const atlasUrl = '/mapStyles/styleAtlas/{z}/{x}/{y}.jpg';
const sateliteUrl = '/mapStyles/styleSatelite/{z}/{x}/{y}.jpg';
const gridUrl = '/mapStyles/styleGrid/{z}/{x}/{y}.png';

const commonTileOptions: L.TileLayerOptions = {
    minZoom: 0,
    maxZoom: 8,
    maxNativeZoom: 5,
    noWrap: false,
};

// Fetch shared map data
const fetchSharedMap = async () => {
    try {
        loading.value = true;
        error.value = '';

        const response = await axios.get(`${import.meta.env.VITE_API_URL}/map/`, {
            params: {
                action: 'getSharedMap',
                token: token
            }
        });

        if (response.data.success) {
            categories.value = response.data.categories;
            markers.value = response.data.markers;
            showSidebar.value = response.data.showSidebar;
            mapType.value = response.data.mapType || 'global';

            // Pre-select all categories
            selectedCategories.value = categories.value.map(cat => cat.id);

            // Fit map to markers after data is loaded
            setTimeout(() => {
                fitMapToMarkers();
            }, 500);
        } else {
            error.value = t('mapView.shareNotFound') || 'Share-Link nicht gefunden';
        }
    } catch (err: any) {
        console.error('Error fetching shared map:', err);
        if (err.response?.status === 404) {
            error.value = t('mapView.shareNotFound') || 'Share-Link nicht gefunden oder abgelaufen';
        } else if (err.response?.status === 410) {
            error.value = t('mapView.shareExpired') || 'Share-Link ist abgelaufen';
        } else {
            error.value = t('mapView.shareLoadError') || 'Fehler beim Laden der geteilten Karte';
        }
    } finally {
        loading.value = false;
    }
};

// Create marker icon
const createMarkerIcon = (iconName: string): L.Icon => {
    return L.icon({
        iconUrl: `/img/mapIcons/${iconName}.png`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -10],
    });
};

// Filtered markers based on selected categories
const filteredMarkers = computed(() => {
    if (!showSidebar.value || selectedCategories.value.length === 0) {
        return markers.value;
    }
    return markers.value.filter(marker =>
        selectedCategories.value.includes(marker.category_id)
    );
});

// Get marker count for category
const getMarkerCountForCategory = (categoryId: number): number => {
    return markers.value.filter(m => m.category_id === categoryId).length;
};

// Toggle category selection
const toggleCategory = (categoryId: number) => {
    const index = selectedCategories.value.indexOf(categoryId);
    if (index > -1) {
        selectedCategories.value.splice(index, 1);
    } else {
        selectedCategories.value.push(categoryId);
    }
};

// Change map style
const changeMapStyle = (style: 'styleAtlas' | 'styleSatelite' | 'styleGrid') => {
    activeStyle.value = style;
};

// Fit map to markers
const fitMapToMarkers = () => {
    if (!mapInstance.value || filteredMarkers.value.length === 0) return;

    const bounds = L.latLngBounds(
        filteredMarkers.value.map(m => [m.y_coordinate, m.x_coordinate] as L.LatLngExpression)
    );
    mapInstance.value.fitBounds(bounds, {
        padding: [50, 50],
        maxZoom: 6
    });
};

// Map ready handler
const onMapReady = () => {
    mapInstance.value = map.value?.leafletObject as L.Map;
    if (filteredMarkers.value.length > 0) {
        fitMapToMarkers();
    }
};

// Load data on mount
onMounted(() => {
    fetchSharedMap();
});
</script>

<style scoped>
.shared-map-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background-color: var(--k-canvas);
}

.map-content {
    width: 100%;
    height: 100%;
    position: relative;
}

.map-style-switcher {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.map-wrapper {
    width: 100%;
    height: 100%;
}

.map-instance {
    width: 100%;
    height: 100%;
    z-index: 1;
}

.category-sidebar {
    position: absolute !important;
    right: 0;
    top: 0;
    height: 100%;
    z-index: 1000;
    background-color: var(--k-surface);
    backdrop-filter: blur(10px);
}

.marker-popup h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
}

.marker-popup p {
    margin: 5px 0;
    font-size: 14px;
}

.error-alert {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 500px;
    z-index: 2000;
}
</style>
