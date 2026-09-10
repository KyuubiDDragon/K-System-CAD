<template>
    <div class="background-selector">
        <v-btn
            class="selector-toggle"
            icon="mdi-image-outline"
            variant="tonal"
            size="small"
            @click="isOpen = !isOpen"
            :title="t('layout.chooseBackground')"
        ></v-btn>

        <div class="background-panel" v-if="isOpen">
            <div class="panel-header">
                <span>{{ t('layout.selectBackground') }}</span>
                <v-btn
                    icon="mdi-close"
                    size="x-small"
                    variant="text"
                    @click="isOpen = false"
                ></v-btn>
            </div>

            <div class="background-options">
                <div
                    v-for="bg in backgrounds"
                    :key="bg.path"
                    class="background-option"
                    :class="{ active: currentBackground === bg.path }"
                    @click="selectBackground(bg.path)"
                >
                    <img :src="bg.thumbnail" :alt="bg.name" />
                    <span class="option-name" :class="{ 'is-default': bg.isDefault }">
                        {{ bg.name }}
                        <v-icon v-if="bg.isDefault" size="16" class="ml-1">mdi-star</v-icon>
                    </span>
                </div>

                <div class="background-option upload" :class="{ uploading: isLoading }">
                    <label for="bg-upload" class="upload-label" v-if="!isLoading">
                        <v-icon icon="mdi-upload" size="24"></v-icon>
                        <span>{{ t('layout.customImage') }}</span>
                    </label>
                    <div v-else class="upload-progress">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                        <span>{{ t('layout.uploading') }}</span>
                    </div>
                    <input
                        type="file"
                        id="bg-upload"
                        accept="image/*"
                        @change="uploadBackground"
                        class="upload-input"
                        :disabled="isLoading"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api'; // API Client importieren
import { useToast } from 'vue-toastification'; // Import für Toast-Benachrichtigungen
import { useAuthStore } from '@/stores/auth';

const toast = useToast();
const emit = defineEmits(['background-change']);
const { t } = useI18n();
const authStore = useAuthStore();

// States
const isOpen = ref(false);
const currentBackground = ref('');
const isLoading = ref(false);

// Get default background based on authority
const getAuthorityDefaultBackground = () => {
    const authorityBranding = authStore.user?.authority_branding;
    if (authorityBranding?.default_background) {
        return authorityBranding.default_background;
    }
    
    // Fallback based on authority name
    const authorityName = authStore.user?.authority?.toLowerCase() || '';
    if (authorityName.includes('lsfd') || authorityName.includes('fire')) {
        return '/img/bg2.png';
    } else if (authorityName.includes('lspd') || authorityName.includes('police')) {
        return '/img/bg3.png';
    }
    
    return '/img/bg.jpg';
};

// Vordefinierte Hintergrundbilder + Authority Default
const backgrounds = ref([
    {
        name: t('layout.authorityDefault'),
        path: getAuthorityDefaultBackground(),
        thumbnail: getAuthorityDefaultBackground(),
        isDefault: true,
    },
    {
        name: 'Standard',
        path: '/img/bg.jpg',
        thumbnail: '/img/bg.jpg',
    },
    {
        name: 'Hintergrund 2',
        path: '/img/bg2.png',
        thumbnail: '/img/bg2.png',
    },
    {
        name: 'Hintergrund 3',
        path: '/img/bg3.png',
        thumbnail: '/img/bg3.png',
    },
]);

// Initialen Hintergrund aus localStorage laden oder Authority Default setzen
onMounted(() => {
    const savedBg = localStorage.getItem('desktop-background');
    currentBackground.value = savedBg || getAuthorityDefaultBackground();
    emit('background-change', currentBackground.value);
});

// Hintergrundbild auswählen
const selectBackground = (path: string) => {
    currentBackground.value = path;
    localStorage.setItem('desktop-background', path);
    emit('background-change', path);
};

// Eigenes Hintergrundbild hochladen
const uploadBackground = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Bild zur API senden statt es als Base64 im localStorage zu speichern
        const formData = new FormData();
        formData.append('background', file);

        // Upload-Status anzeigen
        isLoading.value = true;

        // API-Anfrage zum Hochladen des Bildes
        apiClientAuth
            .post('/desktop/?action=uploadBackground', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then(response => {
                // Nach erfolgreichen Upload den zurückgegebenen Pfad verwenden
                if (response.data && response.data.success && response.data.path) {
                    // Setze einen Cache-Busting-Flag im localStorage
                    localStorage.setItem('force_refresh_theme', 'true');
                    
                    // Cache für das alte Bild löschen
                    if (currentBackground.value) {
                        // Versuche, das alte Bild aus dem Cache zu entfernen
                        const cacheNames = ['background-images-cache', 'image-cache'];
                        if ('caches' in window) {
                            cacheNames.forEach(cacheName => {
                                caches.open(cacheName).then(cache => {
                                    cache.delete(currentBackground.value).then(() => {
                                        console.log(`Bild aus Cache ${cacheName} entfernt`);
                                    });
                                });
                            });
                        }
                    }
                    
                    // Hintergrundbild anwenden mit Timestamp, um Cache-Probleme zu vermeiden
                    const path = `${response.data.path}?t=${Date.now()}`;
                    currentBackground.value = response.data.path;
                    localStorage.setItem('desktop-background', response.data.path);
                    emit('background-change', response.data.path);
                    
                    console.log('Hintergrundbild erfolgreich aktualisiert:', path);
                } else {
                    console.error('Fehler beim Hochladen des Hintergrundbilds:', response.data);
                    toast.error(
                        t('layout.uploadError')
                    );
                }
            })
            .catch(error => {
                console.error('Fehler beim Hochladen des Hintergrundbilds:', error);
                toast.error(
                    t('layout.uploadError')
                );
            })
            .finally(() => {
                isLoading.value = false;
            });
    }
};
</script>

<style scoped>
.background-selector {
    position: absolute;
    bottom: calc(var(--taskbar-height) + var(--desktop-padding));
    left: var(--desktop-padding);
    z-index: 7;
}

.selector-toggle {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--k-line);
}

.background-panel {
    position: absolute;
    bottom: 100%;
    left: 0;
    width: 320px;
    background: rgba(var(--desktop-bg-dark-1), var(--glass-bg-opacity));
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-large);
    border: 1px solid var(--desktop-border);
    margin-bottom: 8px;
    animation: panel-slide-up 0.3s var(--animation-easing);
    transform-origin: bottom left;
    overflow: hidden;
}

@keyframes panel-slide-up {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid var(--desktop-border);
    font-weight: 500;
}

.background-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 16px;
    max-height: 360px;
    overflow-y: auto;
}

.background-option {
    position: relative;
    border-radius: var(--border-radius-sm);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.background-option:hover {
    transform: scale(1.03);
}

.background-option.active {
    border-color: var(--desktop-accent-blue);
}

.background-option img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    display: block;
}

.option-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 6px 8px;
    background: rgba(0, 0, 0, 0.7);
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
}

.option-name.is-default {
    background: var(--k-surface);
    color: #000;
    font-weight: 600;
}

.background-option.upload {
    height: 80px;
    background: var(--k-row-hover);
    display: flex;
    justify-content: center;
    align-items: center;
    border: 1px dashed rgba(255, 255, 255, 0.3);
}

.background-option.upload.uploading {
    background: rgba(0, 0, 0, 0.2);
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    width: 100%;
    height: 100%;
    justify-content: center;
}

.upload-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.upload-input {
    display: none;
}
</style>
