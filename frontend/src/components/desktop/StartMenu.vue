<!-- Start Menu Component -->
<template>
    <div class="start-menu-overlay" @click="closeMenu">
        <div class="start-menu" @click.stop>
            <!--
                Kopfzeile: wer angemeldet ist, und das Suchfeld.

                Die Suche stand vorher nur im Stylesheet, im Markup gab es sie
                nicht. Bei ueber dreissig Programmen ist Tippen aber der
                schnellste Weg - der Entwurf sagt dasselbe ueber die
                Befehlspalette: "Ein Tastendruck, ein Feld."
            -->
            <header class="menu-head">
                <div class="menu-user">
                    <div class="user-avatar">
                        <img v-if="userAvatar" :src="userAvatar" :alt="userName" />
                        <span v-else>{{ userInitialen }}</span>
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ userName }}</div>
                        <div class="user-status">{{ userStatus }}</div>
                    </div>
                </div>

                <label class="menu-search">
                    <v-icon size="16">mdi-magnify</v-icon>
                    <input
                        ref="sucheRef"
                        v-model="suche"
                        type="text"
                        :placeholder="t('desktop.searchApps')"
                        @keydown.esc="suche ? (suche = '') : closeMenu()"
                        @keydown.enter="ersterTreffer && launchApp(ersterTreffer)"
                    />
                    <kbd v-if="!suche">ESC</kbd>
                </label>
            </header>

            <div class="menu-body">
                <!-- Angeheftet: die Programme, die man taeglich braucht. -->
                <template v-if="!suche">
                    <p class="menu-group">{{ t('desktop.applications') }}</p>
                    <div class="menu-grid">
                        <button
                            v-for="app in favoriteApps"
                            :key="app.id"
                            type="button"
                            class="menu-tile"
                            @click="launchApp(app)"
                        >
                            <span class="tile-icon" :style="{ color: app.color || 'var(--k-accent)' }">
                                <v-icon size="26">{{ app.icon }}</v-icon>
                            </span>
                            <span class="tile-title">{{ app.title }}</span>
                        </button>
                    </div>
                </template>

                <!--
                    Alle Programme als Liste. Bei einer Suche steht nur sie da -
                    die Kacheln oben waeren dann eine zweite Antwort auf
                    dieselbe Frage.
                -->
                <p class="menu-group">
                    {{ suche ? t('desktop.searchResults') : t('desktop.allApps') }}
                    <span class="menu-count">{{ gefilterteApps.length }}</span>
                </p>
                <div class="menu-list">
                    <button
                        v-for="app in gefilterteApps"
                        :key="app.id"
                        type="button"
                        class="menu-row"
                        @click="launchApp(app)"
                    >
                        <v-icon size="17" :style="{ color: app.color || 'var(--k-accent)' }">
                            {{ app.icon }}
                        </v-icon>
                        <span>{{ app.title }}</span>
                    </button>
                    <p v-if="!gefilterteApps.length" class="menu-leer">
                        {{ t('desktop.noAppsFound', { q: suche }) }}
                    </p>
                </div>
            </div>

            <!-- Fusszeile: die drei Wege hinaus. -->
            <footer class="menu-actions">
                <button type="button" class="action-button" @click="switchToSidebar">
                    <v-icon size="16">mdi-view-list</v-icon>
                    <span>{{ t('desktop.switchToSidebar') }}</span>
                </button>
                <button type="button" class="action-button" @click="closeMenu">
                    <v-icon size="16">mdi-close</v-icon>
                    <span>{{ t('desktop.close') }}</span>
                </button>
                <button type="button" class="action-button is-danger" @click="logout">
                    <v-icon size="16">mdi-logout</v-icon>
                    <span>{{ t('desktop.logout') }}</span>
                </button>
            </footer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue';
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
    // Kein Ersatzbild von einem fremden Dienst: via.placeholder.com wurde
    // blockiert bzw. war nicht erreichbar, sodass das kaputte Bild samt
    // Alternativtext im Menue stand. Ohne Bild uebernehmen die Initialen.
    return authStore.user?.avatar || '';
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
/** Suchbegriff des Startmenues. */
const suche = ref('');
const sucheRef = ref<HTMLInputElement | null>(null);

/** Beim Oeffnen liegt der Schreibzeiger im Feld - Tippen ist der schnellste Weg. */
onMounted(async () => {
    await nextTick();
    sucheRef.value?.focus();
});

/** Kuerzel, solange kein Bild hinterlegt ist. */
const userInitialen = computed(() =>
    String(userName.value || '?')
        .split(/\s+/)
        .slice(0, 2)
        .map(w => w.charAt(0).toUpperCase())
        .join(''),
);

const gefilterteApps = computed(() => {
    const q = suche.value.trim().toLowerCase();
    const alle = allApps.value as AppItem[];
    if (!q) return alle;
    return alle.filter(a => String(a.title ?? '').toLowerCase().includes(q));
});

const ersterTreffer = computed<AppItem | null>(() => gefilterteApps.value[0] ?? null);

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
/* ============================================================
   STARTMENUE

   Groesse nach dem Vorbild, das die Leute kennen: Windows und
   macOS geben ihrem Menue reichlich Platz. Die vorherige Fassung
   war auf die 34-px-Leiste geschrumpft und wirkte gedrungen.

   Gestalt aus dem Entwurf: gehobene Flaeche, ein Rahmen, ein
   Schatten. Kein Verlauf, kein Weichzeichner, Radius 8.
   ============================================================ */
.start-menu-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(10, 14, 20, 0.45);
    z-index: 1000;
}

.start-menu {
    position: fixed;
    bottom: 56px;
    left: 12px;
    width: 560px;
    max-width: calc(100vw - 24px);
    max-height: min(680px, calc(100vh - 80px));
    display: flex;
    flex-direction: column;
    background: var(--k-raised);
    border: 1px solid var(--k-line-strong);
    border-radius: 8px;
    box-shadow:
        0 16px 40px rgba(16, 22, 32, 0.16),
        0 2px 8px rgba(16, 22, 32, 0.08);
    z-index: 1001;
    overflow: hidden;
    animation: slideUp 150ms cubic-bezier(0.16, 1, 0.3, 1);
    transform-origin: bottom left;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .start-menu { animation: none; }
}

/* ---- Kopfzeile ---- */
.menu-head {
    flex: none;
    padding: 12px 14px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
}

.menu-user {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.user-avatar {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 50%;
    overflow: hidden;
    background: var(--k-accent);
    color: var(--k-on-fill);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12.5px;
    font-weight: 650;
}

.user-avatar img { width: 100%; height: 100%; object-fit: cover; }

.user-name {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--k-ink);
}

.user-status {
    font-size: 11.5px;
    color: var(--k-ink-muted);
}

/* Suchfeld: dieselbe Gestalt wie jedes Eingabefeld im System. */
.menu-search {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 32px;
    padding: 0 10px;
    background: var(--k-surface);
    border: 1px solid var(--k-line-strong);
    border-radius: 5px;
    color: var(--k-ink-faint);
}

.menu-search:focus-within {
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px var(--k-accent-weak);
}

.menu-search input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--k-ink);
    font: inherit;
    font-size: 13px;
}

.menu-search input::placeholder { color: var(--k-ink-faint); }

.menu-search kbd {
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 10px;
    border: 1px solid var(--k-line-strong);
    border-radius: 3px;
    padding: 0 4px;
    color: var(--k-ink-faint);
    background: var(--k-sunken);
    line-height: 15px;
}

/* ---- Inhalt ---- */
.menu-body {
    flex: 1;
    overflow-y: auto;
    padding: 4px 8px 8px;
}

.menu-group {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--k-ink-faint);
    margin: 12px 0 6px;
    padding: 0 6px;
}

.menu-count {
    font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 10.5px;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0;
    color: var(--k-ink-faint);
}

/* Angeheftete Programme als Kacheln. */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 4px;
}

.menu-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    padding: 12px 6px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    font: inherit;
    transition: background-color 120ms ease;
}

.menu-tile:hover { background: var(--k-row-hover); }

.tile-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
}

.tile-title {
    font-size: 11.5px;
    line-height: 1.25;
    color: var(--k-ink);
    text-align: center;
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Alle Programme als Liste - 30 px Zeile wie im Dichte-System. */
.menu-list { display: flex; flex-direction: column; }

.menu-row {
    display: flex;
    align-items: center;
    gap: 10px;
    height: 30px;
    padding: 0 8px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: var(--k-ink);
    font: inherit;
    font-size: 12.5px;
    text-align: left;
    cursor: pointer;
    transition: background-color 120ms ease;
}

.menu-row:hover { background: var(--k-row-hover); }

.menu-leer {
    font-size: 12.5px;
    color: var(--k-ink-muted);
    padding: 10px 8px;
    margin: 0;
}

/* ---- Fusszeile ---- */
.menu-actions {
    flex: none;
    display: flex;
    gap: 8px;
    padding: 10px 12px;
    background: var(--k-sunken);
    border-top: 1px solid var(--k-line);
}

.action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex: 1;
    height: 30px;
    padding: 0 12px;
    border: 1px solid var(--k-line-strong);
    border-radius: 5px;
    background: var(--k-surface);
    color: var(--k-ink);
    font: inherit;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 120ms ease, border-color 120ms ease;
}

.action-button:hover { background: var(--k-row-hover); }

/* Abmelden wird erst beim Zeigen rot - so schlaegt es nicht dauernd Alarm. */
.action-button.is-danger { color: var(--k-critical); }
.action-button.is-danger:hover {
    background: var(--k-critical-weak);
    border-color: var(--k-critical);
}

.action-button:focus-visible {
    outline: 2px solid var(--k-accent);
    outline-offset: 1px;
}
</style>
