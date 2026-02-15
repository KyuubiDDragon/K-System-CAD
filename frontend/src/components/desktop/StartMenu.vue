<!-- Start Menu Component -->
<template>
    <div class="start-menu-overlay" @click="closeMenu">
        <div class="start-menu" @click.stop>
            <!-- User section -->
            <div class="menu-user-section">
                <div class="user-avatar">
                    <img :src="userAvatar" :alt="userName" />
                </div>
                <div class="user-info">
                    <div class="user-name">{{ userName }}</div>
                    <div class="user-status">{{ userStatus }}</div>
                </div>
            </div>

            <!-- Apps section -->
            <div class="menu-apps-section">
                <div class="section-title">{{ t('desktop.applications') }}</div>
                <div class="apps-grid">
                    <div
                        v-for="app in favoriteApps"
                        :key="app.id"
                        class="menu-app"
                        @click="launchApp(app)"
                    >
                        <div
                            class="menu-app-icon"
                            :style="{ backgroundColor: app.color || '#3b82f6' }"
                        >
                            <v-icon size="22" color="white">{{ app.icon }}</v-icon>
                        </div>
                        <div class="menu-app-title">{{ app.title }}</div>
                    </div>
                </div>
            </div>

            <!-- Tools & Utilities section -->
            <div class="menu-apps-section">
                <div class="section-title">{{ t('desktop.toolsUtilities') }}</div>
                <div class="apps-grid">
                    <div
                        v-for="app in toolsApps"
                        :key="app.id"
                        class="menu-app"
                        @click="launchApp(app)"
                    >
                        <div
                            class="menu-app-icon"
                            :style="{ backgroundColor: app.color || '#3b82f6' }"
                        >
                            <v-icon size="22" color="white">{{ app.icon }}</v-icon>
                        </div>
                        <div class="menu-app-title">{{ app.title }}</div>
                    </div>
                </div>
            </div>

            <!-- All apps list -->
            <div class="menu-list-section">
                <div class="section-title">{{ t('desktop.allApps') }}</div>
                <div class="apps-list">
                    <div
                        v-for="app in allApps"
                        :key="app.id"
                        class="list-app"
                        @click="launchApp(app)"
                    >
                        <div
                            class="list-app-icon"
                            :style="{ backgroundColor: app.color || '#3b82f6' }"
                        >
                            <v-icon size="16" color="white">{{ app.icon }}</v-icon>
                        </div>
                        <div class="list-app-title">{{ app.title }}</div>
                    </div>
                </div>
            </div>

            <!-- Bottom actions -->
            <div class="menu-actions">
                <!-- SCHNELLWECHSEL: Desktop → Sidebar -->
                <div class="action-button switch-button" @click="switchToSidebar">
                    <v-icon size="20">mdi-view-list</v-icon>
                    <span>{{ t('desktop.switchToSidebar') }}</span>
                </div>
                <div class="action-button logout-button" @click="logout">
                    <v-icon size="20">mdi-logout</v-icon>
                    <span>{{ t('desktop.logout') }}</span>
                </div>
                <div class="action-button close-button" @click="closeMenu">
                    <v-icon size="20">mdi-close</v-icon>
                    <span>{{ t('desktop.close') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';

const { t } = useI18n();
const router = useRouter();
const uiStore = useUIStore();

interface AppItem {
    id: string | number;
    title: string;
    icon: string;
    color?: string;
}

interface Props {
  apps?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  apps: () => []
});

const emit = defineEmits(['app-click', 'close']);

// Stores
const authStore = useAuthStore();

// User data
const userAvatar = computed(() => {
    return authStore.user?.avatar || 'https://via.placeholder.com/40';
});

const userName = computed(() => {
    return authStore.user?.name || authStore.user?.username || t('desktop.userPlaceholder');
});

const userStatus = computed(() => {
    return t('desktop.online');
});

// Filtered app lists
const favoriteApps = computed(() => {
    // Get all apps that are not tools
    const nonToolApps = props.apps.filter(app => 
        !['calculator', 'minesweeper', 'solitaire', 'sudoku'].includes(app.id)
    );
    // Return first 8 non-tool apps
    return nonToolApps.slice(0, 8);
});

// Tool apps (Calculator, Minesweeper, Solitaire, etc.) and Widgets
const toolsApps = computed(() => {
    return props.apps.filter(app =>
        ['calculator', 'minesweeper', 'solitaire', 'sudoku', 'whiteboard', 'quacklejump', 'weather', 'waterduck'].includes(app.id) ||
        app.parent === 'tools-group' || app.parent === 'widgets-group'
    );
});

const allApps = computed(() => {
    return [...props.apps].sort((a, b) => a.title.localeCompare(b.title));
});

// Actions
const launchApp = (app: AppItem) => {
    emit('app-click', app);
};

const closeMenu = () => {
    emit('close');
};

const logout = async () => {
    closeMenu();
    // Then logout
    await authStore.logout();
};

/**
 * ============================================
 * SCHNELLWECHSEL: Desktop → Sidebar
 * Ohne Logout! Sofortiger Wechsel!
 * ============================================
 */
const switchToSidebar = async () => {
    try {
        // 1. Menü schließen
        closeMenu();

        // 2. Desktop-Modus deaktivieren
        uiStore.setDesktopMode(false);

        // 3. Speichere Präferenz - WICHTIG für F5 Reload!
        await uiStore.saveLayoutPreference('sidebar');

        // 4. Navigiere zum Dashboard (Sidebar-Modus)
        await router.push('/dashboard');

        console.log('✅ Switched from Desktop to Sidebar mode');
    } catch (error) {
        console.error('❌ Failed to switch to sidebar mode:', error);
    }
};

</script>

<style scoped>
.start-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(var(--black-rgb), 0.5);
    z-index: 1000;
}

.start-menu {
    position: fixed;
    bottom: 60px;
    left: 12px;
    width: 500px;
    max-width: 90vw;
    max-height: 80vh;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.98), rgba(51, 65, 85, 0.95));
    backdrop-filter: blur(20px);
    overflow-y: auto;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 8px 16px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.15);
    z-index: 1001;
    animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: bottom left;
}

.start-menu-header {
    padding: 16px;
    background-color: rgba(var(--desktop-bg-dark-2), 0.7);
    border-bottom: 1px solid rgba(var(--white-rgb), 0.1);
}

.search-input {
    width: 100%;
    background-color: rgba(var(--desktop-bg-dark-1), 0.8);
    border: 2px solid var(--desktop-accent-blue);
    border-radius: 8px;
    color: var(--desktop-text);
    padding: 8px 16px;
    font-size: 16px;
    outline: none;
    transition: all 0.2s;
}

.user-profile {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    border: 2px solid var(--desktop-accent-blue);
    color: var(--on-primary);
    font-weight: bold;
}

.user-info {
    flex: 1;
}

.username {
    font-weight: 600;
    font-size: 16px;
    color: var(--desktop-text);
}

.email {
    font-size: 14px;
    color: var(--desktop-text-secondary);
}

.status-indicator {
    width: 24px;
    height: 24px;
    background-color: var(--success);
    border-radius: 50%;
    margin-left: 8px;
}

.menu-user-section {
    display: flex;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    position: relative;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #3b82f6;
    margin-right: 16px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-name {
    font-size: 16px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
    letter-spacing: 0.3px;
}

.user-status {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    font-weight: 500;
}

.user-status::before {
    content: '';
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #10b981;
    margin-right: 8px;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }
    50% { box-shadow: 0 0 12px rgba(16, 185, 129, 0.8); }
    100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }
}

.menu-apps-section {
    padding: 20px;
}

.section-title {
    font-size: 13px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    padding-bottom: 8px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    border-radius: 1px;
}

.apps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.menu-app {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    padding: 8px;
    border-radius: 12px;
}

.menu-app:hover {
    transform: translateY(-4px) scale(1.05);
    background-color: rgba(255, 255, 255, 0.05);
}

.menu-app-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.menu-app:hover .menu-app-icon {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3), 0 4px 8px rgba(0, 0, 0, 0.15);
}

.menu-app-title {
    font-size: 12px;
    color: white;
    text-align: center;
    max-width: 80px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
    letter-spacing: 0.2px;
}

.menu-list-section {
    padding: 0 16px 16px;
    flex: 1;
    overflow: hidden;
    max-height: 240px;
}

.apps-list {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 8px;
    max-height: 200px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) rgba(0, 0, 0, 0.2);
}

.apps-list::-webkit-scrollbar {
    width: 6px;
}

.apps-list::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
}

.apps-list::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.3);
    border-radius: 8px;
}

.list-app {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.list-app:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.list-app-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}

.list-app-title {
    font-size: 13px;
    color: white;
}

.menu-actions {
    display: flex;
    justify-content: center;
    gap: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(51, 65, 85, 0.6));
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0 0 16px 16px;
}

.action-button {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 10px;
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    min-width: 100px;
    justify-content: center;
}

.action-button:hover {
    background-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.2);
}

.switch-button:hover {
    background-color: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
    color: #93c5fd;
}

.logout-button:hover {
    background-color: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.close-button:hover {
    background-color: rgba(107, 114, 128, 0.2);
    border-color: rgba(107, 114, 128, 0.3);
}

.action-button span {
    margin-left: 8px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 480px) {
    .start-menu {
        width: calc(100% - 32px);
    }

    .apps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
