<!-- Sudoku Component -->
<template>
    <div class="sudoku">
        <div class="sudoku-header">
            <div class="difficulty-selector">
<span class="difficulty-label">{{ t('sudokuGame.difficulty') }}:</span>
                <div class="difficulty-buttons">
                    <button
                        class="difficulty-button"
                        :class="{ active: difficulty === 'easy' }"
                        @click="setDifficulty('easy')"
                    >
                        {{ t('sudokuGame.easy') }}
                    </button>
                    <button
                        class="difficulty-button"
                        :class="{ active: difficulty === 'medium' }"
                        @click="setDifficulty('medium')"
                    >
                        {{ t('sudokuGame.medium') }}
                    </button>
                    <button
                        class="difficulty-button"
                        :class="{ active: difficulty === 'hard' }"
                        @click="setDifficulty('hard')"
                    >
                        {{ t('sudokuGame.hard') }}
                    </button>
                </div>
            </div>
            <button class="new-game-button" @click="newGame">{{ t('sudokuGame.newGame') }}</button>
            <div class="timer">⏱️ {{ timer }}</div>
        </div>
        <div class="sudoku-grid">
            <div
                v-for="(cell, index) in grid"
                :key="index"
                class="sudoku-cell"
                :class="{
                    original: cell.isOriginal,
                    selected: selectedCell === index,
                    'same-value':
                        selectedCell !== null &&
                        grid[selectedCell].value !== 0 &&
                        grid[selectedCell].value === cell.value,
                    error: cell.hasError,
                    'highlight-box': isInSameBox(index, selectedCell),
                    'highlight-row': isInSameRow(index, selectedCell),
                    'highlight-col': isInSameCol(index, selectedCell),
                    'box-border-top':
                        Math.floor(Math.floor(index / 9) / 3) * 3 === Math.floor(index / 9),
                    'box-border-bottom':
                        Math.floor(Math.floor(index / 9) / 3) * 3 + 2 === Math.floor(index / 9),
                    'box-border-left': Math.floor((index % 9) / 3) * 3 === index % 9,
                    'box-border-right': Math.floor((index % 9) / 3) * 3 + 2 === index % 9,
                }"
                @click="selectCell(index)"
            >
                {{ cell.value || '' }}
            </div>
        </div>
        <div class="number-pad">
            <button v-for="n in 9" :key="n" class="number-button" @click="placeNumber(n)">
                {{ n }}
            </button>
            <button class="number-button clear" @click="placeNumber(0)">{{ t('sudokuGame.clear') }}</button>
            <button class="number-button notes" @click="toggleNoteMode">
                {{ notesMode ? t('sudokuGame.exitNotes') : t('sudokuGame.notes') }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useToast, POSITION } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

// Game state
interface SudokuCell {
    value: number;
    isOriginal: boolean;
    notes: number[];
    hasError: boolean;
}

const toast = useToast();
const { t } = useI18n();
const grid = ref<SudokuCell[]>([]);
const selectedCell = ref<number | null>(null);
const difficulty = ref<'easy' | 'medium' | 'hard'>('easy');
const notesMode = ref(false);
const timer = ref(0);
let timerInterval: number | null = null;

// Computed properties
const difficultyLabel = computed(() => {
    return difficulty.value.charAt(0).toUpperCase() + difficulty.value.slice(1);
});

// Set difficulty
const setDifficulty = (level: 'easy' | 'medium' | 'hard') => {
    difficulty.value = level;
};

// Initialize the game
const initGame = () => {
    // Start with an empty grid
    grid.value = Array(81)
        .fill(null)
        .map(() => ({
            value: 0,
            isOriginal: false,
            notes: [],
            hasError: false,
        }));

    // Generate a solved Sudoku puzzle
    generateSolvedSudoku();

    // Remove numbers based on difficulty
    let cellsToRemove = 0;
    switch (difficulty.value) {
        case 'easy':
            cellsToRemove = 40; // 41 numbers displayed
            break;
        case 'medium':
            cellsToRemove = 50; // 31 numbers displayed
            break;
        case 'hard':
            cellsToRemove = 60; // 21 numbers displayed
            break;
    }

    // Remove random cells
    const indicesToRemove: number[] = [];
    while (indicesToRemove.length < cellsToRemove) {
        const randomIndex = Math.floor(Math.random() * 81);
        if (!indicesToRemove.includes(randomIndex)) {
            indicesToRemove.push(randomIndex);
        }
    }

    indicesToRemove.forEach(index => {
        grid.value[index].value = 0;
        grid.value[index].isOriginal = false;
    });

    // Mark all remaining non-zero cells as original
    grid.value.forEach(cell => {
        if (cell.value !== 0) {
            cell.isOriginal = true;
        }
    });
};

// Generate a valid Sudoku solution
const generateSolvedSudoku = () => {
    // Start with an empty board (with zeros)
    const board = Array(9)
        .fill(0)
        .map(() => Array(9).fill(0));

    // Use backtracking to fill the board
    const solve = (board: number[][]) => {
        for (let row = 0; row < 9; row++) {
            for (let col = 0; col < 9; col++) {
                // Find empty cells
                if (board[row][col] === 0) {
                    // Try placing numbers 1-9
                    const numbers = shuffleArray([1, 2, 3, 4, 5, 6, 7, 8, 9]);
                    for (const num of numbers) {
                        if (isValidPlacement(board, row, col, num)) {
                            board[row][col] = num;

                            // Recursively try to solve the rest of the board
                            if (solve(board)) {
                                return true;
                            }

                            // If placing this number doesn't lead to a solution, backtrack
                            board[row][col] = 0;
                        }
                    }
                    // If no number works, trigger backtracking
                    return false;
                }
            }
        }
        // Board is filled
        return true;
    };

    // Start solving
    solve(board);

    // Convert 2D board to flat grid
    for (let row = 0; row < 9; row++) {
        for (let col = 0; col < 9; col++) {
            const index = row * 9 + col;
            grid.value[index].value = board[row][col];
        }
    }
};

// Check if a number can be placed at a position
const isValidPlacement = (board: number[][], row: number, col: number, num: number) => {
    // Check row
    for (let c = 0; c < 9; c++) {
        if (board[row][c] === num) return false;
    }

    // Check column
    for (let r = 0; r < 9; r++) {
        if (board[r][col] === num) return false;
    }

    // Check 3x3 box
    const boxRow = Math.floor(row / 3) * 3;
    const boxCol = Math.floor(col / 3) * 3;
    for (let r = boxRow; r < boxRow + 3; r++) {
        for (let c = boxCol; c < boxCol + 3; c++) {
            if (board[r][c] === num) return false;
        }
    }

    return true;
};

// Shuffle an array (Fisher-Yates algorithm)
const shuffleArray = <T,>(array: T[]): T[] => {
    const result = [...array];
    for (let i = result.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [result[i], result[j]] = [result[j], result[i]];
    }
    return result;
};

// Start a new game
const newGame = () => {
    stopTimer();
    timer.value = 0;
    selectedCell.value = null;
    initGame();
    startTimer();
};

// Select a cell
const selectCell = (index: number) => {
    if (!timerInterval) {
        startTimer();
    }
    selectedCell.value = index;
};

// Place a number in the selected cell
const placeNumber = (num: number) => {
    if (selectedCell.value === null) return;

    const cell = grid.value[selectedCell.value];

    // Can't modify original cells
    if (cell.isOriginal) return;

    if (notesMode.value && num !== 0) {
        // Toggle note
        const noteIndex = cell.notes.indexOf(num);
        if (noteIndex === -1) {
            cell.notes.push(num);
        } else {
            cell.notes.splice(noteIndex, 1);
        }
    } else {
        // Place or clear value
        cell.value = num;
        cell.notes = [];

        // Check for errors (conflicts)
        validateBoard();

        // Check if puzzle is solved
        checkSolution();
    }
};

// Toggle notes mode
const toggleNoteMode = () => {
    notesMode.value = !notesMode.value;
};

// Validate the board to highlight errors
const validateBoard = () => {
    // Reset errors
    grid.value.forEach(cell => {
        cell.hasError = false;
    });

    // Check rows
    for (let row = 0; row < 9; row++) {
        const valuesSeen: Record<number, number[]> = {};
        for (let col = 0; col < 9; col++) {
            const index = row * 9 + col;
            const value = grid.value[index].value;
            if (value !== 0) {
                valuesSeen[value] = valuesSeen[value] || [];
                valuesSeen[value].push(index);
            }
        }
        // Mark duplicates as errors
        Object.values(valuesSeen).forEach(indices => {
            if (indices.length > 1) {
                indices.forEach(idx => {
                    grid.value[idx].hasError = true;
                });
            }
        });
    }

    // Check columns
    for (let col = 0; col < 9; col++) {
        const valuesSeen: Record<number, number[]> = {};
        for (let row = 0; row < 9; row++) {
            const index = row * 9 + col;
            const value = grid.value[index].value;
            if (value !== 0) {
                valuesSeen[value] = valuesSeen[value] || [];
                valuesSeen[value].push(index);
            }
        }
        // Mark duplicates as errors
        Object.values(valuesSeen).forEach(indices => {
            if (indices.length > 1) {
                indices.forEach(idx => {
                    grid.value[idx].hasError = true;
                });
            }
        });
    }

    // Check 3x3 boxes
    for (let boxRow = 0; boxRow < 3; boxRow++) {
        for (let boxCol = 0; boxCol < 3; boxCol++) {
            const valuesSeen: Record<number, number[]> = {};
            for (let row = boxRow * 3; row < boxRow * 3 + 3; row++) {
                for (let col = boxCol * 3; col < boxCol * 3 + 3; col++) {
                    const index = row * 9 + col;
                    const value = grid.value[index].value;
                    if (value !== 0) {
                        valuesSeen[value] = valuesSeen[value] || [];
                        valuesSeen[value].push(index);
                    }
                }
            }
            // Mark duplicates as errors
            Object.values(valuesSeen).forEach(indices => {
                if (indices.length > 1) {
                    indices.forEach(idx => {
                        grid.value[idx].hasError = true;
                    });
                }
            });
        }
    }
};

// Check if the puzzle is solved correctly
const checkSolution = () => {
    // Check if all cells are filled
    const allFilled = grid.value.every(cell => cell.value !== 0);

    // Check if there are no errors
    const noErrors = grid.value.every(cell => !cell.hasError);

    if (allFilled && noErrors) {
        // Puzzle is solved!
        console.log('Sudoku solved! 🎉');
        stopTimer();

        toast.success(
            t('sudokuGame.solved', {
                difficulty: difficultyLabel.value,
                seconds: timer.value,
            }),
            {
                timeout: 5000,
                position: POSITION.BOTTOM_RIGHT,
            }
        );
    }
};

// Helper functions for highlighting cells
const isInSameRow = (index1: number, index2: number | null) => {
    if (index2 === null) return false;
    return Math.floor(index1 / 9) === Math.floor(index2 / 9);
};

const isInSameCol = (index1: number, index2: number | null) => {
    if (index2 === null) return false;
    return index1 % 9 === index2 % 9;
};

const isInSameBox = (index1: number, index2: number | null) => {
    if (index2 === null) return false;
    const box1 = Math.floor(Math.floor(index1 / 9) / 3) * 3 + Math.floor((index1 % 9) / 3);
    const box2 = Math.floor(Math.floor(index2 / 9) / 3) * 3 + Math.floor((index2 % 9) / 3);
    return box1 === box2;
};

// Timer functions
const startTimer = () => {
    if (timerInterval) return;
    timerInterval = window.setInterval(() => {
        timer.value++;
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

// Lifecycle hooks
onMounted(() => {
    initGame();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<style scoped>
/*
   Die Farben kommen aus dem Entwurfssystem.

   Hier standen die alten Desktop-Variablen: --desktop-text ist fest weiss,
   --desktop-bg-dark-2 ein festes Dunkelblau. Beide kippen nicht mit dem Modus,
   im hellen Modus stand damit weisse Schrift auf hellem Grund. Abgebildet
   wurde nach Bedeutung - Schrift auf --k-ink, Flaechen auf --k-surface und
   --k-sunken, Linien auf --k-line, der Akzent auf --k-accent.

   Das Spielfeld selbst - die Rasterlinien, die Markierung der Dreierbloecke -
   behaelt seine Gestalt; es soll wie Sudoku aussehen, nicht wie eine Tabelle.
*/

.sudoku {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    font-family: 'Arial', sans-serif;
    user-select: none;
    background-color: color-mix(in srgb, var(--k-sunken) 20%, transparent);
    height: 100%;
    box-sizing: border-box;
}

.sudoku-header {
    display: flex;
    justify-content: space-between;
    width: 450px;
    margin-bottom: 20px;
    font-size: 16px;
    font-weight: bold;
    color: var(--k-ink);
}

.difficulty-selector {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.difficulty-label {
    margin-bottom: 5px;
    font-size: 14px;
    color: var(--k-ink);
}

.difficulty-buttons {
    display: flex;
    gap: 5px;
}

.difficulty-button {
    padding: 4px 8px;
    background-color: color-mix(in srgb, var(--k-surface) 50%, transparent);
    color: var(--k-ink);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.difficulty-button:hover {
    background-color: color-mix(in srgb, var(--k-accent) 30%, transparent);
}

.difficulty-button.active {
    background: linear-gradient(45deg, var(--k-accent), var(--k-accent));
    color: var(--k-ink);
    border-color: transparent;
    box-shadow: var(--shadow-small);
}

.new-game-button {
    padding: 5px 10px;
    background: linear-gradient(90deg, var(--k-accent), var(--k-accent));
    color: var(--k-ink);
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: var(--shadow-small);
}

.new-game-button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.sudoku-grid {
    display: grid;
    grid-template-columns: repeat(9, 50px);
    grid-template-rows: repeat(9, 50px);
    grid-gap: 0;
    border: 2px solid var(--k-ink);
    box-shadow: var(--shadow-medium);
}

.sudoku-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    box-sizing: border-box;
    border: 1px solid var(--k-line);
    font-size: 22px;
    font-weight: bold;
    cursor: pointer;
    background-color: color-mix(in srgb, var(--k-surface) 30%, transparent);
    color: var(--k-ink);
    position: relative;
    transition: background-color 0.15s;
}

.box-border-top {
    border-top: 2px solid var(--k-ink);
}

.box-border-bottom {
    border-bottom: 2px solid var(--k-ink);
}

.box-border-left {
    border-left: 2px solid var(--k-ink);
}

.box-border-right {
    border-right: 2px solid var(--k-ink);
}

.sudoku-cell.original {
    background-color: color-mix(in srgb, var(--k-sunken) 40%, transparent);
    color: var(--k-ink-faint);
}

.sudoku-cell.selected {
    background-color: color-mix(in srgb, var(--k-accent) 40%, transparent);
    color: var(--k-ink);
}

.sudoku-cell.highlight-row,
.sudoku-cell.highlight-col,
.sudoku-cell.highlight-box {
    background-color: color-mix(in srgb, var(--k-accent) 15%, transparent);
}

.sudoku-cell.selected {
    z-index: 1;
}

.sudoku-cell.error {
    color: var(--error);
}

.sudoku-cell.same-value {
    background-color: color-mix(in srgb, var(--k-success) 20%, transparent);
}

.number-pad {
    display: grid;
    grid-template-columns: repeat(5, 50px);
    grid-gap: 10px;
    margin-top: 20px;
}

.number-button {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    background-color: color-mix(in srgb, var(--k-surface) 50%, transparent);
    color: var(--k-ink);
    border: 1px solid var(--k-line);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: var(--shadow-small);
}

.number-button:hover {
    background-color: color-mix(in srgb, var(--k-accent) 30%, transparent);
    transform: translateY(-2px);
}

.number-button.clear,
.number-button.notes {
    font-size: 14px;
    grid-column: span 2;
    width: 110px;
}

.number-button.clear {
    background-color: rgba(var(--error-raw), 0.2);
    color: var(--error);
}

.number-button.clear:hover {
    background-color: rgba(var(--error-raw), 0.3);
}

.number-button.notes {
    background-color: color-mix(in srgb, var(--k-success) 20%, transparent);
    color: var(--success);
}

.number-button.notes:hover {
    background-color: color-mix(in srgb, var(--k-success) 30%, transparent);
}
</style>
