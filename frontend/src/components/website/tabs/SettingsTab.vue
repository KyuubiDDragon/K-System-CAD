<template>
    <div class="settings-tab">
        <h2>Website-Einstellungen</h2>
        <p>Hier können Sie Ihre Website konfigurieren.</p>

        <!-- Template Selector -->
        <TemplateSelector
            :model-value="templateSettings"
            @update:model-value="updateTemplateSettings"
            @change="onChange"
        />

        <!-- Basic Information -->
        <BasicInfo
            :model-value="basicInfoSettings"
            @update:model-value="updateBasicInfo"
            @change="onChange"
        />

        <!-- Hero Section -->
        <HeroSection
            :model-value="heroSettings"
            :pages="pages"
            :get-media-url="getMediaUrl"
            @update:model-value="updateHeroSettings"
            @change="onChange"
            @upload-media="handleUploadMedia"
            @remove-media="handleRemoveMedia"
        />

        <!-- Color Scheme -->
        <ColorScheme
            :model-value="colorSchemeSettings"
            :preset-color-schemes="presetColorSchemes"
            @update:model-value="updateColorScheme"
            @change="onChange"
            @select-scheme="handleSelectScheme"
        />

        <!-- Media Settings -->
        <MediaSettings
            :model-value="mediaSettings"
            :get-media-url="getMediaUrl"
            @update:model-value="updateMediaSettings"
            @change="onChange"
            @upload-media="handleUploadMedia"
            @remove-media="handleRemoveMedia"
        />

        <!-- Social Links -->
        <SocialLinks
            :model-value="socialLinksSettings"
            :social-links="socialLinks"
            @update:model-value="updateSocialLinks"
            @change="onChange"
            @update-social-links="handleUpdateSocialLinks"
        />

        <!-- Contact Settings -->
        <ContactSettings
            :model-value="contactSettings"
            @update:model-value="updateContactSettings"
            @change="onChange"
        >
            <template #contact-form-editor>
                <slot name="contact-form-editor"></slot>
            </template>
        </ContactSettings>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import BasicInfo from '../settings/BasicInfo.vue';
import HeroSection from '../settings/HeroSection.vue';
import ColorScheme from '../settings/ColorScheme.vue';
import MediaSettings from '../settings/MediaSettings.vue';
import SocialLinks from '../settings/SocialLinks.vue';
import ContactSettings from '../settings/ContactSettings.vue';
import TemplateSelector from '../settings/TemplateSelector.vue';

interface Page {
    id: number;
    title: string;
}

interface SocialLink {
    platform: string;
    url: string;
}

interface ColorScheme {
    name: string;
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
    bannerBackground?: string;
    bannerText?: string;
    heroBackground?: string;
    heroText?: string;
    buttonBackground?: string;
    buttonText?: string;
}

interface CustomColors {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
}

interface WebsiteSettings {
    site_name: string;
    site_slogan: string;
    navbar_name: string;
    site_description: string;
    hero_image: string | null;
    cta_text: string;
    cta_target_type: string;
    cta_target_id: number | null;
    selectedScheme: string;
    selected_scheme: string;
    useCustomColors: boolean;
    customColors: CustomColors;
    custom_css: string | null;
    logo: string | null;
    banner: string | null;
    background_image: string | null;
    contact_email: string;
    contact_phone: string;
    footer_text: string;
    is_active: boolean;
    maintenance_mode: boolean;
    maintenance_message: string;
    show_contact_form: boolean;
    layout_template?: string;
    template_settings?: any;
    sections_order?: any;
}

interface Props {
    websiteSettings: WebsiteSettings;
    pages: Page[];
    socialLinks: SocialLink[];
    presetColorSchemes: ColorScheme[];
    getMediaUrl: (fileName: string | null) => string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change'): void;
    (e: 'update:websiteSettings', value: WebsiteSettings): void;
    (e: 'uploadMedia', event: Event, mediaType: string): void;
    (e: 'removeMedia', mediaType: string): void;
    (e: 'updateSocialLinks', links: SocialLink[]): void;
    (e: 'selectScheme', schemeName: string): void;
}>();

// Computed properties for each section
const templateSettings = computed(() => ({
    layout_template: props.websiteSettings.layout_template || 'default',
    template_settings: props.websiteSettings.template_settings,
}));

const basicInfoSettings = computed(() => ({
    site_name: props.websiteSettings.site_name,
    site_slogan: props.websiteSettings.site_slogan,
    navbar_name: props.websiteSettings.navbar_name,
    site_description: props.websiteSettings.site_description,
}));

const heroSettings = computed(() => ({
    hero_image: props.websiteSettings.hero_image,
    cta_text: props.websiteSettings.cta_text,
    cta_target_type: props.websiteSettings.cta_target_type,
    cta_target_id: props.websiteSettings.cta_target_id,
}));

const colorSchemeSettings = computed(() => ({
    selectedScheme: props.websiteSettings.selectedScheme,
    selected_scheme: props.websiteSettings.selected_scheme,
    useCustomColors: props.websiteSettings.useCustomColors,
    customColors: props.websiteSettings.customColors,
    custom_css: props.websiteSettings.custom_css,
}));

const mediaSettings = computed(() => ({
    logo: props.websiteSettings.logo,
    banner: props.websiteSettings.banner,
    background_image: props.websiteSettings.background_image,
}));

const socialLinksSettings = computed(() => ({
    contact_email: props.websiteSettings.contact_email,
    contact_phone: props.websiteSettings.contact_phone,
}));

const contactSettings = computed(() => ({
    footer_text: props.websiteSettings.footer_text,
    is_active: props.websiteSettings.is_active,
    maintenance_mode: props.websiteSettings.maintenance_mode,
    maintenance_message: props.websiteSettings.maintenance_message,
    show_contact_form: props.websiteSettings.show_contact_form,
}));

// Update handlers
function updateTemplateSettings(value: typeof templateSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateBasicInfo(value: typeof basicInfoSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateHeroSettings(value: typeof heroSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateColorScheme(value: typeof colorSchemeSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateMediaSettings(value: typeof mediaSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateSocialLinks(value: typeof socialLinksSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function updateContactSettings(value: typeof contactSettings.value) {
    emit('update:websiteSettings', {
        ...props.websiteSettings,
        ...value,
    });
}

function onChange() {
    emit('change');
}

function handleUploadMedia(event: Event, mediaType: string) {
    emit('uploadMedia', event, mediaType);
}

function handleRemoveMedia(mediaType: string) {
    emit('removeMedia', mediaType);
}

function handleUpdateSocialLinks(links: SocialLink[]) {
    emit('updateSocialLinks', links);
}

function handleSelectScheme(schemeName: string) {
    emit('selectScheme', schemeName);
}
</script>

<style scoped>
.settings-tab {
    padding: 1rem;
}

.settings-tab h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #e5e7eb;
}

.settings-tab > p {
    font-size: 1rem;
    color: #9ca3af;
    margin-bottom: 2rem;
}
</style>
