<!-- Calculator Component -->
<template>
  <div class="calculator">
    <div class="calculator-display">
      <div class="display-result">{{ currentDisplay }}</div>
      <div class="display-input">{{ inputExpression }}</div>
    </div>
    
    <div class="calculator-keypad">
      <div class="keypad-row">
        <button class="keypad-button operation-button" @click="resetCalculator">C</button>
        <button class="keypad-button operation-button" @click="deleteLastInput">⌫</button>
        <button class="keypad-button operation-button" @click="appendToInput('%')">%</button>
        <button class="keypad-button operation-button" @click="appendToInput('/')">/</button>
      </div>
      <div class="keypad-row">
        <button class="keypad-button number-button" @click="appendToInput('7')">7</button>
        <button class="keypad-button number-button" @click="appendToInput('8')">8</button>
        <button class="keypad-button number-button" @click="appendToInput('9')">9</button>
        <button class="keypad-button operation-button" @click="appendToInput('*')">×</button>
      </div>
      <div class="keypad-row">
        <button class="keypad-button number-button" @click="appendToInput('4')">4</button>
        <button class="keypad-button number-button" @click="appendToInput('5')">5</button>
        <button class="keypad-button number-button" @click="appendToInput('6')">6</button>
        <button class="keypad-button operation-button" @click="appendToInput('-')">-</button>
      </div>
      <div class="keypad-row">
        <button class="keypad-button number-button" @click="appendToInput('1')">1</button>
        <button class="keypad-button number-button" @click="appendToInput('2')">2</button>
        <button class="keypad-button number-button" @click="appendToInput('3')">3</button>
        <button class="keypad-button operation-button" @click="appendToInput('+')">+</button>
      </div>
      <div class="keypad-row">
        <button class="keypad-button number-button" @click="appendToInput('0')">0</button>
        <button class="keypad-button number-button" @click="appendToInput('.')">.</button>
        <button class="keypad-button equals-button" @click="calculateResult">=</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, onMounted } from 'vue';
import type { WindowContext } from '@/stores/windowContext';

// Props vom Fenster
interface Props {
  windowId?: string
  desktopWindow?: boolean
}

defineProps<Props>();

// Emits für Kommunikation mit dem Fenster
const emit = defineEmits(['loaded']);

// Fensterkontext aus dem umgebenden Fenster
const windowContext = inject<WindowContext>('windowContext', null);

// Calculator state
const inputExpression = ref('');
const currentDisplay = ref('0');
const hasCalculated = ref(false);

// Funktionen
const appendToInput = (value: string) => {
  // Wenn ein Ergebnis angezeigt wird und ein neuer Wert eingegeben wird, zurücksetzen
  if (hasCalculated.value) {
    if ('0123456789'.includes(value)) {
      inputExpression.value = value;
      currentDisplay.value = value;
    } else {
      inputExpression.value = currentDisplay.value + value;
    }
    hasCalculated.value = false;
  } else {
    inputExpression.value += value;
    currentDisplay.value = inputExpression.value;
  }
};

const deleteLastInput = () => {
  if (hasCalculated.value) {
    resetCalculator();
  } else {
    inputExpression.value = inputExpression.value.slice(0, -1);
    if (inputExpression.value === '') {
      currentDisplay.value = '0';
    } else {
      currentDisplay.value = inputExpression.value;
    }
  }
};

const resetCalculator = () => {
  inputExpression.value = '';
  currentDisplay.value = '0';
  hasCalculated.value = false;
};

const calculateResult = () => {
  if (inputExpression.value === '') return;
  
  try {
    // Sicher eval durch Function
    const sanitizedExpression = inputExpression.value
      .replace(/%/g, '/100')
      .replace(/×/g, '*');
    
     
    const result = new Function(`return ${sanitizedExpression}`)();
    currentDisplay.value = String(result);
    hasCalculated.value = true;
  } catch (error) {
    currentDisplay.value = 'Error';
    hasCalculated.value = true;
  }
};

// Wenn Komponente geladen ist, Loading-Status aktualisieren
onMounted(() => {
  if (windowContext) {
    windowContext.setLoading(false);
  }
  
  // Dem Fenster mitteilen, dass die Komponente geladen ist
  emit('loaded');
});
</script>

<style scoped>
.calculator {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
  overflow: hidden;
  user-select: none;
}

.calculator-display {
  background-color: #212121;
  color: var(--k-ink);
  padding: 20px;
  text-align: right;
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  min-height: 100px;
  font-family: 'Roboto Mono', monospace;
}

.display-result {
  font-size: 2.5rem;
  margin-bottom: 8px;
  word-break: break-all;
}

.display-input {
  font-size: 1rem;
  color: var(--k-ink-muted);
  min-height: 1rem;
  word-break: break-all;
}

.calculator-keypad {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.keypad-row {
  display: flex;
  flex: 1;
}

.keypad-button {
  flex: 1;
  border: none;
  font-size: 1.5rem;
  background-color: #e0e0e0;
  outline: none;
  cursor: pointer;
  border: 1px solid rgba(0, 0, 0, 0.1);
  transition: background-color 0.2s;
}

.number-button {
  background-color: #f0f0f0;
}

.operation-button {
  background-color: #e0e0e0;
}

.equals-button {
  background-color: #2196f3;
  color: var(--k-ink);
  flex: 2;
}

.keypad-button:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.equals-button:hover {
  background-color: #1976d2;
}
</style> 