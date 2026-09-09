<!-- Minesweeper Component -->
<template>
  <div class="minesweeper">
    <div class="minesweeper-header">
      <div class="mine-counter">🦆 {{ mineCount - flagCount }}</div>
      <button class="reset-button" @click="resetGame">{{ gameStatus === 'lost' ? '😵' : (gameStatus === 'won' ? '😎' : '🙂') }}</button>
      <div class="timer">⏱️ {{ timer }}</div>
    </div>
    <div class="minesweeper-grid" :style="gridStyle">
      <div 
        v-for="(cell, index) in grid" 
        :key="index" 
        class="minesweeper-cell"
        :class="getCellClass(cell)"
        @click="revealCell(index)"
        @contextmenu.prevent="toggleFlag(index)"
      >
        {{ getCellContent(cell) }}
      </div>
    </div>
  </div>
</template>

<script lang="ts">
// Define the Cell interface here, outside the component export
import { ref, computed, onMounted, onUnmounted, defineComponent } from 'vue';

interface Cell {
  isMine: boolean;
  isRevealed: boolean;
  isFlagged: boolean;
  adjacentMines: number;
}

export default defineComponent({
  name: 'Minesweeper',
  setup() {
    // Game configuration
    const rows = 10;
    const cols = 10;
    const mineCount = 15;

    // Game state
    const grid = ref<Cell[]>([]);
    const gameStatus = ref<'playing' | 'won' | 'lost'>('playing');
    const flagCount = ref(0);
    const timer = ref(0);
    let timerInterval: number | null = null;

    // Initialize game
    const initGame = () => {
      // Create empty grid
      grid.value = Array(rows * cols).fill(null).map(() => ({
        isMine: false,
        isRevealed: false,
        isFlagged: false,
        adjacentMines: 0
      }));

      // Place mines randomly
      let minesPlaced = 0;
      while (minesPlaced < mineCount) {
        const randomIndex = Math.floor(Math.random() * (rows * cols));
        if (!grid.value[randomIndex].isMine) {
          grid.value[randomIndex].isMine = true;
          minesPlaced++;
        }
      }

      // Calculate adjacent mines
      for (let i = 0; i < rows * cols; i++) {
        if (!grid.value[i].isMine) {
          const adjacentIndices = getAdjacentIndices(i);
          let count = 0;
          adjacentIndices.forEach(idx => {
            if (idx >= 0 && idx < rows * cols && grid.value[idx].isMine) {
              count++;
            }
          });
          grid.value[i].adjacentMines = count;
        }
      }
    };

    // Get adjacent cell indices (including diagonals)
    const getAdjacentIndices = (index: number) => {
      const row = Math.floor(index / cols);
      const col = index % cols;
      
      return [
        (row - 1) * cols + (col - 1), // top-left
        (row - 1) * cols + col,       // top
        (row - 1) * cols + (col + 1), // top-right
        row * cols + (col - 1),       // left
        row * cols + (col + 1),       // right
        (row + 1) * cols + (col - 1), // bottom-left
        (row + 1) * cols + col,       // bottom
        (row + 1) * cols + (col + 1), // bottom-right
      ].filter(idx => {
        const r = Math.floor(idx / cols);
        const c = idx % cols;
        return r >= 0 && r < rows && c >= 0 && c < cols;
      });
    };

    // Reveal a cell
    const revealCell = (index: number) => {
      // Can't reveal if game is over or cell is flagged
      if (gameStatus.value !== 'playing' || grid.value[index].isFlagged) {
        return;
      }

      // Start timer on first click
      if (!timerInterval) {
        startTimer();
      }

      const cell = grid.value[index];
      
      // Already revealed
      if (cell.isRevealed) {
        return;
      }
      
      // Reveal current cell
      cell.isRevealed = true;
      
      // Game over if it's a mine
      if (cell.isMine) {
        gameStatus.value = 'lost';
        revealAllMines();
        stopTimer();
        return;
      }
      
      // If it's a cell with no adjacent mines, reveal all adjacent cells
      if (cell.adjacentMines === 0) {
        const adjacentIndices = getAdjacentIndices(index);
        adjacentIndices.forEach(idx => {
          if (!grid.value[idx].isRevealed && !grid.value[idx].isFlagged) {
            revealCell(idx);
          }
        });
      }
      
      // Check if player has won
      checkWinCondition();
    };

    // Toggle flag on a cell
    const toggleFlag = (index: number) => {
      if (gameStatus.value !== 'playing' || grid.value[index].isRevealed) {
        return;
      }
      
      // Start timer on first flag
      if (!timerInterval) {
        startTimer();
      }
      
      const cell = grid.value[index];
      
      if (cell.isFlagged) {
        cell.isFlagged = false;
        flagCount.value--;
      } else if (flagCount.value < mineCount) {
        cell.isFlagged = true;
        flagCount.value++;
      }
    };

    // Reveal all mines when game is lost
    const revealAllMines = () => {
      grid.value.forEach(cell => {
        if (cell.isMine) {
          cell.isRevealed = true;
        }
      });
    };

    // Check if player has won
    const checkWinCondition = () => {
      const nonMineCount = rows * cols - mineCount;
      const revealedCount = grid.value.filter(cell => cell.isRevealed).length;
      
      if (revealedCount === nonMineCount) {
        gameStatus.value = 'won';
        // Flag all mines
        grid.value.forEach(cell => {
          if (cell.isMine && !cell.isFlagged) {
            cell.isFlagged = true;
            flagCount.value++;
          }
        });
        stopTimer();
      }
    };

    // Reset game
    const resetGame = () => {
      // Reset game state
      grid.value = [];
      gameStatus.value = 'playing';
      flagCount.value = 0;
      timer.value = 0;
      
      // Clear timer
      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
      
      // Initialize new game
      initGame();
    };

    // Start timer
    const startTimer = () => {
      if (timerInterval) {
        return;
      }
      
      timerInterval = window.setInterval(() => {
        timer.value++;
      }, 1000);
    };

    // Stop timer
    const stopTimer = () => {
      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
    };

    // Get cell class based on its state
    const getCellClass = (cell: Cell) => {
      if (cell.isRevealed) {
        if (cell.isMine) {
          return 'mine';
        }
        return cell.adjacentMines > 0 ? `adjacent-${cell.adjacentMines}` : 'empty';
      }
      
      return {
        'unrevealed': true,
        'flagged': cell.isFlagged
      };
    };

    // Get cell content based on its state
    const getCellContent = (cell: Cell) => {
      if (!cell.isRevealed) {
        return cell.isFlagged ? '🚩' : '';
      }
      
      if (cell.isMine) {
        return '🦆';
      }
      
      return cell.adjacentMines > 0 ? cell.adjacentMines : '';
    };

    // Grid style based on dimensions
    const gridStyle = computed(() => {
      return {
        gridTemplateColumns: `repeat(${cols}, 1fr)`,
        gridTemplateRows: `repeat(${rows}, 1fr)`
      };
    });

    // Initialize game on setup
    initGame();

    return {
      grid,
      gridStyle,
      mineCount,
      flagCount,
      timer,
      gameStatus,
      resetGame,
      revealCell,
      toggleFlag,
      getCellClass,
      getCellContent
    };
  }
});
</script>

<style scoped>
.minesweeper {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
  background-color: var(--k-sunken);
  border-radius: 8px;
  overflow: hidden;
  font-family: monospace;
}

.minesweeper-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  background-color: var(--k-ink);
  border-bottom: 1px solid var(--k-line);
  color: var(--k-ink);
}

.mine-counter, .timer {
  background-color: var(--k-sunken);
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 16px;
  min-width: 60px;
  text-align: center;
}

.reset-button {
  background-color: var(--k-accent);
  border: none;
  border-radius: 4px;
  width: 40px;
  height: 40px;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.reset-button:hover {
  background-color: var(--k-accent-hover);
}

.minesweeper-grid {
  flex: 1;
  display: grid;
  gap: 2px;
  background-color: var(--k-ink);
  padding: 12px;
  overflow: auto;
}

.minesweeper-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
  user-select: none;
  cursor: pointer;
}

.unrevealed {
  background-color: var(--k-accent);
  border-top: 4px solid #60a5fa;
  border-left: 4px solid #60a5fa;
  border-right: 4px solid #1d4ed8;
  border-bottom: 4px solid #1d4ed8;
}

.unrevealed:hover {
  filter: brightness(1.1);
  transform: scale(0.95);
  transition: all 0.1s ease;
}

.flagged {
  background-color: var(--k-accent);
  border-top: 4px solid #60a5fa;
  border-left: 4px solid #60a5fa;
  border-right: 4px solid #1d4ed8;
  border-bottom: 4px solid #1d4ed8;
}

.revealed {
  background-color: var(--k-sunken);
  border: 1px solid #0f172a;
  color: var(--k-ink);
}

.mine {
  background-color: #ef4444;
  border: 1px solid #0f172a;
  font-size: 22px; /* Größere Enten */
}

.adjacent-1 { color: var(--k-accent); }
.adjacent-2 { color: #10b981; }
.adjacent-3 { color: #ef4444; }
.adjacent-4 { color: #8b5cf6; }
.adjacent-5 { color: #f59e0b; }
.adjacent-6 { color: #06b6d4; }
.adjacent-7 { color: #ec4899; }
.adjacent-8 { color: #f43f5e; }
</style> 