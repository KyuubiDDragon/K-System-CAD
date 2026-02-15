<template>
    <span v-if="url" class="image-link-wrapper">
        <v-tooltip location="top" :disabled="!url">
             <template v-slot:activator="{ props: tooltipProps }">
                <a
                   href="#"
                   @click.prevent="copyLink"
                   v-bind="tooltipProps"
                   class="text-link"
                   :title="t('admin.clickToCopy', { url })"
                 >
                   {{ label || t('admin.link') }}
                </a>
             </template>
              <v-img
                 :src="url"
                 :alt="`${label || t('admin.image')} ${t('admin.preview')}`"
                 max-width="250"
                 max-height="150"
                 contain
                 class="tooltip-image"
                 @error="imageError = true"
              >
                  <template v-slot:placeholder>
                       <div class="d-flex align-center justify-center fill-height">
                           <v-progress-circular indeterminate size="20" color="grey"></v-progress-circular>
                        </div>
                   </template>
                   <template v-slot:error>
                        <div class="d-flex align-center justify-center fill-height text-caption text-grey">{{ t('admin.imageNotLoadable') }}</div>
                    </template>
               </v-img>
         </v-tooltip>
    </span>
    <span v-else class="text-caption text-grey">-</span>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

// --- Props ---
interface Props {
  url?: string
  label?: string
}

const props = withDefaults(defineProps<Props>(), {
  url: null,
  label: 'Show Link'
});

// --- I18n ---
const { t } = useI18n();

// --- Emits ---
// Emit 'copy' event with success status and the copied text
const emit = defineEmits<{
  (e: 'copy', payload: { success: boolean; text: string | null }): void
}>();

// --- State ---
const imageError = ref(false); // Track image loading errors

// --- Methods ---
const copyLink = async () => {
    if (!props.url || !navigator.clipboard) {
        const message = !navigator.clipboard ? t('admin.copyNotSupported') : t('admin.noUrlToCopy');
         emit('copy', { success: false, text: message }); // Emit failure
         return;
     }
    try {
        await navigator.clipboard.writeText(props.url);
         emit('copy', { success: true, text: t("admin.linkCopied", { label: props.label }) }); // Emit success
    } catch (err) {
        console.error(t('admin.copyLinkError'), err);
         emit('copy', { success: false, text: t("admin.copyError", { err }) }); // Emit failure
    }
};
</script>

<style scoped>
.text-link {
    cursor: pointer;
    text-decoration: underline;
    color: rgb(var(--v-theme-primary)); /* Use theme primary color */
    font-size: 0.8rem;
}
.text-link:hover {
    text-decoration: none;
    color: rgb(var(--v-theme-primary-darken-1));
}
.tooltip-image {
    background-color: rgba(255, 255, 255, 0.9); /* Light background for image */
    border-radius: 4px;
}
</style>