<template>
    <component
        :is="currentTemplate"
        :website="website"
        :navigation-items="navigationItems"
        :pages="pages"
        :posts="posts"
        :categories="categories"
        :sections="sections"
        :news="news"
        :preview-mode="previewMode"
        :get-media-url="getMediaUrl"
    />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';

// Lazy load templates
const DefaultTemplate = defineAsyncComponent(() => import('./DefaultTemplate.vue'));
const OnePagerTemplate = defineAsyncComponent(() => import('./OnePagerTemplate.vue'));

interface Props {
    website: any;
    navigationItems?: any[];
    pages?: any[];
    posts?: any[];
    categories?: any[];
    sections?: any[];
    news?: any[];
    previewMode?: boolean;
    getMediaUrl?: (fileName: string | null) => string;
}

const props = withDefaults(defineProps<Props>(), {
    navigationItems: () => [],
    pages: () => [],
    posts: () => [],
    categories: () => [],
    sections: () => [],
    news: () => [],
    previewMode: false,
    getMediaUrl: (fileName) => fileName || ''
});

// Determine which template to use based on website.layout_template
const currentTemplate = computed(() => {
    const template = props.website?.layout_template || 'default';

    switch (template) {
        case 'onepager':
            return OnePagerTemplate;
        case 'landing':
            // return LandingTemplate; // Will be implemented later
            return DefaultTemplate; // Fallback to default for now
        case 'portfolio':
            // return PortfolioTemplate; // Will be implemented later
            return DefaultTemplate; // Fallback to default for now
        case 'business':
            // return BusinessTemplate; // Will be implemented later
            return DefaultTemplate; // Fallback to default for now
        case 'default':
        default:
            return DefaultTemplate;
    }
});
</script>

<style scoped>
/* Template router has no specific styles */
</style>
