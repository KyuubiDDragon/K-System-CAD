<template>
    <div class="desktop-loading-screen" :class="{ 'fade-out': fadeOut }">
        <div class="loading-content">
            <!-- Logo/Icon -->
            <div class="loading-logo">
                <img src="/img/waterduck.png" alt="K-Systems" class="logo-image" />
            </div>

            <!-- Loading Text -->
            <div class="loading-text">
                <h2>{{ loadingTitle }}</h2>
                <p v-if="loadingMessage">{{ loadingMessage }}</p>
            </div>

            <!-- Windows-style Loading Spinner -->
            <div class="loading-spinner">
                <div class="spinner-dots">
                    <span v-for="i in 5" :key="i" class="dot" :style="{ animationDelay: `${i * 0.15}s` }"></span>
                </div>
            </div>

            <!-- Optional Progress Text -->
            <div v-if="progressText" class="progress-text">
                {{ progressText }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    loadingTitle?: string;
    loadingMessage?: string;
    progressText?: string;
    autoHide?: boolean;
    hideDelay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    loadingTitle: 'K-Systems',
    loadingMessage: '',
    progressText: '',
    autoHide: false,
    hideDelay: 2000
});

const fadeOut = ref(false);

onMounted(() => {
    if (props.autoHide) {
        setTimeout(() => {
            fadeOut.value = true;
        }, props.hideDelay);
    }
});
</script>

<style scoped>
.desktop-loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    opacity: 1;
    transition: opacity 0.8s ease-in-out;
}

.desktop-loading-screen.fade-out {
    opacity: 0;
    pointer-events: none;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 32px;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loading-logo {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    animation: logoPulse 2s ease-in-out infinite;
}

@keyframes logoPulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 12px 40px rgba(59, 130, 246, 0.3);
    }
}

.logo-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

.loading-text {
    text-align: center;
    color: white;
}

.loading-text h2 {
    font-size: 28px;
    font-weight: 300;
    margin: 0 0 12px 0;
    letter-spacing: 1px;
    color: #f8fafc;
}

.loading-text p {
    font-size: 16px;
    margin: 0;
    color: #cbd5e1;
    font-weight: 300;
}

.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 40px;
}

.spinner-dots {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    animation: dotBounce 1.4s ease-in-out infinite;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

@keyframes dotBounce {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1.2);
        opacity: 1;
    }
}

.progress-text {
    font-size: 14px;
    color: #94a3b8;
    font-weight: 300;
    text-align: center;
    min-height: 20px;
    animation: fadeInOut 1.5s ease-in-out infinite;
}

@keyframes fadeInOut {
    0%, 100% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
}

/* Dark theme specific (already dark by default) */
:deep(.theme-dark) .desktop-loading-screen {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
}

/* Light theme adjustments if needed */
:deep(.theme-light) .desktop-loading-screen {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 50%, #cbd5e1 100%);
}

:deep(.theme-light) .loading-text h2,
:deep(.theme-light) .loading-text p {
    color: #1e293b;
}

:deep(.theme-light) .loading-logo {
    background: rgba(255, 255, 255, 0.8);
    border-color: rgba(0, 0, 0, 0.1);
}

:deep(.theme-light) .progress-text {
    color: #475569;
}
</style>