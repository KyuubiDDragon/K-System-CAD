<template>
  <div class="quacklejump-app">
    <!-- Game Header -->
    <v-card class="game-header mb-3" elevation="4" color="primary">
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center">
          <v-icon class="mr-2" size="32">mdi-duck</v-icon>
          <span class="text-h5 font-weight-bold">QuackleJump</span>
        </div>
        <div class="game-stats">
          <v-chip class="mr-2" color="yellow-darken-2" text-color="black">
            <v-icon start>mdi-star</v-icon>
            Score: {{ currentScore }}
          </v-chip>
          <v-chip color="orange-darken-2" text-color="white">
            <v-icon start>mdi-trophy</v-icon>
            High: {{ highScore }}
          </v-chip>
        </div>
      </v-card-title>
    </v-card>

    <!-- Game Container -->
    <v-card class="game-container" elevation="8">
      <div v-if="gameState === 'menu'" class="game-menu">
        <div class="menu-content">
          <v-img
            :src="duckLogo"
            class="duck-logo mb-4"
            width="150"
            height="150"
            contain
          />
          <h1 class="game-title mb-4">QuackleJump</h1>
          <p class="game-subtitle mb-6">Help Quackle reach new heights!</p>
          
          <v-btn
            size="x-large"
            color="primary"
            rounded="xl"
            @click="startGame"
            class="play-button mb-3"
          >
            <v-icon start>mdi-play</v-icon>
            Start Game
          </v-btn>
          
          <v-btn
            size="large"
            variant="outlined"
            rounded="xl"
            @click="showScoreboard = true"
            class="mb-3"
          >
            <v-icon start>mdi-format-list-numbered</v-icon>
            Scoreboard
          </v-btn>
          
          <v-btn
            size="large"
            variant="text"
            rounded="xl"
            @click="showInstructions = true"
          >
            <v-icon start>mdi-help-circle</v-icon>
            How to Play
          </v-btn>
        </div>
      </div>

      <div v-else-if="gameState === 'playing'" class="game-canvas-container">
        <canvas
          ref="gameCanvas"
          :width="canvasWidth"
          :height="canvasHeight"
          @click="handleClick"
        />
        
        <!-- In-game UI overlay -->
        <div class="game-overlay">
          <v-btn
            icon
            size="small"
            variant="flat"
            class="pause-button"
            @click="pauseGame"
          >
            <v-icon>mdi-pause</v-icon>
          </v-btn>
        </div>
      </div>

      <div v-else-if="gameState === 'paused'" class="game-paused">
        <v-card class="pause-menu" elevation="12">
          <v-card-title class="text-center">
            <v-icon size="48" color="primary">mdi-pause-circle</v-icon>
          </v-card-title>
          <v-card-text class="text-center">
            <h2 class="mb-4">Game Paused</h2>
            <v-btn
              color="primary"
              size="large"
              rounded
              @click="resumeGame"
              class="mb-2"
            >
              <v-icon start>mdi-play</v-icon>
              Resume
            </v-btn>
            <v-btn
              variant="outlined"
              size="large"
              rounded
              @click="backToMenu"
            >
              <v-icon start>mdi-home</v-icon>
              Main Menu
            </v-btn>
          </v-card-text>
        </v-card>
      </div>

      <div v-else-if="gameState === 'gameover'" class="game-over">
        <v-card class="gameover-card" elevation="12">
          <v-card-title class="text-center">
            <h2>Game Over!</h2>
          </v-card-title>
          <v-card-text>
            <div class="score-display text-center mb-4">
              <p class="text-h6">Your Score</p>
              <p class="text-h3 font-weight-bold">{{ currentScore }}</p>
              <v-chip
                v-if="isNewHighScore"
                color="yellow"
                text-color="black"
                class="mt-2"
              >
                <v-icon start>mdi-star</v-icon>
                New High Score!
              </v-chip>
            </div>
            
            <v-text-field
              v-model="playerName"
              label="Enter your name"
              placeholder="Quackle Master"
              variant="outlined"
              class="mb-4"
              :rules="[v => !!v || 'Name is required']"
              @keyup.enter="saveScore"
            />
            
            <div class="d-flex gap-2">
              <v-btn
                color="primary"
                variant="elevated"
                rounded
                block
                @click="saveScore"
                :disabled="!playerName"
              >
                <v-icon start>mdi-content-save</v-icon>
                Save Score
              </v-btn>
              <v-btn
                variant="outlined"
                rounded
                block
                @click="playAgain"
              >
                <v-icon start>mdi-restart</v-icon>
                Play Again
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </div>
    </v-card>

    <!-- Scoreboard Dialog -->
    <v-dialog v-model="showScoreboard" max-width="600">
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-trophy</v-icon>
          High Scores
        </v-card-title>
        <v-card-text>
          <v-table>
            <thead>
              <tr>
                <th class="text-center">Rank</th>
                <th>Player</th>
                <th class="text-right">Score</th>
                <th class="text-right">Height</th>
                <th class="text-center">Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(score, index) in highScores" :key="score.id">
                <td class="text-center">
                  <v-icon v-if="index === 0" color="yellow">mdi-trophy</v-icon>
                  <v-icon v-else-if="index === 1" color="grey">mdi-trophy</v-icon>
                  <v-icon v-else-if="index === 2" color="orange-darken-3">mdi-trophy</v-icon>
                  <span v-else>{{ index + 1 }}</span>
                </td>
                <td>{{ score.player_name }}</td>
                <td class="text-right font-weight-bold">{{ score.score }}</td>
                <td class="text-right">{{ score.height_reached }}m</td>
                <td class="text-center">{{ formatDate(score.created_at) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showScoreboard = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Instructions Dialog -->
    <v-dialog v-model="showInstructions" max-width="500">
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-help-circle</v-icon>
          How to Play
        </v-card-title>
        <v-card-text>
          <div class="instructions">
            <h3 class="mb-2">Controls</h3>
            <ul class="mb-4">
              <li><strong>← → Arrow Keys:</strong> Move Quackle left and right</li>
              <li><strong>Space:</strong> Use power-ups</li>
            </ul>
            
            <h3 class="mb-2">Levels</h3>
            <ul class="mb-4">
              <li><strong>Level 1 (0m):</strong> Sky - Easy start</li>
              <li><strong>Level 2 (200m):</strong> Clouds - More fragile platforms</li>
              <li><strong>Level 3 (500m):</strong> Space - Windstorms appear</li>
              <li><strong>Level 4 (1000m):</strong> Fire - Increased difficulty</li>
              <li><strong>Level 5 (2000m):</strong> Volcano - Lightning strikes!</li>
            </ul>
            
            <h3 class="mb-2">Platforms</h3>
            <ul class="mb-4">
              <li><v-icon color="brown" size="small">mdi-square</v-icon> <strong>Normal:</strong> Standard platform</li>
              <li><v-icon color="red" size="small">mdi-square</v-icon> <strong>Fragile:</strong> Breaks after landing</li>
              <li><v-icon color="blue" size="small">mdi-square</v-icon> <strong>Moving:</strong> Moves horizontally</li>
              <li><v-icon color="green" size="small">mdi-square</v-icon> <strong>Spring:</strong> Super jump!</li>
            </ul>
            
            <h3 class="mb-2">Power-Ups</h3>
            <ul>
              <li><v-icon color="purple" size="small">mdi-rocket</v-icon> <strong>Jetpack:</strong> Fly for 5 seconds</li>
              <li><v-icon color="yellow" size="small">mdi-star</v-icon> <strong>Super Jump:</strong> Triple jump height</li>
              <li><v-icon color="blue" size="small">mdi-shield</v-icon> <strong>Shield:</strong> Protection from enemies</li>
              <li><v-icon color="pink" size="small">mdi-balloon</v-icon> <strong>Balloon:</strong> Slow fall</li>
            </ul>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showInstructions = false">Got it!</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useToast } from 'vue-toastification';
import { apiClientAuth } from '@/api';
import { GameEngine } from './game/GameEngine';

// Game state
const gameState = ref<'menu' | 'playing' | 'paused' | 'gameover'>('menu');
const gameCanvas = ref<HTMLCanvasElement | null>(null);
const gameEngine = ref<GameEngine | null>(null);

// Game settings
const canvasWidth = 400;
const canvasHeight = 600;

// UI state
const showScoreboard = ref(false);
const showInstructions = ref(false);
const playerName = ref('');
const currentScore = ref(0);
const highScore = ref(0);
const highScores = ref<any[]>([]);
const isNewHighScore = computed(() => currentScore.value > highScore.value);

// Toast notifications
const toast = useToast();

// Placeholder duck logo
const duckLogo = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0MCIgZmlsbD0iI0ZGRDCWMCI+PC9jaXJjbGU+PGNpcmNsZSBjeD0iNDAiIGN5PSI0MCIgcj0iNSIgZmlsbD0iIzAwMCI+PC9jaXJjbGU+PGNpcmNsZSBjeD0iNjAiIGN5PSI0MCIgcj0iNSIgZmlsbD0iIzAwMCI+PC9jaXJjbGU+PHBhdGggZD0iTTM1IDYwIHE1IDEwIDE1IDEwIHQxNSAtMTAiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLXdpZHRoPSIyIiBmaWxsPSJub25lIj48L3BhdGg+PHBhdGggZD0iTTM1IDUwIGwxNSA1IGwxNSAtNSIgZmlsbD0iI0ZGNjM0NyI+PC9wYXRoPjwvc3ZnPg==';

// Game methods
const startGame = () => {
  gameState.value = 'playing';
  initializeGame();
};

const initializeGame = () => {
  // Wait for next tick to ensure canvas is rendered
  nextTick(() => {
    if (!gameCanvas.value) {
      console.error('Game canvas not found!');
      return;
    }
    
    console.log('Initializing game with canvas:', gameCanvas.value);
    
    // Initialize game engine
    gameEngine.value = new GameEngine(gameCanvas.value, {
      width: canvasWidth,
      height: canvasHeight,
      onScoreUpdate: (score: number) => {
        currentScore.value = score;
      },
      onGameOver: () => {
        gameState.value = 'gameover';
      }
    });
    
    gameEngine.value.start();
    console.log('Game started!');
  });
};

const pauseGame = () => {
  if (gameEngine.value) {
    gameEngine.value.pause();
    gameState.value = 'paused';
  }
};

const resumeGame = () => {
  if (gameEngine.value) {
    gameEngine.value.resume();
    gameState.value = 'playing';
  }
};

const backToMenu = () => {
  if (gameEngine.value) {
    gameEngine.value.destroy();
    gameEngine.value = null;
  }
  gameState.value = 'menu';
  currentScore.value = 0;
};

const playAgain = () => {
  playerName.value = '';
  currentScore.value = 0;
  gameState.value = 'playing';
  initializeGame();
};

// Input handlers
const handleClick = () => {
  if (gameEngine.value && gameState.value === 'playing') {
    gameEngine.value.handleClick();
  }
};

// Score management
const loadHighScores = async () => {
  try {
    const response = await apiClientAuth.get('/games/quacklejump/?action=getHighscores');
    highScores.value = response.data.scores || [];
    if (highScores.value.length > 0) {
      highScore.value = highScores.value[0].score;
    }
  } catch (error) {
    console.error('Failed to load high scores:', error);
  }
};

const saveScore = async () => {
  if (!playerName.value) {
    toast.warning('Please enter your name!');
    return;
  }
  
  try {
    await apiClientAuth.post('/games/quacklejump/?action=saveScore', {
      player_name: playerName.value,
      score: currentScore.value,
      height_reached: Math.floor(currentScore.value / 10), // Simplified height calculation
      play_time: gameEngine.value?.getPlayTime() || 0,
      power_ups_collected: gameEngine.value?.getPowerUpsCollected() || 0,
      enemies_defeated: gameEngine.value?.getEnemiesDefeated() || 0
    });
    
    toast.success('Score saved!');
    await loadHighScores();
    backToMenu();
  } catch (error) {
    console.error('Failed to save score:', error);
    toast.error('Failed to save score');
  }
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

// Lifecycle
onMounted(async () => {
  await loadHighScores();
});

onUnmounted(() => {
  if (gameEngine.value) {
    gameEngine.value.destroy();
  }
});
</script>

<style scoped>
.quacklejump-app {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: linear-gradient(to bottom, #87CEEB, #98D8E8);
  padding: 16px;
}

.game-header {
  background: linear-gradient(45deg, #1976D2, #42A5F5) !important;
}

.game-container {
  flex: 1;
  display: flex;
  position: relative;
  background: rgba(255, 255, 255, 0.9);
  overflow: hidden;
}

/* Menu Styles */
.game-menu {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(to bottom, #1a1a2e, #16213e);
}

.menu-content {
  text-align: center;
  padding: 32px;
}

.duck-logo {
  margin: 0 auto;
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.game-title {
  font-size: 3rem;
  font-weight: bold;
  color: #42A5F5;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.game-subtitle {
  font-size: 1.2rem;
  color: #B3E5FC;
}

.play-button {
  font-size: 1.2rem;
  padding: 12px 32px;
}

/* Canvas Styles */
.game-canvas-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

canvas {
  border: 4px solid #1976D2;
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  cursor: none;
}

.game-overlay {
  position: absolute;
  top: 16px;
  right: 16px;
}

.pause-button {
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Pause Menu */
.game-paused {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
}

.pause-menu {
  min-width: 300px;
  background: rgba(30, 41, 59, 0.95) !important;
  border: 1px solid var(--k-line);
}

/* Game Over */
.game-over {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
}

.gameover-card {
  min-width: 400px;
  background: rgba(30, 41, 59, 0.95) !important;
  border: 1px solid var(--k-line);
}

.score-display {
  padding: 16px;
  background: var(--k-row-hover);
  border-radius: 8px;
  border: 1px solid var(--k-line);
}

/* Instructions */
.instructions {
  line-height: 1.8;
}

.instructions ul {
  list-style: none;
  padding-left: 0;
}

.instructions li {
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Animations */
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.duck-logo {
  animation: bounce 2s infinite;
}

/* Responsive */
@media (max-width: 600px) {
  .game-title {
    font-size: 2rem;
  }
  
  .gameover-card {
    min-width: 90%;
  }
  
  canvas {
    max-width: 100%;
    height: auto;
  }
}
</style>