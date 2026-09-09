<!-- WaterDuck Browser Component -->
<template>
    <div
        class="waterduck-browser"
        :class="{ 'standalone-mode': isStandalone, 'dark-mode': isDarkMode }"
    >
        <!-- Browser Navigation Bar -->
        <div class="browser-navigation">
            <div class="browser-controls">
                <button class="nav-button" :disabled="!canGoBack" @click="goBack">
                    <v-icon>mdi-arrow-left</v-icon>
                </button>
                <button class="nav-button" :disabled="!canGoForward" @click="goForward">
                    <v-icon>mdi-arrow-right</v-icon>
                </button>
                <button class="nav-button" @click="refreshPage">
                    <v-icon>mdi-refresh</v-icon>
                </button>
                <button class="nav-button" @click="goHome">
                    <v-icon>mdi-home</v-icon>
                </button>
            </div>

            <!-- Address Bar -->
            <div class="address-bar">
                <div class="site-info">
                    <v-icon v-if="isSecure" size="small" color="green">mdi-lock</v-icon>
                    <v-icon v-else size="small" color="gray">mdi-earth</v-icon>
                </div>
                <input
                    type="text"
                    v-model="currentUrl"
                    @keydown.enter="navigateTo(currentUrl)"
                    :placeholder="t('waterduckApp.enterUrl')"
                />
            </div>

            <!-- Search and Menu -->
            <div class="browser-actions">
                <button class="action-button" @click="showSearch = !showSearch">
                    <v-icon>mdi-magnify</v-icon>
                </button>
                <button class="action-button" @click="showMenu = !showMenu">
                    <v-icon>mdi-dots-vertical</v-icon>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar" v-if="showSearch">
            <input
                type="text"
                v-model="searchQuery"
                @keydown.enter="search"
                :placeholder="t('waterduckApp.searchPlaceholder')"
                ref="searchInput"
            />
            <button class="search-button" @click="search">
                <v-icon>mdi-magnify</v-icon>
            </button>
        </div>

        <!-- Menu -->
        <div class="browser-menu" v-if="showMenu">
            <div class="menu-item" @click="newTab">
                <v-icon size="small">mdi-plus</v-icon>
                <span>{{ t('waterduckApp.newTab') }}</span>
            </div>
            <div class="menu-item" @click="clearHistory">
                <v-icon size="small">mdi-delete-sweep</v-icon>
                <span>{{ t('waterduckApp.clearHistory') }}</span>
            </div>
            <div class="menu-item" @click="toggleDarkMode">
                <v-icon size="small">{{
                    isDarkMode ? 'mdi-weather-sunny' : 'mdi-weather-night'
                }}</v-icon>
                <span>{{ isDarkMode ? t('waterduckApp.lightMode') : t('waterduckApp.darkMode') }}</span>
            </div>
            <div class="menu-item" @click="showMenu = false">
                <v-icon size="small">mdi-close</v-icon>
                <span>{{ t('waterduckApp.closeMenu') }}</span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="browser-tabs">
            <div
                v-for="(tab, index) in tabs"
                :key="index"
                class="browser-tab"
                :class="{ active: index === currentTabIndex }"
                @click="currentTabIndex = index"
            >
                <div class="tab-content">
                    <v-icon v-if="tab.loading" size="x-small" class="loading-icon"
                        >mdi-loading mdi-spin</v-icon
                    >
                    <!-- Tab icon -->
                    <div class="tab-icon" v-if="!tab.loading">
                        <v-icon v-if="tab.url === 'home'" size="x-small">mdi-home</v-icon>
                        <v-icon v-else-if="!tab.logo" size="x-small">mdi-web</v-icon>
                        <img v-else :src="getLogoUrl(tab.logo)" alt="Site Icon" />
                    </div>
                    <span class="tab-title">{{ tab.title || t('waterduckApp.newTab') }}</span>
                </div>
                <button class="tab-close" @click.stop="closeTab(index)">
                    <v-icon size="x-small">mdi-close</v-icon>
                </button>
            </div>
            <button class="new-tab-button" @click="newTab">
                <v-icon size="small">mdi-plus</v-icon>
            </button>
        </div>

        <!-- Browser Content Area -->
        <div class="browser-content">
            <!-- Home Screen -->
            <div v-if="currentTab.url === 'home'" class="browser-home">
                <div class="browser-logo">
                    <img src="/img/waterduck.png" alt="WaterDuck" class="logo-image-duck" />
                    <h1>WaterDuck</h1>
                </div>

                <div class="search-container">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @keydown.enter="search"
                        :placeholder="t('waterduckApp.searchWebPlaceholder')"
                        class="home-search"
                    />
                    <button class="home-search-button" @click="search">
                        <v-icon>mdi-magnify</v-icon>
                    </button>
                </div>

                <div class="quick-links">
                    <h3>{{ t('waterduckApp.websites') }}</h3>
                    <div v-if="websites.length === 0" class="no-websites">
                        <p>{{ t('waterduckApp.noWebsites') }}</p>
                    </div>
                    <div v-else class="link-grid">
                        <div
                            v-for="(site, index) in websites"
                            :key="index"
                            class="quick-link-item"
                            @click="navigateToWebsite(site.id)"
                        >
                            <div class="site-icon">
                                <v-icon v-if="!site.logo" size="large" color="primary"
                                    >mdi-web</v-icon
                                >
                                <img v-else :src="getLogoUrl(site.logo)" alt="Website Logo" />
                            </div>
                            <div class="site-title">{{ site.site_name }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Website Content -->
            <WebsiteView
                v-else-if="currentTab.url.startsWith('website:')"
                :websiteId="getWebsiteId(currentTab.url)"
                :previewMode="false"
                :isInBrowser="true"
                @titleChanged="updateTabTitle"
                @logoChanged="updateTabLogo"
            />

            <!-- 404 Page -->
            <div v-else class="browser-error">
                <v-icon size="64" color="error">mdi-alert-circle</v-icon>
                <h2>{{ t('waterduckApp.pageNotFound') }}</h2>
                <p>{{ t('waterduckApp.urlNotFound', { url: currentTab.url }) }}</p>
                <button class="error-back-button" @click="goBack">{{ t('waterduckApp.goBack') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import WebsiteView from '@/views/WebsiteView.vue';
import { apiClientPublic } from '@/api';
import { useRoute } from 'vue-router';
import duckLogoImg from '@/assets/waterduck.png';

// Make logo available to template
const duckLogo = duckLogoImg;

// Check if component is used standalone or in desktop
const route = useRoute();
const { t } = useI18n();
const isStandalone = computed(() => route && route.name === 'waterduck');

// Browser State
const tabs = ref([
    {
        url: 'home',
        title: 'Home',
        history: ['home'],
        historyIndex: 0,
        loading: false,
        logo: null,
    },
]);
const currentTabIndex = ref(0);
const currentUrl = ref('waterduck.web');
const websites = ref([]);
const searchQuery = ref('');
const showSearch = ref(false);
const showMenu = ref(false);
const isDarkMode = ref(true);

// Get current tab
const currentTab = computed(() => tabs.value[currentTabIndex.value]);

// Calculate if we can navigate back/forward
const canGoBack = computed(() => {
    return currentTab.value && currentTab.value.historyIndex > 0;
});

const canGoForward = computed(() => {
    return currentTab.value && currentTab.value.historyIndex < currentTab.value.history.length - 1;
});

// Computed property to determine if the site is secure
const isSecure = computed(() => {
    const url = currentTab.value?.url || '';
    return url === 'home' || url.startsWith('website:');
});

// Load websites on component mount
onMounted(async () => {
    await fetchWebsites();

    // If in standalone mode and the route has a website ID, open that website directly
    if (isStandalone.value && route.query.website) {
        navigateToWebsite(route.query.website);
    }
});

// Watch for changes to current tab to update the URL display
watch(currentTabIndex, () => {
    if (currentTab.value) {
        currentUrl.value =
            currentTab.value.url === 'home'
                ? 'waterduck.web'
                : currentTab.value.url.replace('website:', 'website.waterduck.web/');
    }
});

// Fetch all websites
async function fetchWebsites() {
    try {
        console.log('Fetching websites...');
        const response = await apiClientPublic.get('/company/website/?action=getPublicWebsites');
        console.log('Website response:', response.data);

        if (response.data && response.data.success) {
            websites.value = response.data.websites || [];
            console.log('Loaded websites:', websites.value);
        } else if (response.data) {
            console.error('API returned unsuccessful response:', response.data);
            websites.value = [];
        }
    } catch (error) {
        console.error('Error fetching websites:', error);
        // Use dummy data for testing if API fails
        websites.value = [
            {
                id: 1,
                site_name: 'Demo Company Website',
                site_description: 'A demo website for testing',
                logo: null,
            },
            {
                id: 2,
                site_name: 'Test Blog',
                site_description: 'A test blog website',
                logo: null,
            },
        ];
        console.log('Using fallback test data:', websites.value);
    }
}

// Navigate to a website by ID
function navigateToWebsite(websiteId) {
    const url = `website:${websiteId}`;
    console.log('Navigating to website with ID:', websiteId, 'Type:', typeof websiteId);
    
    // Find the website to get its name for the tab title
    const website = websites.value.find(site => site.id == websiteId);
    console.log('Found website:', website);
    const title = website ? website.site_name : 'Website';
    
    // Update current tab's history and URL
    const tab = currentTab.value;
    tab.url = url;
    tab.title = title;
    // Store the logo for use in tab icon
    tab.logo = website?.logo || null;
    
    // Add to history if it's a new URL
    if (tab.history[tab.historyIndex] !== url) {
        // Cut off the forward history if we're navigating from a point in history
        tab.history = tab.history.slice(0, tab.historyIndex + 1);
        tab.history.push(url);
        tab.historyIndex = tab.history.length - 1;
    }
    
    // Update URL display
    currentUrl.value = url.replace('website:', 'website.waterduck.web/');
    
    // Show loading state briefly
    tab.loading = true;
    setTimeout(() => {
        tab.loading = false;
    }, 500);
}

// Extract website ID from URL
function getWebsiteId(url) {
    if (!url || typeof url !== 'string' || !url.includes('website:')) {
        console.error('Invalid URL format for getWebsiteId:', url);
        return null;
    }
    const id = url.replace('website:', '');
    console.log('Extracted websiteId:', id, 'Type:', typeof id);
    // Convert to number to ensure consistent type
    return parseInt(id, 10);
}

// Get logo URL with API base
function getLogoUrl(logo) {
    if (!logo) return null;
    const baseUrl = import.meta.env.VITE_API_URL || '';
    return `${baseUrl}/${logo}`;
}

// Navigation functions
function goBack() {
    if (!canGoBack.value) return;

    const tab = currentTab.value;
    tab.historyIndex--;
    tab.url = tab.history[tab.historyIndex];
    tab.loading = true;

    // Update URL display
    currentUrl.value =
        tab.url === 'home'
            ? 'waterduck.web'
            : tab.url.replace('website:', 'website.waterduck.web/');

    setTimeout(() => {
        tab.loading = false;
    }, 500);
}

function goForward() {
    if (!canGoForward.value) return;

    const tab = currentTab.value;
    tab.historyIndex++;
    tab.url = tab.history[tab.historyIndex];
    tab.loading = true;

    // Update URL display
    currentUrl.value =
        tab.url === 'home'
            ? 'waterduck.web'
            : tab.url.replace('website:', 'website.waterduck.web/');

    setTimeout(() => {
        tab.loading = false;
    }, 500);
}

function refreshPage() {
    const tab = currentTab.value;
    tab.loading = true;
    setTimeout(() => {
        tab.loading = false;
    }, 500);
}

function goHome() {
    navigateTo('home');
}

function navigateTo(url) {
    // Simple URL parsing
    if (url === 'waterduck.web' || url === 'home') {
        url = 'home';
    } else if (url.includes('website.waterduck.web/')) {
        // Extract the website ID from the URL
        const websiteId = url.replace('website.waterduck.web/', '');
        url = `website:${websiteId}`;
    }

    // Update current tab's history and URL
    const tab = currentTab.value;

    // Only update if it's a different URL
    if (tab.url !== url) {
        tab.url = url;

        // Add to history
        // Cut off the forward history if we're navigating from a point in history
        tab.history = tab.history.slice(0, tab.historyIndex + 1);
        tab.history.push(url);
        tab.historyIndex = tab.history.length - 1;
    }

    // Show loading state briefly
    tab.loading = true;
    setTimeout(() => {
        tab.loading = false;
    }, 500);
}

function search() {
    // For this demo, search just navigates to home
    showSearch.value = false;
    const tab = currentTab.value;

    // Set title to reflect search query
    tab.title = `Search: ${searchQuery.value}`;

    // Simulate search - just go home but with search title
    if (tab.url !== 'home') {
        tab.url = 'home';
        tab.history.push('home');
        tab.historyIndex = tab.history.length - 1;
    }

    // Clear search after navigation
    setTimeout(() => {
        searchQuery.value = '';
    }, 500);
}

function newTab() {
    tabs.value.push({
        url: 'home',
        title: t('waterduckApp.newTab'),
        history: ['home'],
        historyIndex: 0,
        loading: false,
        logo: null,
    });
    
    // Switch to the new tab
    currentTabIndex.value = tabs.value.length - 1;
    showMenu.value = false;
}

function closeTab(index) {
    // Don't close if it's the only tab
    if (tabs.value.length <= 1) return;

    // Remove the tab
    tabs.value.splice(index, 1);

    // Adjust current tab index if needed
    if (currentTabIndex.value >= tabs.value.length) {
        currentTabIndex.value = tabs.value.length - 1;
    }
}

function clearHistory() {
    // Keep only the current URL in history
    const currentUrl = currentTab.value.url;
    currentTab.value.history = [currentUrl];
    currentTab.value.historyIndex = 0;
    showMenu.value = false;
}

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    showMenu.value = false;
}

// Update the tab title when the website title changes
function updateTabTitle(title) {
    if (currentTab.value) {
        currentTab.value.title = title;
    }
}

// Update the tab logo when the website logo changes
function updateTabLogo(logo) {
    if (currentTab.value) {
        currentTab.value.logo = logo;
    }
}
</script>

<style scoped>

.main-container {
    height: 100% !important;
}

#web-body {
  --v-layout-top: 0px !important;
}

.waterduck-browser {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: var(--k-ink);
    border-radius: 8px;
    overflow: hidden;
    font-family:
        -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell,
        'Open Sans', 'Helvetica Neue', sans-serif;
    transition:
        background-color 0.3s ease,
        color 0.3s ease;
}

/* Dark mode styles */
.waterduck-browser[class*='dark-mode'],
.waterduck-browser[class*='dark-mode'] .browser-content {
    background-color: var(--k-surface);
    color: #e0e0e0;
}

.waterduck-browser[class*='dark-mode'] .browser-navigation,
.waterduck-browser[class*='dark-mode'] .search-bar,
.waterduck-browser[class*='dark-mode'] .browser-tabs {
    background-color: #2d2d2d;
    border-color: #444;
}

.waterduck-browser[class*='dark-mode'] .browser-tab {
    background-color: #3a3a3a;
    border-color: #444;
}

.waterduck-browser[class*='dark-mode'] .browser-tab.active {
    background-color: var(--k-surface);
}

.waterduck-browser[class*='dark-mode'] .address-bar {
    background-color: #333;
    border-color: #444;
}

.waterduck-browser[class*='dark-mode'] .address-bar input {
    color: #e0e0e0;
    background-color: transparent;
}

.waterduck-browser[class*='dark-mode'] .nav-button,
.waterduck-browser[class*='dark-mode'] .action-button,
.waterduck-browser[class*='dark-mode'] .new-tab-button {
    color: #bbb;
}

.waterduck-browser[class*='dark-mode'] .nav-button:hover:not(:disabled),
.waterduck-browser[class*='dark-mode'] .action-button:hover,
.waterduck-browser[class*='dark-mode'] .new-tab-button:hover {
    background-color: #444;
}

.waterduck-browser[class*='dark-mode'] .browser-menu {
    background-color: #333;
    border-color: #444;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
}

.waterduck-browser[class*='dark-mode'] .menu-item:hover {
    background-color: #444;
}

.waterduck-browser[class*='dark-mode'] .quick-link-item:hover {
    background-color: #333;
}

.waterduck-browser[class*='dark-mode'] .site-icon {
    background-color: #333;
}

.waterduck-browser[class*='dark-mode'] .search-bar input,
.waterduck-browser[class*='dark-mode'] .home-search {
    background-color: #333;
    color: #e0e0e0;
    border-color: #444;
}

.waterduck-browser.standalone-mode {
    height: 100vh;
    border-radius: 0;
}

.browser-navigation {
    display: flex;
    padding: 8px 16px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    align-items: center;
}

.browser-controls {
    display: flex;
    margin-right: 12px;
}

.nav-button {
    background: none;
    border: none;
    border-radius: 4px;
    padding: 4px;
    margin-right: 4px;
    cursor: pointer;
    color: #555;
}

.nav-button:hover:not(:disabled) {
    background-color: #e0e0e0;
}

.nav-button:disabled {
    color: #ccc;
    cursor: not-allowed;
}

.address-bar {
    flex: 1;
    display: flex;
    align-items: center;
    background-color: var(--k-ink);
    border-radius: 24px;
    padding: 6px 12px;
    border: 1px solid #ddd;
}

.site-info {
    margin-right: 8px;
}

.address-bar input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 14px;
}

.browser-actions {
    margin-left: 12px;
    display: flex;
}

.action-button {
    background: none;
    border: none;
    border-radius: 4px;
    padding: 4px;
    margin-left: 4px;
    cursor: pointer;
    color: #555;
}

.action-button:hover {
    background-color: #e0e0e0;
}

.search-bar {
    display: flex;
    padding: 8px 16px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.search-bar input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px 0 0 4px;
    outline: none;
}

.search-button {
    background-color: #1976d2;
    color: var(--k-ink);
    border: none;
    border-radius: 0 4px 4px 0;
    padding: 0 12px;
    cursor: pointer;
}

.browser-menu {
    position: absolute;
    top: 56px;
    right: 16px;
    background-color: var(--k-ink);
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    cursor: pointer;
}

.menu-item:hover {
    background-color: #f5f5f5;
}

.menu-item i {
    margin-right: 8px;
}

.browser-tabs {
    display: flex;
    background-color: #e8e8e8;
    padding: 4px 4px 0 4px;
    overflow-x: auto;
    scrollbar-width: none; /* Firefox */
}

.browser-tabs::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge */
}

.browser-tab {
    display: flex;
    align-items: center;
    background-color: #f5f5f5;
    border-radius: 4px 4px 0 0;
    padding: 8px 12px;
    margin-right: 4px;
    min-width: 120px;
    max-width: 200px;
    cursor: pointer;
    border: 1px solid #e0e0e0;
    border-bottom: none;
    position: relative;
}

.browser-tab.active {
    background-color: var(--k-ink);
    z-index: 1;
}

.tab-content {
    display: flex;
    align-items: center;
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.loading-icon {
    margin-right: 4px;
}

.tab-icon {
    margin-right: 4px;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.tab-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.tab-title {
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

.tab-close {
    background: none;
    border: none;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
    cursor: pointer;
    opacity: 0.7;
}

.tab-close:hover {
    background-color: #e0e0e0;
    opacity: 1;
}

.new-tab-button {
    background: none;
    border: none;
    border-radius: 4px;
    padding: 8px;
    cursor: pointer;
    color: #555;
}

.browser-content {
    flex: 1;
    overflow: auto;
    position: relative;
    background-color: var(--k-ink);
}

.browser-home {
    padding: 48px 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.browser-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 32px;
}

.logo-image-duck {
    width: 156px;
    height: 156px;
    object-fit: contain;
}

.browser-logo h1 {
    margin-top: 12px;
    font-size: 28px;
    color: #1976d2;
}

.search-container {
    width: 100%;
    max-width: 600px;
    display: flex;
    margin-bottom: 48px;
}

.home-search {
    flex: 1;
    padding: 14px 24px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 24px 0 0 24px;
    outline: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.home-search-button {
    background-color: #1976d2;
    color: var(--k-ink);
    border: none;
    border-radius: 0 24px 24px 0;
    padding: 0 24px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.quick-links {
    width: 100%;
    max-width: 800px;
}

.quick-links h3 {
    margin-bottom: 16px;
    color: #333;
}

.link-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 16px;
}

.quick-link-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    border-radius: 8px;
    transition: background-color 0.2s;
    cursor: pointer;
}

.quick-link-item:hover {
    background-color: #f5f5f5;
}

.site-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
}

.site-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.site-title {
    font-size: 13px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    white-space: nowrap;
}

.browser-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    padding: 24px;
}

.browser-error h2 {
    margin: 16px 0;
    color: #d32f2f;
}

.error-back-button {
    margin-top: 16px;
    padding: 8px 16px;
    background-color: #1976d2;
    color: var(--k-ink);
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
</style>
