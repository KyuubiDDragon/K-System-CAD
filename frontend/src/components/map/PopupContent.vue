<template>
  <div class="marker-popup-content">
      <div v-if="marker.category_id === 1"> <h3 class="text-subtitle-1 font-weight-medium mb-1">{{ marker.name || t('popup.unnamed') }}</h3>
          <p class="text-caption mb-0"><strong>{{ t('popup.location') }}:</strong> {{ marker.location || '-' }}</p>
      </div>
      <div v-else>
          <h3 class="text-subtitle-1 font-weight-medium mb-1">{{ marker.name || t('popup.unnamed') }}</h3>
          <p class="text-caption mb-0"><strong>{{ t('popup.ceo') }}:</strong> {{ marker.ceo || '-' }}</p>
          <p class="text-caption mb-0"><strong>{{ t('popup.phone') }}:</strong> {{ marker.phonenumber || '-' }}</p>
          <p class="text-caption mb-0"><strong>{{ t('popup.location') }}:</strong> {{ marker.location || '-' }}</p>
      </div>

      <v-divider class="my-2"></v-divider>
      <v-row dense justify="end" class="pa-0">
           <v-col cols="auto" class="pa-0 pr-1">
              <v-tooltip :text="t('popup.edit')" location="top">
                  <template v-slot:activator="{ props: tooltipProps }">
                     <v-btn
                         v-if="canEdit"
                         variant="text" size="small" color="primary"
                         @click.stop="editMarker"
                         v-bind="tooltipProps"
                         icon="mdi-pencil"
                     ></v-btn>
                  </template>
               </v-tooltip>
            </v-col>
            <v-col cols="auto" class="pa-0">
                 <v-tooltip :text="t('popup.delete')" location="top">
                     <template v-slot:activator="{ props: tooltipProps }">
                        <v-btn
                            v-if="canDelete"
                            variant="text" size="small" color="error"
                            @click.stop="deleteMarker"
                            v-bind="tooltipProps"
                            icon="mdi-delete"
                         ></v-btn>
                      </template>
                  </v-tooltip>
             </v-col>
      </v-row>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Markers as Marker } from '@/types/Map'; // Adjust path and type name if needed
// Removed ErrorSnackbar import as it's unlikely needed here

// --- Props ---
interface Props {
  marker?: Record<string, any>
  canEdit?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false
});

// --- Emits ---
const emit = defineEmits(['edit-marker', 'delete-marker']);

const { t } = useI18n();

// --- Methods ---
const editMarker = () => {
  emit('edit-marker', props.marker); // Emit event with marker data
};

const deleteMarker = () => {
  emit('delete-marker', props.marker); // Emit event with marker data (or just id)
};

</script>

<style scoped>
.marker-popup-content {
  min-width: 150px; /* Ensure minimum width */
  max-width: 250px; /* Prevent excessive width */
  font-size: 0.8rem; /* Adjust base font size */
}
.marker-popup-content h3 {
  line-height: 1.3;
}
.marker-popup-content p {
  line-height: 1.4;
   margin-bottom: 2px;
}
.v-btn {
  min-width: 36px; /* Ensure icon buttons have space */
}
</style>