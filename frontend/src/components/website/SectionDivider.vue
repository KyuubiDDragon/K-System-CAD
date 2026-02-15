<template>
    <div class="section-divider" :class="[`divider-${position}`, { 'divider-flip': flip }]">
        <svg
            v-if="shape !== 'none'"
            :viewBox="getViewBox()"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
            :style="svgStyle"
        >
            <!-- Wave -->
            <path v-if="shape === 'wave'" :fill="color" d="M0,32L48,37.3C96,43,192,53,288,58.7C384,64,480,64,576,58.7C672,53,768,43,864,48C960,53,1056,75,1152,80C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>

            <!-- Tilt -->
            <path v-if="shape === 'tilt'" :fill="color" d="M0,0 L1440,160 L1440,320 L0,320 Z"></path>

            <!-- Curve -->
            <path v-if="shape === 'curve'" :fill="color" d="M0,160 Q720,64 1440,160 L1440,320 L0,320 Z"></path>

            <!-- Triangle -->
            <path v-if="shape === 'triangle'" :fill="color" d="M0,320 L720,0 L1440,320 Z"></path>

            <!-- Zigzag -->
            <path v-if="shape === 'zigzag'" :fill="color" d="M0,160 L240,80 L480,160 L720,80 L960,160 L1200,80 L1440,160 L1440,320 L0,320 Z"></path>

            <!-- Mountains -->
            <path v-if="shape === 'mountains'" :fill="color" d="M0,320 L0,160 L240,64 L480,160 L720,32 L960,160 L1200,96 L1440,160 L1440,320 Z"></path>

            <!-- Waves with Opacity (Multiple Waves) -->
            <g v-if="shape === 'waves-opacity'">
                <path :fill="color" opacity="0.3" d="M0,160 Q360,96 720,160 T1440,160 L1440,320 L0,320 Z"></path>
                <path :fill="color" opacity="0.5" d="M0,192 Q360,128 720,192 T1440,192 L1440,320 L0,320 Z"></path>
                <path :fill="color" d="M0,224 Q360,160 720,224 T1440,224 L1440,320 L0,320 Z"></path>
            </g>

            <!-- Arrow -->
            <path v-if="shape === 'arrow'" :fill="color" d="M0,0 L720,160 L1440,0 L1440,320 L0,320 Z"></path>

            <!-- Split/Torn Paper Effect -->
            <path v-if="shape === 'split'" :fill="color" d="M0,160 L120,140 L240,170 L360,145 L480,175 L600,150 L720,180 L840,155 L960,175 L1080,160 L1200,185 L1320,165 L1440,180 L1440,320 L0,320 Z"></path>

            <!-- Clouds -->
            <g v-if="shape === 'clouds'">
                <ellipse :fill="color" cx="100" cy="180" rx="100" ry="60" opacity="0.9"/>
                <ellipse :fill="color" cx="200" cy="190" rx="120" ry="70" opacity="0.85"/>
                <ellipse :fill="color" cx="350" cy="185" rx="90" ry="55" opacity="0.9"/>
                <ellipse :fill="color" cx="500" cy="195" rx="130" ry="75" opacity="0.8"/>
                <ellipse :fill="color" cx="650" cy="180" rx="100" ry="60" opacity="0.9"/>
                <ellipse :fill="color" cx="800" cy="190" rx="110" ry="65" opacity="0.85"/>
                <ellipse :fill="color" cx="950" cy="185" rx="95" ry="58" opacity="0.9"/>
                <ellipse :fill="color" cx="1100" cy="193" rx="125" ry="72" opacity="0.8"/>
                <ellipse :fill="color" cx="1250" cy="188" rx="105" ry="63" opacity="0.9"/>
                <ellipse :fill="color" cx="1380" cy="192" rx="115" ry="68" opacity="0.85"/>
                <rect :fill="color" x="0" y="220" width="1440" height="100"/>
            </g>

            <!-- Book/Pages Wave -->
            <path v-if="shape === 'book'" :fill="color" d="M0,160 Q240,100 480,160 T960,160 Q1200,100 1440,160 L1440,180 Q1200,120 960,180 T480,180 Q240,120 0,180 L0,320 L1440,320 L1440,180 Z"></path>

            <!-- Lightning/Electric -->
            <path v-if="shape === 'lightning'" :fill="color" d="M0,200 L180,140 L160,180 L360,100 L320,160 L540,80 L480,150 L720,60 L660,140 L900,80 L840,160 L1080,100 L1020,180 L1260,120 L1200,200 L1440,140 L1440,320 L0,320 Z"></path>

            <!-- Pyramids -->
            <path v-if="shape === 'pyramids'" :fill="color" d="M0,320 L0,200 L144,80 L288,200 L432,120 L576,200 L720,60 L864,200 L1008,140 L1152,200 L1296,100 L1440,200 L1440,320 Z"></path>

            <!-- Slime/Dripping -->
            <path v-if="shape === 'slime'" :fill="color" d="M0,160 Q60,100 120,160 T240,160 Q300,120 360,160 T480,160 Q540,90 600,160 T720,160 Q780,110 840,160 T960,160 Q1020,95 1080,160 T1200,160 Q1260,105 1320,160 T1440,160 L1440,320 L0,320 Z"></path>
        </svg>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    shape: string;
    position: 'top' | 'bottom';
    color?: string;
    flip?: boolean;
    height?: number;
}

const props = withDefaults(defineProps<Props>(), {
    shape: 'none',
    position: 'bottom',
    color: 'currentColor',
    flip: false,
    height: 80
});

function getViewBox(): string {
    // Standard viewBox for all shapes
    return '0 0 1440 320';
}

const svgStyle = computed(() => ({
    height: `${props.height}px`
}));
</script>

<style scoped>
.section-divider {
    position: absolute;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    z-index: 1;
}

.divider-top {
    top: 0;
}

.divider-bottom {
    bottom: 0;
}

.section-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    /* Height is now dynamically set via inline style */
}

.divider-flip svg {
    transform: scaleX(-1);
}
</style>
