<template>
    <div class="solitaire-game">
      <div class="game-header">
        <h2>{{ t('solitaireGame.title') }}</h2>
        <div class="game-controls">
          <v-btn color="primary" @click="newGame">{{ t('solitaireGame.newGame') }}</v-btn>
          <div class="score-display">
            <span>{{ t('solitaireGame.moves') }}: {{ moves }}</span>
            <span class="ml-4">{{ t('solitaireGame.time') }}: {{ formatTime(gameTime) }}</span>
          </div>
        </div>
      </div>
  
      <div class="game-board">
        <div class="top-row">
          <div class="stock-waste">
            <div
              class="card-pile stock-pile"
              @click="drawCard"
              :class="{ 'empty': stock.length === 0 }"
            >
              <div v-if="stock.length > 0" class="card card-back"></div>
              <div v-else class="card-placeholder" @click.stop="resetStock"> <v-icon>mdi-reload</v-icon>
              </div>
            </div>
  
            <div class="card-pile waste-pile">
              <div
                v-for="(card, index) in displayedWaste"
                :key="'waste-' + card.id"
                class="card"
                :class="[
                  'card-' + card.suit,
                  { 'selectable': index === displayedWaste.length - 1 } 
                ]"
                :style="{ transform: `translateX(${index * 20}px)` }"
                @click="index === displayedWaste.length - 1 ? selectCard(card, 'waste') : null"
                @dblclick="index === displayedWaste.length - 1 ? tryAutoMoveToFoundation(card, 'waste') : null"
                :draggable="index === displayedWaste.length - 1"
                @dragstart="index === displayedWaste.length - 1 ? dragStart(card, 'waste', -1, -1) : null"
              >
                <div class="card-value">{{ card.display }}</div>
                <div class="card-suit">{{ getSuitSymbol(card.suit) }}</div>
              </div>
            </div>
          </div>
  
          <div class="foundation-piles">
            <div
              v-for="(pile, index) in foundations"
              :key="'foundation-' + index"
              class="card-pile foundation-pile"
              :class="{ 
                'empty': pile.length === 0, 
                'drop-target': canDropOnFoundation(index),
                'won': gameWon 
              }"
              @click="pile.length === 0 && selectedCard ? moveCardToFoundation(index) : (pile.length > 0 ? selectCard(pile[pile.length - 1], 'foundation', index) : null)"
              @dragover="onDragOver"  
              @drop="dropOnFoundation(index)"
            >
              <div
                v-if="pile.length > 0"
                class="card"
                :class="['card-' + pile[pile.length - 1].suit, 'selectable']"
                 draggable="true"
                 @dragstart="dragStart(pile[pile.length - 1], 'foundation', index, pile.length - 1)"
                 @dblclick="tryAutoMoveToFoundation(pile[pile.length - 1], 'foundation', index)"
              >
                <div class="card-value">{{ pile[pile.length - 1].display }}</div>
                <div class="card-suit">{{ getSuitSymbol(pile[pile.length - 1].suit) }}</div>
              </div>
              <div v-else class="card-placeholder">
                <span class="suit-placeholder">{{ ['♠', '♥', '♦', '♣'][index] }}</span>
              </div>
            </div>
          </div>
        </div>
  
        <div class="tableau-piles">
          <div
            v-for="(pile, pileIndex) in tableau"
            :key="'tableau-' + pileIndex"
            class="card-pile tableau-pile"
             :class="{ 'empty': pile.length === 0, 'drop-target': canDropOnTableau(pileIndex) }"
            @dragover="onDragOver"
            @drop="dropOnTableau(pileIndex)"
          >
            <div
              v-if="pile.length === 0"
              class="card-placeholder"
               @click="selectedCard ? moveCardToTableau(pileIndex) : null"
            ></div>
            <div
              v-for="(card, cardIndex) in pile"
              :key="'tableau-' + pileIndex + '-' + card.id"
              class="card"
              :class="[
                card.faceUp ? 'card-' + card.suit : 'card-back',
                { 'selectable': card.faceUp },
                { 'stacked-card': cardIndex < pile.length - 1 && card.faceUp } 
              ]"
              :style="{ 
                transform: `translateY(${cardIndex * 35}px)`,
                zIndex: cardIndex 
              }" 
              @click="card.faceUp ? selectCard(card, 'tableau', pileIndex, cardIndex) : null"
              @dblclick="card.faceUp ? tryAutoMoveToFoundation(card, 'tableau', pileIndex, cardIndex) : null"
              :draggable="card.faceUp" 
              @dragstart="card.faceUp ? dragStart(card, 'tableau', pileIndex, cardIndex) : null"
            >
              <template v-if="card.faceUp">
                <div class="card-value">{{ card.display }}</div>
                <div class="card-suit">{{ getSuitSymbol(card.suit) }}</div>
              </template>
            </div>
          </div>
        </div>
      </div>
  
      <v-snackbar v-model="showMessage" :color="messageColor" timeout="3000">
        {{ message }}
      </v-snackbar>

      <!-- Add confetti overlay for win animation -->
      <div v-if="gameWon" class="win-overlay">
        <div class="confetti-container">
          <div v-for="n in 50" :key="n" 
               class="confetti" 
               :style="{
                  '--fall-delay': `${Math.random() * 5}s`,
                  '--fall-duration': `${3 + Math.random() * 5}s`,
                  '--left-pos': `${Math.random() * 100}%`,
                  '--bg-color': `hsl(${Math.random() * 360}, 80%, 60%)`
               }">
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script lang="ts">
  import { ref, computed, onMounted, onUnmounted, defineComponent } from 'vue';
  import { useI18n } from 'vue-i18n';
  // Keine Imports von lodash-es oder vuedraggable mehr
  
  // Define card interface to fix type errors
  interface Card {
    id: number;
    suit: string;
    value: string;
    display: string;
    rank: number;
    color: string;
    faceUp: boolean;
  }
  
  export default defineComponent({
    name: 'Solitaire',
    setup() {
      const { t } = useI18n();
      // --- Originaler Game State ---
      const stock = ref<Card[]>([]);
      const waste = ref<Card[]>([]);
      const foundations = ref<Card[][]>([[], [], [], []]);
      const tableau = ref<Card[][]>([[], [], [], [], [], [], []]);
      
      // State für Klick-Auswahl
      const selectedCard = ref<Card | null>(null);
      const selectedSource = ref<string | null>(null);
      const selectedPileIndex = ref<number>(-1);
      const selectedCardIndex = ref<number>(-1); // Wichtig für Tableau-Stacks
      
      // State für Drag & Drop
      const draggedCard = ref<Card | null>(null);
      const dragSource = ref<string | null>(null);
      const dragPileIndex = ref<number>(-1);
      const dragCardIndex = ref<number>(-1);
      const draggedStack = ref<Card[]>([]); // Um Stapel im Tableau zu ziehen
      
      const moves = ref(0);
      const gameTime = ref(0);
      const gameTimer = ref<number | null>(null);
      const gameStarted = ref(false);
      const gameWon = ref(false);
      
      const message = ref('');
      const messageColor = ref('success');
      const showMessage = ref(false);
      
      // --- Original Computed Property ---
      const displayedWaste = computed(() => {
        // Zeigt nur die obersten 3 Karten des Waste-Stapels an
         const maxVisible = 3;
         const startIndex = Math.max(0, waste.value.length - maxVisible);
         // Zeigt nur die letzten 3, aber für Klickbarkeit brauchen wir das Original-Waste-Array
         // Das Original war hier etwas unklar, wie es die nicht sichtbaren behandelt.
         // Wir nehmen an, nur die oberste ist klickbar, wie im Template.
         // Die Logik zeigt nur die obersten 3 an:
         if (waste.value.length <= 3) {
           return waste.value;
         }
         return waste.value.slice(waste.value.length - 3);
      });

      // --- Lifecycle Hooks (Original) ---
      onMounted(() => {
        newGame();
      });
      
      onUnmounted(() => {
        if (gameTimer.value) {
          clearInterval(gameTimer.value);
        }
      });
      
      // --- Core Game Logic (Original) ---
      
      const newGame = () => {
        // Reset game state
        stock.value = [];
        waste.value = [];
        foundations.value = [[], [], [], []];
        tableau.value = [[], [], [], [], [], [], []];
      
        // Reset Selection State
        selectedCard.value = null;
        selectedSource.value = null;
        selectedPileIndex.value = -1;
        selectedCardIndex.value = -1;
      
        // Reset Drag State
        draggedCard.value = null;
        dragSource.value = null;
        dragPileIndex.value = -1;
        dragCardIndex.value = -1;
        draggedStack.value = [];
      
        moves.value = 0;
        gameTime.value = 0;
        gameWon.value = false;
      
        if (gameTimer.value) {
          clearInterval(gameTimer.value);
        }
      
        const deck = createDeck();
        shuffleDeck(deck);
      
        // Deal cards to tableau piles
        for (let i = 0; i < 7; i++) {
          for (let j = 0; j <= i; j++) {
            const card = deck.pop();
            if (card) {
              card.faceUp = j === i; // Nur oberste ist aufgedeckt
              tableau.value[i].push(card);
            }
          }
        }
      
        stock.value = deck;
        gameStarted.value = true;
        startTimer(); // Timer starten
      };
      
      const createDeck = (): Card[] => {
        const suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        const values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        const deck: Card[] = [];
        let id = 1;
        suits.forEach(suit => {
            values.forEach((value, index) => {
                deck.push({
                    id: id++,
                    suit,
                    value,
                    display: value,
                    rank: index + 1,
                    color: (suit === 'hearts' || suit === 'diamonds') ? 'red' : 'black',
                    faceUp: false
                });
            });
        });
        return deck;
      };
      
      const shuffleDeck = (deck: Card[]): void => {
        for (let i = deck.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [deck[i], deck[j]] = [deck[j], deck[i]];
        }
      };
      
      const drawCard = (): void => {
        if (stock.value.length === 0) {
          resetStock();
          return;
        }
        const card = stock.value.pop();
        if (card) {
          card.faceUp = true;
          waste.value.push(card);
          moves.value++;
        }
      };
      
      const resetStock = () => {
        if (waste.value.length === 0) return;
        // Kein saveState mehr
        stock.value = waste.value.map(card => ({ ...card, faceUp: false })).reverse();
        waste.value = [];
        moves.value++;
      };
      
      // --- Originale Klick-Auswahl & Bewegungslogik ---
      const selectCard = (card: Card, source: string, pileIndex: number = -1, cardIndex: number = -1) => {
          // Wenn bereits eine Karte ausgewählt ist, versuchen wir, sie zu bewegen
          if (selectedCard.value) {
              const targetPileType = source; // Wo wurde hingeklickt?
              const targetPileIndex = pileIndex;
      
              // Ist der Klick ein gültiges Ziel?
              if (targetPileType === 'foundation') {
                  moveCardToFoundation(targetPileIndex);
              } else if (targetPileType === 'tableau') {
                  // Prüfen, ob auf die letzte Karte geklickt wurde oder auf eine leere Stelle
                  if (cardIndex === tableau.value[targetPileIndex].length - 1 || pileIndex === -1) {
                       moveCardToTableau(targetPileIndex);
                  } else {
                       // Klick auf ungültige Karte im Ziel -> Auswahl aufheben
                       deselectCard();
                  }
              } else {
                   // Klick woanders hin -> Auswahl aufheben
                   deselectCard();
              }
      
          } else {
              // Keine Karte ausgewählt -> Diese Karte auswählen
              selectedCard.value = card;
              selectedSource.value = source;
              selectedPileIndex.value = pileIndex;
              selectedCardIndex.value = cardIndex;
               // Hier könnte man visuelles Feedback für die Auswahl hinzufügen
          }
      };
      
      const deselectCard = () => {
          selectedCard.value = null;
          selectedSource.value = null;
          selectedPileIndex.value = -1;
          selectedCardIndex.value = -1;
           // Visuelles Feedback entfernen
      }
      
      const moveCardToFoundation = (foundationIndex: number) => {
        if (!selectedCard.value || selectedSource.value === 'foundation') {
            deselectCard();
            return;
        }
      
        if (isValidFoundationMove(selectedCard.value, foundations.value[foundationIndex])) {
          const cardToMove = removeSelectedCard();
          if (cardToMove && cardToMove.length > 0) {
              foundations.value[foundationIndex].push(...cardToMove);
              moves.value++;
              turnUpNextTableauCard();
              checkWinCondition();
          }
        } else {
          showInvalidMoveMessage();
        }
        deselectCard();
      };
      
      const moveCardToTableau = (tableauIndex: number) => {
          if (!selectedCard.value) {
              deselectCard();
              return;
          }
      
          // Prüfen, ob der Stack mitbewegt werden muss (nur bei Quelle=Tableau)
          let cardsToMove = [selectedCard.value];
          if (selectedSource.value === 'tableau' && selectedCardIndex.value !== -1) {
              cardsToMove = tableau.value[selectedPileIndex.value].slice(selectedCardIndex.value);
          }
      
          // Nur die oberste Karte des zu bewegenden Stapels ist für die Regel relevant
          const topCardToMove = cardsToMove[0];
      
          if (isValidTableauMove(topCardToMove, tableau.value[tableauIndex])) {
              const removedCards = removeSelectedCard(cardsToMove.length); // Entferne den ganzen Stapel
              if (removedCards) {
                  tableau.value[tableauIndex].push(...removedCards);
                  moves.value++;
                  turnUpNextTableauCard(); // Nächste Karte aufdecken
                  checkWinCondition();
              }
          } else {
              showInvalidMoveMessage();
          }
          deselectCard(); // Auswahl immer aufheben nach Versuch
      };

      // Angepasste Funktion, um Karte(n) zu entfernen
      const removeSelectedCard = (count = 1): Card[] | null => {
          let removed: Card[] | undefined;
          if (selectedSource.value === 'waste') {
              removed = waste.value.splice(-count);
          } else if (selectedSource.value === 'foundation' && selectedPileIndex.value !== -1) {
              removed = foundations.value[selectedPileIndex.value].splice(-count);
          } else if (selectedSource.value === 'tableau' && selectedPileIndex.value !== -1) {
              const startIndex = selectedCardIndex.value;
              removed = tableau.value[selectedPileIndex.value].splice(startIndex, count);
          }
          return removed && removed.length > 0 ? removed : null;
      };

      // Nächste Karte im Tableau aufdecken (Original-Logik)
      const turnUpNextTableauCard = () => {
        // Diese Funktion wird nach JEDEM Zug aufgerufen, der potenziell eine Karte aufdecken könnte
        // Sie muss die Quelle kennen, aus der die Karte entfernt wurde
        if (selectedSource.value === 'tableau' && selectedPileIndex.value !== -1) {
          const pile = tableau.value[selectedPileIndex.value];
          if (pile.length > 0 && !pile[pile.length - 1].faceUp) {
            pile[pile.length - 1].faceUp = true;
          }
        }
         // Logik für D&D muss dies separat behandeln
      };
      
      // --- Originale Validierungsregeln ---
      const isValidFoundationMove = (card: Card, foundationPile: Card[]): boolean => {
        if (!card) return false;
        // Nur einzelne Karten bewegen (wird in moveCardToFoundation geprüft)
        if (foundationPile.length === 0) {
          return card.rank === 1; // Ass
        }
        const topCard = foundationPile[foundationPile.length - 1];
        return card.suit === topCard.suit && card.rank === topCard.rank + 1;
      };
      
      const isValidTableauMove = (card: Card, tableauPile: Card[]): boolean => {
        if (!card) return false;
        if (tableauPile.length === 0) {
          return card.rank === 13; // König
        }
        const topCard = tableauPile[tableauPile.length - 1];
        // Zielkarte muss aufgedeckt sein
        return topCard.faceUp && topCard.color !== card.color && topCard.rank === card.rank + 1;
      };
      
      // --- Originale Drag & Drop Funktionen ---
      const dragStart = (card: Card, source: string, pileIndex: number, cardIndex: number) => {
          // Bei Drag Start die Auswahl aufheben, um Konflikte zu vermeiden
          deselectCard();
      
          draggedCard.value = card;
          dragSource.value = source;
          dragPileIndex.value = pileIndex;
          dragCardIndex.value = cardIndex;
      
          // Wenn vom Tableau gezogen wird, den ganzen Stapel speichern
          if (source === 'tableau') {
              draggedStack.value = tableau.value[pileIndex].slice(cardIndex);
          } else {
              draggedStack.value = [card]; // Nur die einzelne Karte
          }
          // event.dataTransfer.effectAllowed = 'move'; // Oft nicht nötig
      };
      
      // Wird im Template für :class Drop-Target verwendet
      const canDropOnFoundation = (foundationIndex: number): boolean => {
        if (!draggedCard.value || draggedStack.value.length > 1) return false; // Nur einzelne Karten
        return isValidFoundationMove(draggedCard.value, foundations.value[foundationIndex]);
      };
      
      // Wird im Template für :class Drop-Target verwendet
      const canDropOnTableau = (tableauIndex: number): boolean => {
        if (!draggedCard.value) return false;
        // Prüfe die oberste Karte des gezogenen Stapels
        return isValidTableauMove(draggedCard.value, tableau.value[tableauIndex]);
      };
      
      // Helper to prevent dragover visual feedback for invalid drops
      const onDragOver = (event: DragEvent): void => {
        event.preventDefault();
      };
      
      const dropOnFoundation = (foundationIndex: number): void => {
        if (!canDropOnFoundation(foundationIndex) || !draggedCard.value) return; // Double check
      
        // Karte(n) aus Quelle entfernen
        removeDraggedCards();
      
        // Zu Foundation hinzufügen - nur wenn sicher nicht null
        if (draggedCard.value) {
          foundations.value[foundationIndex].push(draggedCard.value); // Nur die eine Karte
          moves.value++;
      
          // Nächste Karte in Quelle aufdecken
          turnUpNextTableauCardAfterDrag();
          checkWinCondition();
        }
      
        // Drag State zurücksetzen
        resetDragState();
      };
      
      const dropOnTableau = (tableauIndex: number): void => {
        if (!canDropOnTableau(tableauIndex)) return; // Double check
      
        // Karten aus Quelle entfernen
        removeDraggedCards();
      
        // Zu Tableau hinzufügen (ganzen Stapel)
        tableau.value[tableauIndex].push(...draggedStack.value);
        moves.value++;
      
        // Nächste Karte in Quelle aufdecken
        turnUpNextTableauCardAfterDrag();
        checkWinCondition();
      
        // Drag State zurücksetzen
        resetDragState();
      };
      
      // Hilfsfunktion zum Entfernen der gezogenen Karten aus der Quelle
      const removeDraggedCards = () => {
          const count = draggedStack.value.length;
          if (dragSource.value === 'waste') {
              waste.value.splice(-count);
          } else if (dragSource.value === 'foundation') {
              foundations.value[dragPileIndex.value].splice(-count);
          } else if (dragSource.value === 'tableau') {
              tableau.value[dragPileIndex.value].splice(dragCardIndex.value, count);
          }
      }
      
      // Hilfsfunktion zum Aufdecken nach D&D
      const turnUpNextTableauCardAfterDrag = () => {
          if (dragSource.value === 'tableau' && dragPileIndex.value !== -1) {
              const pile = tableau.value[dragPileIndex.value];
              if (pile.length > 0 && !pile[pile.length - 1].faceUp) {
                   pile[pile.length - 1].faceUp = true;
              }
          }
      };
      
      // Hilfsfunktion zum Zurücksetzen des Drag-States
      const resetDragState = () => {
          draggedCard.value = null;
          dragSource.value = null;
          dragPileIndex.value = -1;
          dragCardIndex.value = -1;
          draggedStack.value = [];
      }

      // --- Restliche Original-Funktionen ---
      const showInvalidMoveMessage = () => {
        message.value = t('solitaireGame.invalidMove');
        messageColor.value = 'error';
        showMessage.value = true;
      };
      
      const checkWinCondition = () => {
        const isWon = foundations.value.every(pile => pile.length === 13);
        if (isWon && !gameWon.value) {
          gameWon.value = true;
          stopTimer(); // Timer stoppen bei Gewinn
          
          // Play victory sound - optional
          try {
            const victorySound = new Audio('/sounds/victory.mp3');
            victorySound.volume = 0.5;
            victorySound.play().catch(e => console.log('Auto-play prevented:', e));
          } catch (e) {
            console.log('Sound not supported:', e);
          }
          
          message.value = t('solitaireGame.winMessage');
          messageColor.value = 'success';
          showMessage.value = true;
        }
      };
      
      const formatTime = (seconds: number): string => {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
      };
      
      const getSuitSymbol = (suit: string): string => {
        const symbols: Record<string, string> = { hearts: '♥', diamonds: '♦', clubs: '♣', spades: '♠' };
        return symbols[suit] || '';
      };
      
      const startTimer = (): void => {
        if (gameTimer.value) clearInterval(gameTimer.value);
        
        // TypeScript fix for the timer
        const interval = setInterval(() => {
          if (gameStarted.value && !gameWon.value) {
            gameTime.value++;
          }
        }, 1000);
        
        gameTimer.value = interval;
      };
      
      const stopTimer = () => {
        if (gameTimer.value) {
          clearInterval(gameTimer.value);
          gameTimer.value = null;
        }
      };
      
      const tryAutoMoveToFoundation = (card: Card, source: string, pileIndex: number = -1, cardIndex: number = -1) => {
        // Nur für aufgedeckte Karten und nur wenn einzelne Karten (bei Tableau wichtig)
        if (!card.faceUp || (source === 'tableau' && cardIndex !== tableau.value[pileIndex].length - 1)) {
          return;
        }
        
        // Prüfe bei allen Foundation-Stapeln, ob die Karte passt
        for (let i = 0; i < 4; i++) {
          if (isValidFoundationMove(card, foundations.value[i])) {
            // Wir müssen dieselbe Logik wie bei selectedCard verwenden
            selectedCard.value = card;
            selectedSource.value = source;
            selectedPileIndex.value = pileIndex;
            selectedCardIndex.value = cardIndex;
            
            // Jetzt zum passenden Foundation-Stapel verschieben
            moveCardToFoundation(i);
            return;
          }
        }
        
        // Wenn kein passender Stapel gefunden wurde, nichts tun
      };
      
      return {
        // State
        stock,
        waste,
        foundations,
        tableau,
        selectedCard,
        selectedSource,
        selectedPileIndex,
        selectedCardIndex,
        draggedCard,
        dragSource,
        dragPileIndex,
        dragCardIndex,
        draggedStack,
        moves,
        gameTime,
        gameWon,
        message,
        messageColor,
        showMessage,
        
        // Computed
        displayedWaste,
        
        // Methods
        newGame,
        formatTime,
        drawCard,
        resetStock,
        selectCard,
        moveCardToFoundation,
        moveCardToTableau,
        canDropOnFoundation,
        canDropOnTableau,
        onDragOver,
        dragStart,
        dropOnFoundation,
        dropOnTableau,
        getSuitSymbol,
        tryAutoMoveToFoundation
      };
    }
  });
  </script>
  
  <style scoped>
/* Bewusst weiss und nicht aus den Merkern: das hier ist Inhalt, keine
   Bedienoberflaeche. Eine vorschau der Behoerden-Website, ein Whiteboard-Blatt,
   eine Spielkarte oder ein Mailtext folgen nicht dem Modus der Verwaltung -
   sie sehen aus, wie sie beim Empfaenger aussehen. */

  /* --- Exaktes Original-CSS --- */
  .solitaire-game {
    background-color: #0b5c2d; /* Green felt background */
    background-image: linear-gradient(rgba(0, 0, 0, 0.1) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(0, 0, 0, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    color: var(--k-ink);
    padding: 1rem;
    border-radius: 8px;
    width: 100%;
    max-width: 850px;
    margin: 0 auto;
    min-height: 600px;
    box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.5);
    position: relative;
    overflow: hidden;
  }
  
  /* Add a subtle table light effect */
  .solitaire-game::before {
    content: '';
    position: absolute;
    top: -100%;
    left: -100%;
    width: 300%;
    height: 300%;
    background: radial-gradient(
      circle at center,
      rgba(255, 255, 255, 0.08) 0%,
      rgba(0, 0, 0, 0) 70%
    );
    pointer-events: none;
  }
  
  .game-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    background-color: rgba(0, 0, 0, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    backdrop-filter: blur(2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }
  
  .game-header h2 {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    margin: 0;
  }
  
  .score-display {
    font-size: 1rem;
    color: var(--k-ink);
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
  }
  
  .game-board {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  .top-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start; /* Align elements at the top */
    margin-bottom: 10px;
  }
  
  .stock-waste {
    display: flex;
    gap: 1.5rem;
  }
  
  .foundation-piles {
    display: flex;
    gap: 1rem;
    align-items: flex-start; /* Align foundation piles at the top */
  }
  
  .tableau-piles {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    height: 350px; /* Original-Höhe */
    align-items: flex-start; /* Align tableau piles at the top */
    margin-bottom: 20px; /* Add margin at bottom for long stacks */
  }
  
  .card-pile {
    position: relative;
    width: 100px;
    height: 140px;
    border-radius: 8px;
    background-color: var(--k-row-hover);
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Change from center to flex-start to align at top */
    border: 1px dashed rgba(255, 255, 255, 0.2);
    padding-top: 5px; /* Add a small padding at top */
  }
  
  .tableau-pile {
    height: 100%; /* Original */
    align-items: flex-start; /* Align items at top */
  }
  
  .card {
    position: absolute;
    top: 0; /* Start from the top */
    width: 90px;
    height: 130px;
    border-radius: 6px;
    background-color: #fff;
    color: var(--k-ink);
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(0, 0, 0, 0.2);
    transition: transform 0.1s, box-shadow 0.1s;
    font-weight: bold;
    background-image: linear-gradient(to bottom, #ffffff, #f0f0f0);
    transform-origin: center top; /* Change origin to top */
    animation: card-deal 0.3s ease-out;
  }
  
  @keyframes card-deal {
    from {
      transform: translateY(-10px) scale(0.97);
      opacity: 0.5;
    }
    to {
      transform: translateY(0) scale(1);
      opacity: 1;
    }
  }
  
  .card-back {
    background-color: var(--k-accent-hover);
    background-image: repeating-linear-gradient(
      45deg,
      var(--k-accent-hover),
      var(--k-accent-hover) 10px,
      #1d4ed8 10px,
      #1d4ed8 20px
    );
    border: 2px solid #1e40af;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3);
    color: transparent;
  }
  
  .card-value {
    font-size: 1.5rem;
    line-height: 1;
  }
  
  .card-suit {
    font-size: 2rem;
    text-align: center;
  }
  
  .card-hearts, .card-diamonds {
    color: #ef4444;
  }
  
  .card-spades, .card-clubs {
    color: var(--k-ink);
  }
  
  .selectable { /* Hover effect for cards */
    cursor: pointer;
    transition: box-shadow 0.2s, filter 0.2s;
  }
  
  .selectable:hover {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    filter: brightness(102%);
  }
  
  /* Subtilerer Effekt für Tableau-Karten */
  .tableau-pile .selectable:hover {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    filter: brightness(101%);
  }
  
  .card-placeholder {
    width: 90px;
    height: 130px;
    border-radius: 6px;
    border: 2px dashed rgba(255, 255, 255, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--k-ink-faint);
    font-size: 2rem;
    background-color: rgba(0, 0, 0, 0.1);
  }
  
  .stock-pile .card-placeholder {
    cursor: pointer;
  }
  
  .stock-pile .card-placeholder:hover {
    background-color: var(--k-row-hover);
  }
  
  .suit-placeholder {
    font-size: 2rem;
    opacity: 0.3;
  }
  
  .drop-target { /* Original Drop Target Highlighting */
    background-color: var(--k-row-hover);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
  }
  
  /* Original Responsive adjustments */
  @media (max-width: 800px) {
    .solitaire-game {
      padding: 0.5rem;
    }
  
    .top-row {
      flex-direction: column;
      gap: 1rem;
    }
  
    .foundation-piles {
      justify-content: space-between;
    }
  
    .card-pile {
      width: 80px;
      height: 112px;
    }
  
    .card {
      width: 70px;
      height: 100px;
      padding: 0.3rem;
    }
  
    .card-value {
      font-size: 1.2rem;
    }
  
    .card-suit {
      font-size: 1.5rem;
    }
  
    .card-placeholder {
      width: 70px;
      height: 100px;
    }
  }
  
  /* Add a victory animation when game is won */
  @keyframes victory-flash {
    0%, 100% { 
      background-color: var(--k-row-hover);
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.3); 
    }
    50% { 
      background-color: rgba(16, 185, 129, 0.3);
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); 
    }
  }
  
  .foundation-pile.won .card {
    animation: victory-pulse 1.5s infinite;
  }
  
  @keyframes victory-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }

  /* Victory animations */
  .win-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 10;
    overflow: hidden;
  }

  .confetti-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  .confetti {
    position: absolute;
    top: -10%;
    left: var(--left-pos, 50%);
    width: 10px;
    height: 16px;
    background-color: var(--bg-color, #4ade80);
    opacity: 0.8;
    animation: fall var(--fall-duration, 4s) ease-in var(--fall-delay, 0s) infinite, 
               sway 3s ease-in-out alternate infinite;
  }

  .confetti:nth-child(odd) {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .confetti:nth-child(3n) {
    width: 8px;
    height: 12px;
    transform: rotate(45deg);
  }

  .confetti:nth-child(5n) {
    width: 12px;
    height: 8px;
    transform: rotate(-45deg);
  }

  @keyframes fall {
    0% {
      top: -10%;
      transform: translateX(0) rotate(0deg);
      opacity: 0;
    }
    10% {
      opacity: 1;
    }
    90% {
      opacity: 1;
    }
    100% {
      top: 100%;
      transform: translateX(20px) rotate(360deg);
      opacity: 0;
    }
  }

  @keyframes sway {
    0% {
      transform: translateX(-25px) rotate(0deg);
    }
    25% {
      transform: translateX(15px) rotate(90deg);
    }
    50% {
      transform: translateX(-15px) rotate(180deg);
    }
    75% {
      transform: translateX(25px) rotate(270deg);
    }
    100% {
      transform: translateX(-25px) rotate(360deg);
    }
  }

  /* Adjust card appearance inside foundation piles */
  .foundation-pile .card,
  .foundation-pile .card-placeholder {
    margin-top: 0; /* Remove any top margin */
  }

  /* Verbesserte Darstellung für gestapelte Karten */
  .stacked-card {
    visibility: visible !important; /* Stelle sicher, dass die Karten sichtbar sind */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2); /* Reduzierter Schatten für gestapelte Karten */
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 6px; /* Behalte den Radius bei */
  }

  /* Zeige für gestapelte Karten im Tableau einen Bereich der Karte an */
  .tableau-pile .stacked-card {
    height: 50px; /* Größere Höhe für bessere Sichtbarkeit */
    overflow: visible; /* Lasse den Inhalt sichtbar */
  }

  /* Bessere Anzeige der Kartenwerte in gestapelten Karten */
  .tableau-pile .stacked-card .card-value {
    font-size: 1.4rem; /* Leicht größerer Wert für bessere Sichtbarkeit */
    position: relative;
    top: 4px; /* Leicht nach unten verschoben */
    left: 2px; /* Leicht nach rechts verschoben */
    text-shadow: 0 0 2px white; /* Bessere Lesbarkeit */
  }

  .tableau-pile .stacked-card .card-suit {
    font-size: 1.2rem; /* Kleineres Symbol */
    position: absolute;
    top: 28px;
    right: 5px;
    display: block; /* Zeige das Symbol an */
  }

  /* Die oberste Karte hat z-index über allen anderen */
  .tableau-pile .card:last-child {
    z-index: 100 !important; /* Höchster z-index für die oberste Karte */
  }

  /* Anpassen der überlappenden Karten im tableau */
  .tableau-pile {
    padding-bottom: 200px; /* Mehr Platz für lange Kartenstapel */
  }
  </style>