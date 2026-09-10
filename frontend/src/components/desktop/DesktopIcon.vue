<template>
    <div
        class="desktop-icon"
        :class="{ 
            'is-folder': isFolder, 
            'is-dragging': isDragging, 
            'selected': selected,
            'is-image': isImageIcon
        }"
        @click="onClick"
        @mousedown="startDrag"
        @dblclick="$emit('dblclick')"
        :style="iconStyle"
        ref="iconRef"
    >
        <div class="icon-container">
            <div class="icon-glow"></div>
            <v-icon 
                v-if="isFolder" 
                icon="mdi-folder" 
                :color="color" 
                size="large" 
                class="icon-image folder-icon"
            /> 
            <img
                v-else-if="isImageIcon"
                :src="icon.icon"
                class="custom-icon-image"
                alt="Icon"
            />
            <v-icon 
                v-else 
                :icon="iconClass"  
                :color="color" 
                size="large" 
                class="icon-image" 
            />
            <div v-if="isFolder && hasItems" class="folder-indicator"></div>
        </div>
        <div class="icon-title">{{ title }}</div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted, nextTick } from 'vue';
// Use require for image import instead of ES module import
const duckLogoPath = '/img/waterduck.png';

interface Props {
    // Enthält den Icon-Namen (z.B. 'mdi-calculator')
    icon: Record<string, any>; // Beispielstruktur, die vom Parent kommt (Option 1): { icon: 'mdi-...', position: {x,y} }
    scale?: number;
    title: string;
    color: string;
    isFolder?: boolean;
    selected?: boolean;
    // Die Position wird als separate Prop übergeben
    position: { x: number; y: number };
    snapToGrid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    scale: 1,
    isFolder: false,
    selected: false,
    snapToGrid: false,
});

const emit = defineEmits(['click', 'position-change', 'select', 'dblclick']);

// Refs
const iconRef = ref<HTMLDivElement | null>(null);
const isDragging = ref(false);

// Interner reaktiver State für die Position.
// Wird für Styles und Drag&Drop verwendet. Initialisiert mit Prop-Wert.
const currentPosition = ref({ ...props.position });

// --- WICHTIG: Watcher für die Prop ---
// Beobachtet die 'position'-Prop von der Elternkomponente.
watch(() => props.position, (newPos) => {
    if (!isDragging.value) {
        // Entferne den nextTick Wrapper
        if (newPos && typeof newPos === 'object' && 'x' in newPos && 'y' in newPos) {
            currentPosition.value = { ...newPos };
            // console.log(`Watcher (direct update) for ${props.title}:`, currentPosition.value); // Debug log
        } else {
            // console.log(`Watcher received invalid newPos for ${props.title}:`, newPos); // Debug log
        }
    }
}, { deep: true, immediate: true });

// --- Computed Properties ---
const iconStyle = computed(() => {
    return {
        transform: isDragging.value ? 'scale(1.05)' : `scale(${props.scale})`, // Verwende scale prop
        // Verwende den reaktiven internen State 'currentPosition'
        left: `${currentPosition.value.x}px`,
        top: `${currentPosition.value.y}px`,
        zIndex: isDragging.value ? 10 : 5, // Icon beim Ziehen nach vorne bringen
        '--icon-color': props.color
    };
});

// Gibt den MDI-Icon-Namen zurück oder prüft auf Bildpfad
const iconClass = computed(() => {
    // Greift auf die 'icon'-Property innerhalb des 'icon'-Objekt-Props zu
    const iconValue = props.icon?.icon || 'mdi-file-question-outline';
    
    // Wenn es ein Bild-Icon ist (beginnt mit 'img:'), leere Zeichenkette zurückgeben
    // Das eigentliche Bild wird über das img-Tag angezeigt
    return isImageIcon.value ? '' : iconValue;
});

// Prüft, ob es sich um ein Bild-Icon handelt
const isImageIcon = computed(() => {
    const iconPath = props.icon?.icon || '';
    return typeof iconPath === 'string' && 
           (iconPath.startsWith('/') || iconPath.includes('.png') || 
            iconPath.includes('.jpg') || iconPath.includes('.jpeg') || 
            iconPath.includes('.svg'));
});

// Gibt den Bildpfad für Bild-Icons zurück
const imageIconSrc = computed(() => {
    if (!isImageIcon.value) return '';
    
    // Simply return the icon path without any processing
    return props.icon?.icon || '';
});

// Prüft, ob ein Ordner Items hat (Logik basierend auf deinen Daten anpassen)
const hasItems = computed(() => {
    // return props.isFolder && props.icon.items && props.icon.items.length > 0; 
    return false; // Placeholder, da 'items' nicht übergeben werden
});

// --- Event Handlers ---
let wasDragging = false; // Flag, um Klick nach Drag zu verhindern

const onClick = (event: MouseEvent) => {
    if (!wasDragging) { 
        emit('click', event); 
    }
    emit('select'); 
    
    setTimeout(() => {
        wasDragging = false;
    }, 50); 
};

// --- Drag Functionality ---
let dragStartX = 0;
let dragStartY = 0;
let initialDragX = 0; // Position zu Beginn des Ziehens
let initialDragY = 0;
const gridSize = 20; // Raster-Größe für Snap-to-Grid

// Ghost preview element for drag feedback
const ghostPreview = ref<{ x: number; y: number; show: boolean }>({
    x: 0,
    y: 0,
    show: false
});

const startDrag = (event: MouseEvent) => {
    if (event.button !== 0) return; // Nur linke Maustaste
    event.preventDefault(); // Verhindert Standard-Drag-Verhalten (z.B. Textauswahl)
    emit('select'); // Icon auswählen

    dragStartX = event.clientX;
    dragStartY = event.clientY;
    // Ziehen beginnt von der aktuellen Position
    initialDragX = currentPosition.value.x;
    initialDragY = currentPosition.value.y;
    wasDragging = false; // Zurücksetzen

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag, { once: true }); // Listener wird automatisch entfernt
};

const onDrag = (event: MouseEvent) => {
    event.preventDefault();
    const deltaX = event.clientX - dragStartX;
    const deltaY = event.clientY - dragStartY;

    // Dragging nur bei ausreichender Bewegung
    if (!isDragging.value && (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3)) {
        isDragging.value = true;
        ghostPreview.value.show = true;
    }
    if (!isDragging.value) return; // Nicht updaten, wenn nicht aktiv gezogen wird

    let newX = initialDragX + deltaX;
    let newY = initialDragY + deltaY;

    // Always snap to grid during drag for smooth movement
    // Hold Ctrl to disable snap-to-grid temporarily
    if (!event.ctrlKey) {
        newX = Math.round(newX / gridSize) * gridSize;
        newY = Math.round(newY / gridSize) * gridSize;
    }

    // --- Kollisionserkennung / Begrenzung auf Container ---
    const parentElement = iconRef.value?.parentElement; // Container (.desktop-icons)
    if (parentElement && iconRef.value) {
        const parentRect = parentElement.getBoundingClientRect();
        // Verwende offsetWidth/Height für die tatsächliche Größe des Icons
        const iconWidth = iconRef.value.offsetWidth; 
        const iconHeight = iconRef.value.offsetHeight;

        // Begrenzung links/oben
        if (newX < 0) newX = 0;
        if (newY < 0) newY = 0;

        // Begrenzung rechts/unten (berücksichtigt Icon-Größe)
        if (newX + iconWidth > parentRect.width) {
            newX = parentRect.width - iconWidth;
        }
        // Annahme: Parent-Höhe ist korrekt, keine Taskleiste im Weg
        const availableHeight = parentRect.height; 
        if (newY + iconHeight > availableHeight) {
             newY = availableHeight - iconHeight;
        }
        // Sicherstellen, dass y nicht negativ wird, falls Berechnung fehlschlägt
         if (newY < 0) newY = 0; 
    }
    
    // Aktualisiere den reaktiven internen Positions-State
    currentPosition.value = { x: newX, y: newY };
};

const stopDrag = () => {
    if (isDragging.value) {
        wasDragging = true; // Klick nach Loslassen verhindern

        // Final snap to grid when releasing
        let finalX = currentPosition.value.x;
        let finalY = currentPosition.value.y;

        // Snap to grid on release
        finalX = Math.round(finalX / gridSize) * gridSize;
        finalY = Math.round(finalY / gridSize) * gridSize;

        currentPosition.value = { x: finalX, y: finalY };

        // Emittiere die finale Position aus dem internen State
        emit('position-change', { ...currentPosition.value });
    }

    isDragging.value = false; // Wichtig: Nach dem Emit zurücksetzen
    ghostPreview.value.show = false; // Hide ghost preview

    // Entferne Mousemove-Listener (Mouseup wird durch 'once: true' entfernt)
    document.removeEventListener('mousemove', onDrag);
};

// --- Lifecycle Hooks ---
onUnmounted(() => {
    // Cleanup der Event Listener, falls sie aus irgendeinem Grund noch existieren
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
});
</script>

<style scoped>
.desktop-icon {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    border-radius: 6px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    user-select: none;
    width: 110px;
    height: 130px;
    box-sizing: border-box;
    /* Die Kachel selbst bleibt unsichtbar - sie traegt nur Symbol und Namen.
       Vorher lag hier ein fest verdrahtetes Dunkelblau mit Weichzeichner, das
       im hellen Modus als graue Platte auf dem Hintergrundbild stand. */
    background: transparent;
    border: 1px solid transparent;
    box-shadow:
        0 4px 12px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.desktop-icon:hover {
    background: var(--k-raised);
    box-shadow:
        0 8px 20px rgba(0, 0, 0, 0.25),
        0 4px 8px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    border: 1px solid var(--k-line);
    transform: translateY(-4px) scale(1.02);
}

.desktop-icon.selected {
    background: var(--k-accent-weak);
    border: 1px solid var(--k-accent-line);
    box-shadow: none;
}

.desktop-icon.is-dragging {
    opacity: 0.95;
    transform: scale(1.08) !important;
    box-shadow:
        0 25px 40px rgba(0, 0, 0, 0.35),
        0 12px 20px rgba(0, 0, 0, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    cursor: grabbing;
    background: var(--k-raised);
    border: 1px solid var(--k-accent-line);
    z-index: 10 !important;
}

.icon-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 64px;
    height: 64px;
    margin-bottom: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.icon-glow {
    position: absolute;
    width: 120%;
    height: 120%;
    border-radius: 50%;
    background: radial-gradient(circle,
                rgba(var(--desktop-accent-color-rgb, 59, 130, 246), 0.2) 0%,
                rgba(var(--desktop-accent-color-rgb, 59, 130, 246), 0) 70%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: scale(1);
}

.desktop-icon:hover .icon-glow {
    opacity: 0.8;
    transform: scale(1.3);
}

.desktop-icon.selected .icon-glow {
    opacity: 1;
    transform: scale(1.4);
    background: radial-gradient(circle,
                rgba(var(--desktop-accent-color-rgb, 59, 130, 246), 0.35) 0%,
                rgba(var(--desktop-accent-color-rgb, 59, 130, 246), 0.1) 50%,
                rgba(var(--desktop-accent-color-rgb, 59, 130, 246), 0) 80%);
}

.desktop-icon:hover .icon-container {
    transform: scale(1.15);
}

.desktop-icon.selected:hover .icon-container {
    transform: scale(1.2) rotate(5deg);
}

.icon-container .v-icon {
    font-size: 42px;
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.3));
    transition: all 0.3s ease;
}

.desktop-icon:hover .icon-container .v-icon {
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.4));
}

.icon-image {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.folder-icon {
    color: var(--icon-color, #fbbf24) !important;
    filter: drop-shadow(0 4px 12px rgba(251, 191, 36, 0.5));
}

.desktop-icon:hover .folder-icon {
    filter: drop-shadow(0 6px 16px rgba(251, 191, 36, 0.6));
}

/*
   Zweizeilige Beschriftung.

   Vorher stand hier white-space: nowrap mit text-overflow: ellipsis - genau
   die Ursache der abgeschnittenen Namen auf der Arbeitsflaeche: "Schwarze...",
   "Organisati...", "Datei Man...". Der Entwurf loest das mit zwei Zeilen,
   "ohne dass das Raster waechst": Breite und Hoehe der Kachel bleiben, nur der
   Umbruch ist erlaubt. Gespeicherte Anordnungen bleiben damit gueltig.

   overflow-wrap: anywhere, weil "Website-Manager" sonst als ein Wort ueber die
   Kachel hinausragt.
*/
.icon-title {
    width: 100%;
    max-width: 100px;
    text-align: center;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow-wrap: anywhere;
    font-size: 10.5px;
    font-weight: 600;
    color: var(--k-ink);
    line-height: 1.25;
    padding: 4px 6px;
    border-radius: 4px;
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    transition: background-color 120ms ease, border-color 120ms ease;
}

.desktop-icon:hover .icon-title {
    background: var(--k-row-hover);
    border-color: var(--k-line-strong);
}

.desktop-icon.selected .icon-title {
    background: var(--k-accent-weak);
    border-color: var(--k-accent-line);
    color: var(--k-accent);
}

/* Indikator für Ordner (optional) */
.folder-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background-color: var(--k-accent);
    border-radius: 50%;
    box-shadow: none;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

/* Keine Sonderregeln mehr fuer den hellen Modus: Flaeche, Linie und Schrift
   kommen aus den Merkern und kippen mit dem Theme. Die Regeln hier setzten die
   Kachel auf --k-ink-faint, also auf ein mittleres Grau - eine graue Platte
   unter jedem Symbol. */

/* Puls-Animation beim Ziehen */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Animation auf Icon anwenden, wenn gezogen wird */
.is-dragging .icon-image { 
    /* animation: pulse 1s var(--animation-easing) infinite; */ /* Optional: Animation aktivieren */
}

.custom-icon-image {
    width: 48px;
    height: 48px;
    object-fit: contain;
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.4));
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.desktop-icon:hover .custom-icon-image {
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.5));
    transform: scale(1.1);
}

.desktop-icon.is-dragging .icon-container {
    animation: gentle-pulse 1.5s infinite ease-in-out;
}

@keyframes gentle-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.12); }
    100% { transform: scale(1); }
}

/* Animation für neu erstellte Icons */
.desktop-icon.new-icon {
    animation: appear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes appear {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(20px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>