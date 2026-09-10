<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="outer-container pa-0"> <v-container fluid class="map-and-controls-container pa-1">
            <v-row no-gutters class="mb-2">
                <v-col cols="12">
                     <v-btn-toggle v-model="activeStyleName" mandatory density="compact" divided variant="outlined">
                        <v-btn @click="changeMapStyle('styleAtlas')" value="styleAtlas">{{ t('mapOnlyView.atlas') }}</v-btn>
                        <v-btn @click="changeMapStyle('styleSatelite')" value="styleSatelite">{{ t('mapOnlyView.satellite') }}</v-btn>
                        <v-btn @click="changeMapStyle('styleGrid')" value="styleGrid">{{ t('mapOnlyView.grid') }}</v-btn>
                     </v-btn-toggle>
                </v-col>
            </v-row>
            <v-row no-gutters class="map-row">
                <v-col cols="12" class="fill-height">
                     <div v-if="loadingMapData" class="d-flex justify-center align-center fill-height">
                        <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                     </div>
                     <l-map
                        v-else
                        ref="mapRef"
                        class="map-instance"
                        :zoom="initialZoom"
                        :center="center"
                        :options="mapOptions"
                        :crs="mapOptions.crs"
                    >
                         <l-marker
                             v-for="marker in displayedMarkers"
                             :key="marker.id"
                             :lat-lng="[marker.y_coordinate, marker.x_coordinate]"
                             :icon="customIcon(marker.icon)"
                          >
                            <l-popup :options="{ minWidth: 150 }"> <div class="text-subtitle-2 font-weight-bold mb-1">{{ marker.name }}</div>
                                <div v-if="marker.location" class="text-caption"><strong>Standort:</strong> {{ marker.location }}</div>
                                <div v-if="marker.ceo" class="text-caption"><strong>CEO:</strong> {{ marker.ceo }}</div>
                                <div v-if="marker.phonenumber" class="text-caption"><strong>Tel:</strong> {{ marker.phonenumber }}</div>
                                </l-popup>
                        </l-marker>
                    </l-map>
                </v-col>
            </v-row>
            </v-container>
    </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick, reactive } from 'vue';
import { LMap, LTileLayer, LMarker, LPopup } from "@vue-leaflet/vue-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
// Use apiClientPublic for read-only data, or apiClientAuth if auth is required
import { apiClientPublic } from '@/api'; // <-- Use PUBLIC or AUTH client
import type { Categories, Markers as Marker } from "@/types/Map"; // <-- Adjust path and types if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useI18n } from 'vue-i18n';

// --- Component State ---
const loadingMapData = ref(true); // Combined loading state
const errorSnackbar = ref({ visible: false, message: "", color: "error" });
const { t } = useI18n();

// --- Map State & Refs ---
const mapRef = ref<LMap | null>(null); // Ref for the LMap component instance
const mapInstance = computed(() => mapRef.value?.leafletObject as L.Map | undefined); // Access the Leaflet map instance
const initialZoom = ref(4);
const center = ref<L.LatLngExpression>([0, 0]); // Initial center
const attribution = ref("YOUR_ATTRIBUTION"); // Replace with your attribution
const activeStyleName = ref<'styleAtlas' | 'styleSatelite' | 'styleGrid'>("styleAtlas");

// --- Leaflet CRS & Tile Layers ---
const center_x = 117.3;
const center_y = 172.8;
const scale_x = 0.02072;
const scale_y = 0.0205;
const transformation = new L.Transformation(scale_x, center_x, -scale_y, center_y);

const CUSTOM_CRS = L.extend({}, L.CRS.Simple, {
    projection: L.Projection.LonLat,
    transformation: transformation,
    scale: (zoom: number) => Math.pow(2, zoom),
    zoom: (sc: number) => Math.log(sc) / Math.LN2,
    distance: (pos1: L.LatLng, pos2: L.LatLng): number => {
        const x_difference = pos2.lng - pos1.lng;
        const y_difference = pos2.lat - pos1.lat;
        return Math.sqrt(x_difference * x_difference + y_difference * y_difference);
    },
    infinite: true,
});

const commonTileOptions: L.TileLayerOptions = {
    minZoom: 0, maxZoom: 8, maxNativeZoom: 5, noWrap: true, attribution: attribution.value
};

const AtlasStyle = L.tileLayer('/mapStyles/styleAtlas/{z}/{x}/{y}.jpg', { ...commonTileOptions, id: 'styleAtlas map' });
const SateliteStyle = L.tileLayer('/mapStyles/styleSatelite/{z}/{x}/{y}.jpg', { ...commonTileOptions, id: 'styleSatelite map' });
const GridStyle = L.tileLayer('/mapStyles/styleGrid/{z}/{x}/{y}.png', { ...commonTileOptions, id: 'styleGrid map', zIndex: 10, opacity: 0.6 });

// Map Options for LMap component
const mapOptions = ref({
    crs: CUSTOM_CRS,
    minZoom: 2,
    maxZoom: 8,
    preferCanvas: true,
    center: center.value,
    zoom: initialZoom.value, // Use initialZoom
    doubleClickZoom: false, // Keep disabled as no add action on dblclick
});

// --- Data ---
const categories = ref<Categories[]>([]); // Not used in template currently, but fetched
const markers = ref<Marker[]>([]);

// --- Computed ---
// Simplified marker display - shows all fetched markers. Add filtering logic if needed.
const displayedMarkers = computed(() => markers.value);

// --- Methods ---

// Snackbar helper
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// Map Interaction
const changeMapStyle = (style: 'styleAtlas' | 'styleSatelite' | 'styleGrid') => {
  if (!mapInstance.value) return;
  const currentLayer = activeStyleName.value === 'styleAtlas' ? AtlasStyle : activeStyleName.value === 'styleSatelite' ? SateliteStyle : GridStyle;
  const newLayer = style === 'styleAtlas' ? AtlasStyle : style === 'styleSatelite' ? SateliteStyle : GridStyle;

  if (mapInstance.value.hasLayer(currentLayer)) {
      mapInstance.value.removeLayer(currentLayer);
  }
  // Always remove grid if switching away from grid, or if it exists when switching to grid (to re-add on top)
  if (mapInstance.value.hasLayer(GridStyle) && style !== 'styleGrid') {
     mapInstance.value.removeLayer(GridStyle);
  }

  mapInstance.value.addLayer(newLayer);

  // Ensure base layers are behind grid
  if (style === 'styleGrid') {
     // Optionally add a base layer beneath the grid if desired, e.g., Atlas
     // if (!mapInstance.value.hasLayer(AtlasStyle)) mapInstance.value.addLayer(AtlasStyle);
     // AtlasStyle.bringToBack();
     newLayer.bringToFront();
  } else {
      newLayer.bringToBack();
      // If grid was previously active and should persist as an overlay, re-add it here
      // if (activeStyleName.value === 'styleGrid' && mapInstance.value.hasLayer(GridStyle)) {
      //    mapInstance.value.addLayer(GridStyle);
      //    GridStyle.bringToFront();
      // }
  }

  // activeStyleName.value = style; // v-model on btn-toggle handles this
};

// Marker Icon
const customIcon = (iconName: string | undefined) => {
  const iconUrl = iconName ? `/img/mapIcons/${iconName}.png` : '/img/mapIcons/default.png'; // Fallback icon
  return L.icon({
    iconUrl: iconUrl,
    iconSize: [20, 20],
    iconAnchor: [10, 10],
    popupAnchor: [0, -10], // Adjust popup anchor relative to icon anchor
  });
};

// Data Fetching
const fetchCategories = async () => {
  try {
    const response = await apiClientPublic.get<{ data: Categories[] }>('mapOnly/?action=getCategories'); // Adjust endpoint/client if needed
    categories.value = response.data.data || response.data || [];
  } catch (error: any) {
    console.error("Error fetching categories:", error);
    showSnackbar(error.response?.data?.error || t("mapOnlyView.errorLoadCategories"), "error");
  }
};

const fetchMarker = async () => {
  try {
    const response = await apiClientPublic.get<{ data: Marker[] }>('mapOnly/?action=getMarker'); // Adjust endpoint/client if needed
    markers.value = response.data.data || response.data || [];
  } catch (error: any) {
    console.error("Error fetching markers:", error);
    showSnackbar(error.response?.data?.error || t("mapOnlyView.errorLoadMarkers"), "error");
  }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    loadingMapData.value = true;
    await Promise.all([fetchCategories(), fetchMarker()]);

    await nextTick(); // Wait for map container to render

    if (mapInstance.value) {
        mapInstance.value.addLayer(AtlasStyle); // Add initial style
        AtlasStyle.bringToBack();
         // Set initial view (optional: fit bounds if markers exist)
         if (markers.value.length > 0) {
              try {
                 const bounds = L.latLngBounds(markers.value.map(m => [m.y_coordinate, m.x_coordinate]));
                 if (bounds.isValid()) {
                    mapInstance.value.fitBounds(bounds, { padding: [50, 50] }); // Add padding
                 } else {
                     mapInstance.value.setView(center.value, initialZoom.value); // Fallback
                 }
              } catch (e) {
                  console.error("Error calculating bounds:", e);
                  mapInstance.value.setView(center.value, initialZoom.value); // Fallback
              }
         } else {
             mapInstance.value.setView(center.value, initialZoom.value);
         }
    } else {
        console.error("Map instance not available on mount.");
        showSnackbar(t("mapOnlyView.initError"), "error");
    }
     loadingMapData.value = false;
});

</script>

<style scoped>
.outer-container {
    height: calc(100vh - var(--k-bar-height) - 2 * var(--k-work-pad));
    display: flex;
    flex-direction: column;
}
.map-and-controls-container {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    height: 100%; /* Take full height of outer-container */
}
.map-row {
     flex-grow: 1; /* Allow map row to fill available space */
     height: calc(100% - 40px); /* Adjust based on control row height */
}
.map-instance {
   height: 100%;
   width: 100%;
   background-color: #eee; /* Placeholder background */
   border-radius: 4px;
}
</style>