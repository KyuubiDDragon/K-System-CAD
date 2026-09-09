<template>
  <div class="whiteboard-canvas" :class="{ 'dark-mode': darkMode }">
    <div class="whiteboard-toolbar">
      <div class="toolbar-group">
        <v-btn @click="goBack" icon color="primary" class="back-button">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <span class="whiteboard-title">{{ whiteboardName || $t('whiteboard') }}</span>
      </div>
      
      <div class="toolbar-group">
        <v-btn-toggle v-model="selectedTool" mandatory>
          <v-btn value="pen" :title="$t('whiteboard.pen')">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn value="eraser" :title="$t('whiteboard.eraser')">
            <v-icon>mdi-eraser</v-icon>
          </v-btn>
          <v-btn value="shapes" :title="$t('whiteboard.shapes')">
            <v-icon>mdi-shape-outline</v-icon>
          </v-btn>
          <v-btn value="select" :title="$t('whiteboard.select')">
            <v-icon>mdi-cursor-default-click</v-icon>
          </v-btn>
        </v-btn-toggle>
        
        <!-- Shapes Submenu -->
        <v-menu v-if="selectedTool === 'shapes'" offset-y>
          <template v-slot:activator="{ props }">
            <v-btn icon v-bind="props" color="primary">
              <v-icon>{{ getShapeIcon }}</v-icon>
            </v-btn>
          </template>
          <v-card>
            <v-card-text>
              <v-btn-toggle v-model="selectedShape" mandatory>
                <v-btn value="line" :title="$t('whiteboard.line')">
                  <v-icon>mdi-minus</v-icon>
                </v-btn>
                <v-btn value="rect" :title="$t('whiteboard.rect')">
                  <v-icon>mdi-rectangle-outline</v-icon>
                </v-btn>
                <v-btn value="circle" :title="$t('whiteboard.circle')">
                  <v-icon>mdi-circle-outline</v-icon>
                </v-btn>
                <v-btn value="triangle" :title="$t('whiteboard.triangle')">
                  <v-icon>mdi-triangle-outline</v-icon>
                </v-btn>
                <v-btn value="arrow" :title="$t('whiteboard.arrow')">
                  <v-icon>mdi-arrow-right</v-icon>
                </v-btn>
              </v-btn-toggle>
            </v-card-text>
          </v-card>
        </v-menu>
      </div>
      
      <div class="toolbar-group">
        <v-menu offset-y>
          <template v-slot:activator="{ props }">
            <v-btn icon v-bind="props">
              <div class="color-preview" :style="{ backgroundColor: penColor }"></div>
            </v-btn>
          </template>
          <v-card>
            <v-card-text>
              <div class="color-picker">
                <div 
                  v-for="color in colors" 
                  :key="color" 
                  class="color-option"
                  :class="{ active: penColor === color }"
                  :style="{ backgroundColor: color }"
                  @click="penColor = color"
                ></div>
              </div>
              
              <v-text-field
                v-model="customColor"
                :label="$t('whiteboard.customColor')"
                type="color"
                hide-details
                class="mt-2"
                @change="penColor = customColor"
              ></v-text-field>
            </v-card-text>
          </v-card>
        </v-menu>
        
        <v-btn-toggle v-model="penSize">
          <v-btn value="small" :title="$t('whiteboard.thin')">
            <span class="pen-size-preview pen-size-small"></span>
          </v-btn>
          <v-btn value="medium" :title="$t('whiteboard.medium')">
            <span class="pen-size-preview pen-size-medium"></span>
          </v-btn>
          <v-btn value="large" :title="$t('whiteboard.thick')">
            <span class="pen-size-preview pen-size-large"></span>
          </v-btn>
        </v-btn-toggle>
      </div>
      
      <div class="toolbar-group">
        <v-btn @click="undoLastAction" icon :title="$t('tiptap.undo')">
          <v-icon>mdi-undo</v-icon>
        </v-btn>
        <v-btn @click="redoLastAction" icon :title="$t('tiptap.redo')">
          <v-icon>mdi-redo</v-icon>
        </v-btn>
        <v-btn @click="clearCanvas" icon :title="$t('clear')">
          <v-icon>mdi-delete</v-icon>
        </v-btn>
        <v-btn @click="saveCanvas" icon :title="$t('save')">
          <v-icon>mdi-content-save</v-icon>
        </v-btn>
      </div>
    </div>
    
    <div class="canvas-container" ref="canvasContainer">
      <canvas 
        ref="canvas" 
        @mousedown="startDrawing" 
        @mousemove="draw" 
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="stopDrawing"
      ></canvas>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue';
import { useSocketIO } from '@/composables/useSocketIO';
import { nanoid } from 'nanoid';
import { useTheme } from 'vuetify';
import { apiClientAuth } from '@/api';

export default {
  name: 'WhiteboardCanvas',
  props: {
    whiteboardId: {
      type: [Number, String],
      required: true
    },
    whiteboardName: {
      type: String,
      default: ''
    },
    initialContent: {
      type: Object,
      default: () => ({})
    },
    history: {
      type: Array,
      default: () => []
    },
    participants: {
      type: Number,
      default: 1
    }
  },
  
  emits: ['back', 'error'],
  
  setup(props, { emit }) {
    const { socket } = useSocketIO();
    const theme = useTheme();
    const darkMode = ref(theme.global.current.value.dark);
    
    const canvas = ref(null);
    const canvasContainer = ref(null);
    let ctx = null;
    
    // Zustandsvariablen
    const isDrawing = ref(false);
    const loading = ref(true);
    const lastPos = ref({ x: 0, y: 0 });
    const participantsCount = ref(props.participants);
    const drawnElements = ref({});
    const currentPath = ref([]);
    
    // Aktions-Verlauf
    const actionHistory = ref([]);
    const redoStack = ref([]);
    
    // Anpassung
    const customColor = ref('#000000');
    
    // Canvas-Dimensionen - an Container anpassen
    const canvasWidth = ref(window.innerWidth);
    const canvasHeight = ref(window.innerHeight - 70); // Toolbar-Höhe abziehen
    
    // Werkzeug-Einstellungen
    const selectedTool = ref('pen');
    const penColor = ref('#000000');
    const penSize = ref('medium');
    const selectedShape = ref('rect');
    
    // Icon für ausgewählte Form
    const getShapeIcon = computed(() => {
      switch(selectedShape.value) {
        case 'line': return 'mdi-minus';
        case 'rect': return 'mdi-rectangle-outline';
        case 'circle': return 'mdi-circle-outline';
        case 'triangle': return 'mdi-triangle-outline';
        case 'arrow': return 'mdi-arrow-right';
        default: return 'mdi-shape-outline';
      }
    });
    
    // Farbpalette
    const colors = [
      '#000000', // Schwarz
      '#FFFFFF', // Weiß
      '#FF0000', // Rot
      '#00FF00', // Grün
      '#0000FF', // Blau
      '#FFFF00', // Gelb
      '#FF00FF', // Magenta
      '#00FFFF', // Cyan
      '#FFA500', // Orange
      '#800080', // Lila
      '#A52A2A', // Braun
      '#808080', // Grau
      '#FFB6C1', // Hellrosa
      '#98FB98', // Hellgrün
      '#ADD8E6'  // Hellblau
    ];
    
    // Canvas Initialisierung
    const initCanvas = () => {
      if (!canvas.value) return;
      
      // Canvas auf volle Größe des Containers setzen
      canvas.value.width = canvasWidth.value;
      canvas.value.height = canvasHeight.value;
      
      ctx = canvas.value.getContext('2d');
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      
      // Dark Mode Hintergrund wenn nötig
      if (darkMode.value) {
        ctx.fillStyle = '#1E1E1E';
        ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
      }
      
      // Vorhandene Elemente laden
      loading.value = false;
      loadInitialContent();
      
      // Pen-Style initial setzen
      updatePenStyle();
      
      console.log('Canvas initialisiert mit Dimensionen:', canvas.value.width, 'x', canvas.value.height);
    };
    
    const loadInitialContent = () => {
      // Alle gespeicherten Elemente zeichnen
      Object.values(props.initialContent).forEach(element => {
        drawElementFromData(element);
        drawnElements.value[element.id] = element;
      });
    };
    
    // Zeichenelemente initialisieren
    const getCanvasPosition = (e) => {
      if (!canvas.value) return { x: 0, y: 0 };
      
      const rect = canvas.value.getBoundingClientRect();
      const scaleX = canvas.value.width / rect.width;
      const scaleY = canvas.value.height / rect.height;
      
      if (e.touches && e.touches[0]) {
        return {
          x: (e.touches[0].clientX - rect.left) * scaleX,
          y: (e.touches[0].clientY - rect.top) * scaleY
        };
      }
      
      return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY
      };
    };
    
    // Zeichenfunktionen implementieren
    const startDrawing = (e) => {
      if (loading.value) return;
      
      isDrawing.value = true;
      const pos = getCanvasPosition(e);
      lastPos.value = pos;
      
      if (selectedTool.value === 'shapes') {
        // Zeichnen mit ausgewählter Form
        updatePenStyle();
        currentPath.value = [{ x: pos.x, y: pos.y }];
      } else if (selectedTool.value === 'pen') {
        // Mit Stift zeichnen
        updatePenStyle();
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        currentPath.value = [{ x: pos.x, y: pos.y }];
      } else if (selectedTool.value === 'eraser') {
        // Mit Radierer zeichnen
        ctx.strokeStyle = darkMode.value ? '#1E1E1E' : '#FFFFFF';
        ctx.lineWidth = getPenWidth() * 3; // Größerer Radierer
        
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        currentPath.value = [{ x: pos.x, y: pos.y }];
      }
    };
    
    const draw = (e) => {
      if (!isDrawing.value || loading.value) return;
      
      const pos = getCanvasPosition(e);
      
      if (selectedTool.value === 'pen') {
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        currentPath.value.push({ x: pos.x, y: pos.y });
      } else if (selectedTool.value === 'eraser') {
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        currentPath.value.push({ x: pos.x, y: pos.y });
      } else if (selectedTool.value === 'shapes') {
        // Canvas zurücksetzen und alles neu zeichnen
        redrawCanvas();
        
        // Temporäre Form zeichnen
        const startPoint = currentPath.value[0];
        updatePenStyle();
        
        if (selectedShape.value === 'line') {
          drawLine(startPoint.x, startPoint.y, pos.x, pos.y);
        } else if (selectedShape.value === 'rect') {
          drawRect(startPoint.x, startPoint.y, pos.x - startPoint.x, pos.y - startPoint.y);
        } else if (selectedShape.value === 'circle') {
          const radius = Math.sqrt(Math.pow(pos.x - startPoint.x, 2) + Math.pow(pos.y - startPoint.y, 2));
          drawCircle(startPoint.x, startPoint.y, radius);
        } else if (selectedShape.value === 'triangle') {
          drawTriangle(startPoint.x, startPoint.y, pos.x, pos.y);
        } else if (selectedShape.value === 'arrow') {
          drawArrow(startPoint.x, startPoint.y, pos.x, pos.y);
        }
        
        // Aktuellen Punkt zum Pfad hinzufügen
        if (currentPath.value.length === 1) {
          currentPath.value.push({ x: pos.x, y: pos.y });
        } else {
          currentPath.value[1] = { x: pos.x, y: pos.y };
        }
      }
    };
    
    const stopDrawing = () => {
      if (!isDrawing.value) return;
      
      if (selectedTool.value === 'pen' || selectedTool.value === 'eraser') {
        if (currentPath.value.length > 1) {
          // Element-Daten erstellen
          const elementData = {
            id: nanoid(),
            type: selectedTool.value,
            color: selectedTool.value === 'eraser' ? (darkMode.value ? '#1E1E1E' : '#FFFFFF') : penColor.value,
            lineWidth: selectedTool.value === 'eraser' ? getPenWidth() * 3 : getPenWidth(),
            points: [...currentPath.value]
          };
          
          // Element an den Server senden
          // Statt Socket.io nutzen wir direkt die API
          saveElementViaApi(elementData).catch(error => {
            console.error('Fehler beim Speichern des Elements:', error);
          });
          
          // Lokal behandeln
          handleDrawEvent({ element: elementData });
        }
      } else if (selectedTool.value === 'shapes' && currentPath.value.length > 1) {
        const firstPoint = currentPath.value[0];
        const lastPoint = currentPath.value[1];
        
        // Element-Daten erstellen
        const elementData = {
          id: nanoid(),
          type: selectedShape.value,
          color: penColor.value,
          lineWidth: getPenWidth(),
          startX: firstPoint.x,
          startY: firstPoint.y,
          endX: lastPoint.x,
          endY: lastPoint.y
        };
        
        // Spezifische Eigenschaften je nach Form hinzufügen
        if (selectedShape.value === 'circle') {
          const radius = Math.sqrt(
            Math.pow(lastPoint.x - firstPoint.x, 2) + 
            Math.pow(lastPoint.y - firstPoint.y, 2)
          );
          elementData.radius = radius;
        }
        
        // Element an den Server senden
        // Statt Socket.io nutzen wir direkt die API
        saveElementViaApi(elementData).catch(error => {
          console.error('Fehler beim Speichern des Elements:', error);
        });
        
        // Lokal behandeln
        handleDrawEvent({ element: elementData });
      }
      
      currentPath.value = [];
      isDrawing.value = false;
    };
    
    // Drawing helper functions
    const drawElementFromData = (element) => {
      if (!ctx) return;
      
      ctx.strokeStyle = element.color;
      ctx.fillStyle = element.color;
      ctx.lineWidth = element.lineWidth || getPenWidth();
      
      switch (element.type) {
        case 'pen':
        case 'eraser':
          drawPathFromPoints(element.points);
          break;
        case 'line':
          drawLine(element.startX, element.startY, element.endX, element.endY);
          break;
        case 'rect':
          drawRect(element.startX, element.startY, element.endX - element.startX, element.endY - element.startY);
          break;
        case 'circle':
          drawCircle(element.startX, element.startY, element.radius);
          break;
        case 'triangle':
          drawTriangle(element.startX, element.startY, element.endX, element.endY);
          break;
        case 'arrow':
          drawArrow(element.startX, element.startY, element.endX, element.endY);
          break;
        case 'text':
          // Text weiterhin zeichnen (für bestehende Text-Elemente)
          ctx.font = `${element.fontSize || 20}px Arial`;
          ctx.fillText(element.text, element.x, element.y);
          break;
        default:
          console.warn('Unbekannter Element-Typ:', element.type);
      }
    };
    
    const drawPathFromPoints = (points) => {
      if (!points || points.length < 2) return;
      
      ctx.beginPath();
      ctx.moveTo(points[0].x, points[0].y);
      
      for (let i = 1; i < points.length; i++) {
        ctx.lineTo(points[i].x, points[i].y);
      }
      
      ctx.stroke();
    };
    
    const drawLine = (x1, y1, x2, y2) => {
      ctx.beginPath();
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.stroke();
    };
    
    const drawRect = (x, y, width, height) => {
      ctx.beginPath();
      ctx.rect(x, y, width, height);
      ctx.stroke();
    };
    
    const drawCircle = (x, y, radius) => {
      ctx.beginPath();
      ctx.arc(x, y, radius, 0, Math.PI * 2);
      ctx.stroke();
    };
    
    const drawTriangle = (x1, y1, x2, y2) => {
      const width = x2 - x1;
      const height = y2 - y1;
      
      ctx.beginPath();
      ctx.moveTo(x1 + width/2, y1); // Spitze
      ctx.lineTo(x1, y2); // Unten links
      ctx.lineTo(x2, y2); // Unten rechts
      ctx.closePath();
      ctx.stroke();
    };
    
    const drawArrow = (x1, y1, x2, y2) => {
      const headLength = 15;
      const dx = x2 - x1;
      const dy = y2 - y1;
      const angle = Math.atan2(dy, dx);
      
      // Pfeilkörper
      ctx.beginPath();
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.stroke();
      
      // Pfeilkopf
      ctx.beginPath();
      ctx.moveTo(x2, y2);
      ctx.lineTo(
        x2 - headLength * Math.cos(angle - Math.PI/6),
        y2 - headLength * Math.sin(angle - Math.PI/6)
      );
      ctx.lineTo(
        x2 - headLength * Math.cos(angle + Math.PI/6),
        y2 - headLength * Math.sin(angle + Math.PI/6)
      );
      ctx.closePath();
      ctx.fill();
    };
    
    const redrawCanvas = () => {
      if (!ctx) return;
      
      // Canvas löschen
      ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
      
      // Hintergrundfarbe setzen (für Dark Mode)
      if (darkMode.value) {
        ctx.fillStyle = '#1E1E1E';
        ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
      }
      
      // Alle gespeicherten Elemente neu zeichnen
      Object.values(drawnElements.value).forEach(element => {
        drawElementFromData(element);
      });
    };
    
    // Hilfsfunktionen
    const getPenWidth = () => {
      switch (penSize.value) {
        case 'small': return 2;
        case 'medium': return 5;
        case 'large': return 10;
        default: return 5;
      }
    };
    
    const updatePenStyle = () => {
      if (!ctx) return;
      
      ctx.strokeStyle = penColor.value;
      ctx.fillStyle = penColor.value;
      ctx.lineWidth = getPenWidth();
    };
    
    const handleTouchStart = (e) => {
      e.preventDefault();
      startDrawing(e);
    };
    
    const handleTouchMove = (e) => {
      e.preventDefault();
      draw(e);
    };
    
    // Canvas actions
    const clearCanvas = () => {
      if (confirm($t('whiteboard.clearConfirm'))) {
        // Alle Elemente speichern für Undo
        const deletedElements = { ...drawnElements.value };
        
        // Element-IDs für API-Aufrufe
        const elementIds = Object.keys(drawnElements.value);
        
        // Aktion zum Verlauf hinzufügen
        actionHistory.value.push({
          type: 'clear',
          elements: deletedElements
        });
        
        // Redo-Stack leeren
        redoStack.value = [];
        
        // Canvas leeren
        drawnElements.value = {};
        redrawCanvas();
        
        // Alle gelöschten Elemente verarbeiten
        elementIds.forEach(id => {
          // Direkt über API löschen
          deleteElementViaApi(id).catch(error => {
            console.error(`Fehler beim Löschen des Elements ${id} über API:`, error);
          });
          
          // Lokalen Event-Handler aufrufen
          handleDeleteElement({ elementId: id });
        });
      }
    };
    
    const saveCanvas = () => {
      if (canvas.value) {
        // Canvas als PNG-Datei speichern (lokaler Download)
        const dataUrl = canvas.value.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = `${props.whiteboardName || 'whiteboard'}_${new Date().toISOString().slice(0,10)}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Snapshot über API speichern
        saveSnapshotViaApi(dataUrl).catch(error => {
          console.error('Fehler beim Speichern des Snapshots über API:', error);
          emit('error', {
            message: $t('whiteboard.snapshotError')
          });
        });
      }
    };
    
    const goBack = () => {
      // Zur Manager-Ansicht zurückkehren
      emit('back');
    };
    
    // Undo/Redo Funktionen
    const undoLastAction = () => {
      if (actionHistory.value.length === 0) return;
      
      const lastAction = actionHistory.value.pop();
      redoStack.value.push(lastAction);
      
      if (lastAction.type === 'add') {
        // Element löschen (für Undo einer Addition)
        deleteElementViaApi(lastAction.elementId).catch(error => {
          console.error(`Fehler beim Löschen des Elements ${lastAction.elementId} über API:`, error);
        });
        
        delete drawnElements.value[lastAction.elementId];
      } else if (lastAction.type === 'delete') {
        // Element wieder hinzufügen (für Undo einer Löschung)
        saveElementViaApi({
          whiteboardId: props.whiteboardId,
          element: lastAction.element
        }).catch(error => {
          console.error('Fehler beim Wiederherstellen des Elements über API:', error);
        });
        
        drawnElements.value[lastAction.elementId] = lastAction.element;
      } else if (lastAction.type === 'clear') {
        // Alle gelöschten Elemente wiederherstellen
        Object.entries(lastAction.elements).forEach(([id, element]) => {
          saveElementViaApi({
            whiteboardId: props.whiteboardId,
            element
          }).catch(error => {
            console.error(`Fehler beim Wiederherstellen des Elements ${id} über API:`, error);
          });
          
          drawnElements.value[id] = element;
        });
      }
      
      redrawCanvas();
    };
    
    const redoLastAction = () => {
      if (redoStack.value.length === 0) return;
      
      const actionToRedo = redoStack.value.pop();
      actionHistory.value.push(actionToRedo);
      
      if (actionToRedo.type === 'add') {
        // Element wieder hinzufügen (für Redo einer Addition)
        saveElementViaApi({
          whiteboardId: props.whiteboardId,
          element: actionToRedo.element
        }).catch(error => {
          console.error('Fehler beim Wiederherstellen des Elements über API:', error);
        });
        
        drawnElements.value[actionToRedo.elementId] = actionToRedo.element;
      } else if (actionToRedo.type === 'delete') {
        // Element wieder löschen (für Redo einer Löschung)
        deleteElementViaApi(actionToRedo.elementId).catch(error => {
          console.error(`Fehler beim Löschen des Elements ${actionToRedo.elementId} über API:`, error);
        });
        
        delete drawnElements.value[actionToRedo.elementId];
      } else if (actionToRedo.type === 'clear') {
        // Alle Elemente erneut löschen
        Object.keys(actionToRedo.elements).forEach(id => {
          deleteElementViaApi(id).catch(error => {
            console.error(`Fehler beim Löschen des Elements ${id} über API:`, error);
          });
          
          delete drawnElements.value[id];
        });
      }
      
      redrawCanvas();
    };
    
    // Fehlende Funktionen hinzufügen
    const finishDrawing = () => {
      // Diese Funktion dient als Hilfsfunktion zum Abschließen des Zeichenvorgangs
      // Sie wird verwendet, um sicherzustellen, dass der Zeichenvorgang korrekt beendet wird
      stopDrawing();
    };
    
    // Funktion zum Löschen eines Elements
    const deleteElement = (elementId) => {
      if (!elementId || !drawnElements.value[elementId]) return;
      
      // Zum Verlauf hinzufügen vor dem Löschen
      actionHistory.value.push({
        type: 'delete',
        elementId,
        element: { ...drawnElements.value[elementId] }
      });
      
      // Element löschen
      delete drawnElements.value[elementId];
      
      // Canvas neu zeichnen
      redrawCanvas();
      
      // Element über API löschen
      deleteElementViaApi(elementId).catch(error => {
        console.error(`Fehler beim Löschen des Elements ${elementId} über API:`, error);
      });
      
      // Redo-Stack leeren
      redoStack.value = [];
    };
    
    // Fenster-Größenänderung überwachen
    const handleResize = () => {
      canvasWidth.value = window.innerWidth;
      canvasHeight.value = window.innerHeight - 70;
      
      if (canvas.value && ctx) {
        // Aktuellen Canvas-Inhalt speichern
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        tempCanvas.width = canvas.value.width;
        tempCanvas.height = canvas.value.height;
        tempCtx.drawImage(canvas.value, 0, 0);
        
        // Canvas-Größe anpassen
        canvas.value.width = canvasWidth.value;
        canvas.value.height = canvasHeight.value;
        
        // Dark Mode Hintergrund wenn nötig
        if (darkMode.value) {
          ctx.fillStyle = '#1E1E1E';
          ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
        }
        
        // Alten Inhalt wiederherstellen
        ctx.drawImage(tempCanvas, 0, 0);
        
        // Stift-Einstellungen zurücksetzen
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        updatePenStyle();
      }
    };
    
    // Watch für Dark Mode
    watch(darkMode, () => {
      redrawCanvas(); // Canvas neu zeichnen mit aktualisiertem Hintergrund
    });
    
    // Socket.io-Events
    const handleDrawEvent = (data) => {
      // Von Socket.io oder lokalen Updates getriggert
      const element = data.element;
      
      // Prüfen, ob das Element zum aktuellen Whiteboard gehört
      if (element.whiteboardId && element.whiteboardId !== props.whiteboardId) {
        return;
      }
      
      // Zeichne das Element, egal woher es kommt
      drawElementFromData(element);
      
      // Element speichern
      if (element.id) {
        // Nur hinzufügen, wenn wir es nicht bereits haben (verhindert Duplikate)
        if (!drawnElements.value[element.id]) {
          drawnElements.value[element.id] = element;
          // Zum Verlauf hinzufügen
          actionHistory.value.push({
            type: 'add',
            elementId: element.id,
            element: { ...element }
          });
          // Redo-Stack leeren, da neue Aktion
          redoStack.value = [];
        }
      }
    };
    
    const handleDeleteElement = (data) => {
      // Lokaler Event-Handler für Element-Löschung
      const { elementId, whiteboardId } = data;
      
      // Prüfen, ob die Löschung zum aktuellen Whiteboard gehört
      if (whiteboardId && whiteboardId !== props.whiteboardId) {
        return;
      }
      
      if (drawnElements.value[elementId]) {
        // Zum Verlauf hinzufügen vor dem Löschen
        actionHistory.value.push({
          type: 'delete',
          elementId,
          element: { ...drawnElements.value[elementId] }
        });
        
        delete drawnElements.value[elementId];
        redrawCanvas();
        
        // Redo-Stack leeren
        redoStack.value = [];
      }
    };
    
    const handleUserJoined = (data) => {
      // Kann entfernt oder für lokale Logik verwendet werden
      participantsCount.value += 1;
    };
    
    const handleUserLeft = (data) => {
      // Kann entfernt oder für lokale Logik verwendet werden
      participantsCount.value = Math.max(1, participantsCount.value - 1);
    };
    
    // Lifecycle hooks
    onMounted(() => {
      try {
        // Canvas initialisieren
        initCanvas();
        
        // Event-Listener für Socket.io-Events registrieren
        console.log('Registriere Socket.io-Events für Whiteboard-Zeichnungen');
        if (socket.value) {
          console.log('Socket verbunden:', socket.value.id);
          
          // Event-Handler registrieren
          socket.value.on('whiteboard:element-drawn', (data) => {
            handleDrawEvent(data);
          });
          
          socket.value.on('whiteboard:element-deleted', (data) => {
            handleDeleteElement(data);
          });
          
          socket.value.on('whiteboard:user-joined', handleUserJoined);
          socket.value.on('whiteboard:user-left', handleUserLeft);
          
          // Beim Beitreten zum Raum anfragen, ob es bereits Elemente gibt
          console.log('Trete dem Whiteboard-Raum bei, ID:', props.whiteboardId);
          socket.value.emit('whiteboard:join', {
            whiteboardId: props.whiteboardId
          });
        } else {
          console.error('Socket ist nicht initialisiert, Echtzeit-Synchronisation nicht verfügbar');
        }
        
        // Fenster-Größenänderung
        window.addEventListener('resize', handleResize);
      } catch (error) {
        console.error('Fehler beim Initialisieren des Canvas:', error);
        emit('error', { message: 'Fehler beim Initialisieren: ' + error.message });
      }
    });
    
    onBeforeUnmount(() => {
      // Socket.io-Event-Listener entfernen
      if (socket.value) {
        console.log('Entferne Socket.io-Event-Listener beim Verlassen des Canvas');
        socket.value.off('whiteboard:element-drawn');
        socket.value.off('whiteboard:element-deleted');
        socket.value.off('whiteboard:user-joined');
        socket.value.off('whiteboard:user-left');
        
        // Verlasse den Whiteboard-Raum
        console.log('Verlasse Whiteboard-Raum, ID:', props.whiteboardId);
        socket.value.emit('whiteboard:leave', {
          whiteboardId: props.whiteboardId
        });
      }
      
      // Fenster-Größenänderung
      window.removeEventListener('resize', handleResize);
    });
    
    // Neue Funktion: Element über API speichern
    const saveElementViaApi = async (elementData) => {
      try {
        // Stelle sicher, dass die Whiteboard-ID immer gesetzt ist
        if (!elementData.whiteboardId) {
          elementData.whiteboardId = props.whiteboardId;
        }
        
        await apiClientAuth.post('/whiteboard?action=saveElement', {
          whiteboardId: props.whiteboardId,
          element: elementData
        });
        
        // DUAL APPROACH: Zusätzlich über Socket.io senden für Echtzeit-Updates
        socket.value.emit('whiteboard:draw', {
          whiteboardId: props.whiteboardId,
          element: elementData
        });
      } catch (error) {
        console.error('Fehler beim Speichern des Elements:', error);
        
        // Fallback: Bei API-Fehler trotzdem über Socket.io versuchen
        socket.value.emit('whiteboard:draw', {
          whiteboardId: props.whiteboardId,
          element: elementData
        });
        throw error;
      }
    };
    
    // Neue Funktion: Element über API löschen
    const deleteElementViaApi = async (elementId) => {
      try {
        await apiClientAuth.post('/whiteboard?action=deleteElement', {
          whiteboardId: props.whiteboardId,
          elementId: elementId
        });
        
        // DUAL APPROACH: Zusätzlich über Socket.io senden für Echtzeit-Updates
        socket.value.emit('whiteboard:delete-element', {
          whiteboardId: props.whiteboardId,
          elementId: elementId
        });
      } catch (error) {
        console.error('API-Fehler beim Löschen des Elements:', error);
        
        // Fallback: Bei API-Fehler trotzdem über Socket.io versuchen
        socket.value.emit('whiteboard:delete-element', {
          whiteboardId: props.whiteboardId,
          elementId: elementId
        });
        throw error;
      }
    };
    
    // Neue Funktion: Snapshot über API speichern
    const saveSnapshotViaApi = async (dataUrl) => {
      try {
        await apiClientAuth.post('/whiteboard?action=createSnapshot', {
          whiteboardId: props.whiteboardId,
          thumbnail: dataUrl,
          name: `Snapshot ${new Date().toLocaleString()}`
        });
      } catch (error) {
        console.error('API-Fehler beim Speichern des Snapshots:', error);
        throw error;
      }
    };
    
    return {
      canvas,
      canvasContainer,
      isDrawing,
      loading,
      participantsCount,
      canvasWidth,
      canvasHeight,
      selectedTool,
      penColor,
      penSize,
      colors,
      darkMode,
      customColor,
      selectedShape,
      getShapeIcon,
      drawLine,
      drawRect,
      drawCircle,
      drawTriangle,
      drawArrow,
      startDrawing,
      draw,
      stopDrawing,
      handleTouchStart,
      handleTouchMove,
      undoLastAction,
      redoLastAction,
      clearCanvas,
      saveCanvas,
      goBack,
      finishDrawing,
      saveElementViaApi,
      deleteElement,
      deleteElementViaApi,
      saveSnapshotViaApi
    };
  }
};
</script>

<style scoped>
.whiteboard-canvas {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
  background-color: #f9f9f9;
  position: relative;
}

.whiteboard-canvas.dark-mode {
  background-color: var(--k-surface);
  color: var(--k-ink);
}

.whiteboard-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background-color: var(--k-ink);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.dark-mode .whiteboard-toolbar {
  background-color: #2D2D2D;
  color: var(--k-ink);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.whiteboard-title {
  font-size: 18px;
  font-weight: 500;
  margin-left: 12px;
}

.canvas-container {
  flex: 1;
  overflow: hidden;
  position: relative;
  width: 100%; /* Volle Breite */
  height: calc(100% - 60px); /* Höhe minus Toolbar */
}

canvas {
  display: block;
  background-color: var(--k-ink);
  touch-action: none;
  width: 100%;
  height: 100%;
}

.dark-mode canvas {
  background-color: var(--k-surface);
}

.color-preview {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 1px solid #ddd;
}

.dark-mode .color-preview {
  border-color: #555;
}

.color-picker {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.color-option {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid transparent;
  transition: transform 0.2s;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #333;
}

.dark-mode .color-option.active {
  border-color: #ddd;
}

.pen-size-preview {
  display: inline-block;
  border-radius: 50%;
  background-color: currentColor;
}

.pen-size-small {
  width: 2px;
  height: 2px;
}

.pen-size-medium {
  width: 5px;
  height: 5px;
}

.pen-size-large {
  width: 10px;
  height: 10px;
}

.back-button {
  margin-right: 8px;
}
</style> 