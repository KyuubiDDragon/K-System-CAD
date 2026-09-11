<template>
  <!--
    Der Rahmen folgt dem Entwurfssystem, die Spielflaeche nicht.

    Alles um das Spiel herum - Kopf, Menue, Bestenliste, Dialoge - ist normale
    Oberflaeche und traegt deshalb dieselben Flaechen, Hoehen und Farben wie
    der Rest der Anwendung. Das Bild im Canvas hat seine eigene Handschrift und
    bleibt unangetastet; ein Spiel darf aussehen wie ein Spiel.

    Vorher stand hier ein blauer Materialverlauf (#1976D2 auf #42A5F5) mit
    gelben und orangen Chips, xl-gerundeten Knoepfen und durchgehend englischem
    Text.
  -->
  <div class="qj">
    <section class="qj-panel">
      <header class="qj-kopf">
        <v-icon size="18" class="qj-kopf-symbol">mdi-duck</v-icon>
        <span class="qj-kopf-titel">{{ $t('games.quacklejump.titel') }}</span>

        <div class="qj-kennzahlen">
          <div class="qj-kennzahl">
            <span class="qj-kennzahl-titel">{{ $t('games.quacklejump.punkte') }}</span>
            <span class="qj-kennzahl-wert">{{ currentScore }}</span>
          </div>
          <div class="qj-kennzahl">
            <span class="qj-kennzahl-titel">{{ $t('games.quacklejump.bestwert') }}</span>
            <span class="qj-kennzahl-wert">{{ highScore }}</span>
          </div>
        </div>
      </header>

      <div class="qj-buehne">
        <!-- Menue -->
        <div v-if="gameState === 'menu'" class="qj-menue">
          <img :src="duckLogo" alt="" class="qj-logo" width="96" height="96" />
          <h1 class="qj-titel">{{ $t('games.quacklejump.titel') }}</h1>
          <p class="qj-untertitel">{{ $t('games.quacklejump.untertitel') }}</p>

          <div class="qj-knoepfe">
            <v-btn color="primary" variant="flat" @click="startGame">
              <v-icon start size="16">mdi-play</v-icon>
              {{ $t('games.quacklejump.starten') }}
            </v-btn>
            <v-btn variant="outlined" @click="showScoreboard = true">
              <v-icon start size="16">mdi-format-list-numbered</v-icon>
              {{ $t('games.quacklejump.bestenliste') }}
            </v-btn>
            <v-btn variant="text" @click="showInstructions = true">
              <v-icon start size="16">mdi-help-circle-outline</v-icon>
              {{ $t('games.quacklejump.anleitung') }}
            </v-btn>
          </div>
        </div>

        <!-- Spiel -->
        <div v-else-if="gameState === 'playing'" class="qj-canvas-rahmen">
          <canvas
            ref="gameCanvas"
            :width="canvasWidth"
            :height="canvasHeight"
            @click="handleClick"
          />
          <v-btn
            icon="mdi-pause"
            size="small"
            variant="flat"
            class="qj-pause"
            @click="pauseGame"
          />
        </div>

        <!-- Pause -->
        <div v-else-if="gameState === 'paused'" class="qj-menue">
          <v-icon size="40" class="qj-zustand-symbol">mdi-pause-circle-outline</v-icon>
          <h2 class="qj-zustand">{{ $t('games.quacklejump.pausiert') }}</h2>
          <div class="qj-knoepfe">
            <v-btn color="primary" variant="flat" @click="resumeGame">
              <v-icon start size="16">mdi-play</v-icon>
              {{ $t('games.quacklejump.fortsetzen') }}
            </v-btn>
            <v-btn variant="outlined" @click="backToMenu">
              <v-icon start size="16">mdi-home-outline</v-icon>
              {{ $t('games.quacklejump.zumMenue') }}
            </v-btn>
          </div>
        </div>

        <!-- Vorbei -->
        <div v-else-if="gameState === 'gameover'" class="qj-menue">
          <h2 class="qj-zustand">{{ $t('games.quacklejump.vorbei') }}</h2>

          <div class="qj-ergebnis">
            <span class="qj-kennzahl-titel">{{ $t('games.quacklejump.deinErgebnis') }}</span>
            <span class="qj-ergebnis-wert">{{ currentScore }}</span>
            <span v-if="isNewHighScore" class="qj-bestwert">
              <v-icon size="12">mdi-star</v-icon>
              {{ $t('games.quacklejump.neuerBestwert') }}
            </span>
          </div>

          <v-text-field
            v-model="playerName"
            :label="$t('games.quacklejump.nameEingeben')"
            :placeholder="$t('games.quacklejump.namePlatzhalter')"
            variant="outlined"
            density="compact"
            class="qj-namensfeld"
            @keyup.enter="saveScore"
          />

          <div class="qj-knoepfe">
            <v-btn color="primary" variant="flat" :disabled="!playerName.trim()" @click="saveScore">
              <v-icon start size="16">mdi-check</v-icon>
              {{ $t('games.quacklejump.eintragen') }}
            </v-btn>
            <v-btn variant="outlined" @click="playAgain">
              <v-icon start size="16">mdi-restart</v-icon>
              {{ $t('games.quacklejump.nochmal') }}
            </v-btn>
          </div>
        </div>
      </div>
    </section>

    <!-- Bestenliste -->
    <v-dialog v-model="showScoreboard" max-width="620">
      <v-card>
        <v-card-title>
          <v-icon size="18" class="mr-2">mdi-trophy-outline</v-icon>
          {{ $t('games.quacklejump.bestenliste') }}
        </v-card-title>
        <v-card-text>
          <p v-if="!highScores.length" class="qj-leer">
            {{ $t('games.quacklejump.nochKeineEintraege') }}
          </p>
          <table v-else class="qj-liste">
            <thead>
              <tr>
                <th class="qj-platz">{{ $t('games.quacklejump.spalte.platz') }}</th>
                <th>{{ $t('games.quacklejump.spalte.name') }}</th>
                <th class="qj-zahl">{{ $t('games.quacklejump.spalte.punkte') }}</th>
                <th class="qj-zahl">{{ $t('games.quacklejump.spalte.hoehe') }}</th>
                <th class="qj-zahl">{{ $t('games.quacklejump.spalte.datum') }}</th>
              </tr>
            </thead>
            <tbody>
              <!--
                Die ersten drei tragen einen Pokal in der Bedeutungsfarbe, nicht
                in Gold, Silber und Bronze aus der Materialpalette.
              -->
              <tr v-for="(eintrag, i) in highScores" :key="eintrag.id">
                <td class="qj-platz">
                  <v-icon v-if="i < 3" size="14" :class="'qj-rang-' + (i + 1)">mdi-trophy</v-icon>
                  <span v-else class="qj-rangzahl">{{ i + 1 }}</span>
                </td>
                <td>{{ eintrag.player_name }}</td>
                <td class="qj-zahl qj-punkte">{{ eintrag.score }}</td>
                <td class="qj-zahl">{{ $t('games.quacklejump.meter', { n: eintrag.height_reached }) }}</td>
                <td class="qj-zahl qj-datum">{{ formatDate(eintrag.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showScoreboard = false">
            {{ $t('games.quacklejump.schliessen') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Anleitung -->
    <v-dialog v-model="showInstructions" max-width="520">
      <v-card>
        <v-card-title>
          <v-icon size="18" class="mr-2">mdi-help-circle-outline</v-icon>
          {{ $t('games.quacklejump.anleitung') }}
        </v-card-title>
        <v-card-text>
          <h3 class="qj-abschnitt">{{ $t('games.quacklejump.steuerung') }}</h3>
          <ul class="qj-punkte-liste">
            <li>{{ $t('games.quacklejump.steuerungPfeile') }}</li>
            <li>{{ $t('games.quacklejump.steuerungLeer') }}</li>
          </ul>

          <h3 class="qj-abschnitt">{{ $t('games.quacklejump.ebenen') }}</h3>
          <ul class="qj-punkte-liste">
            <li>{{ $t('games.quacklejump.ebene1') }}</li>
            <li>{{ $t('games.quacklejump.ebene2') }}</li>
            <li>{{ $t('games.quacklejump.ebene3') }}</li>
            <li>{{ $t('games.quacklejump.ebene4') }}</li>
            <li>{{ $t('games.quacklejump.ebene5') }}</li>
          </ul>

          <h3 class="qj-abschnitt">{{ $t('games.quacklejump.plattformen') }}</h3>
          <ul class="qj-punkte-liste qj-plattformen">
            <li><span class="qj-marke qj-marke-normal"></span>{{ $t('games.quacklejump.plattformNormal') }}</li>
            <li><span class="qj-marke qj-marke-bruechig"></span>{{ $t('games.quacklejump.plattformBruechig') }}</li>
            <li><span class="qj-marke qj-marke-beweglich"></span>{{ $t('games.quacklejump.plattformBeweglich') }}</li>
            <li><span class="qj-marke qj-marke-feder"></span>{{ $t('games.quacklejump.plattformFeder') }}</li>
          </ul>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showInstructions = false">
            {{ $t('games.quacklejump.schliessen') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { formatDate } from '@/utils/datetime';
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
const { t } = useI18n();

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
    toast.error(t('games.quacklejump.ladeFehler'));
  }
};

const saveScore = async () => {
  if (!playerName.value.trim()) {
    toast.warning(t('games.quacklejump.nameNoetig'));
    return;
  }
  
  try {
    await apiClientAuth.post('/games/quacklejump/?action=saveScore', {
      player_name: playerName.value.trim(),
      score: currentScore.value,
      height_reached: Math.floor(currentScore.value / 10), // Simplified height calculation
      play_time: gameEngine.value?.getPlayTime() || 0,
      power_ups_collected: gameEngine.value?.getPowerUpsCollected() || 0,
      enemies_defeated: gameEngine.value?.getEnemiesDefeated() || 0
    });
    
    toast.success(t('games.quacklejump.gespeichert'));
    await loadHighScores();
    backToMenu();
  } catch (error) {
    console.error('Failed to save score:', error);
    toast.error(t('games.quacklejump.speicherFehler'));
  }
};

/*
   Das Datum kommt aus utils/datetime.

   Hier stand eine eigene Fassung mit toLocaleDateString() ohne Sprachangabe -
   sie richtete sich nach der Spracheinstellung des Browsers, nicht nach der
   der Anwendung. In neun anderen Bausteinen rief dasselbe Muster sich selbst
   auf und stuerzte ab.
*/

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
.qj {
    height: 100%;
    padding: 12px;
    background: var(--k-canvas);
    display: flex;
}

.qj-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    border-radius: 6px;
    overflow: hidden;
}

/* ---------- Kopf ---------- */
.qj-kopf {
    height: 34px;
    flex: none;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
}

.qj-kopf-symbol { color: var(--k-ink-faint); }

.qj-kopf-titel {
    font-size: 12px;
    font-weight: 600;
    color: var(--k-ink);
}

/* Punktstand und Bestwert stehen als Kennzahlen im Kopf: Bedeutung klein und
   gesperrt, die Zahl gross und in Festbreite, damit sie beim Zaehlen nicht
   springt. */
.qj-kennzahlen {
    margin-left: auto;
    display: flex;
    gap: 18px;
}

.qj-kennzahl {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.qj-kennzahl-titel {
    font-size: 10.5px;
    font-weight: 650;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--k-ink-faint);
}

.qj-kennzahl-wert {
    font-family: var(--k-mono);
    font-size: 13px;
    font-variant-numeric: tabular-nums;
    color: var(--k-ink);
}

/* ---------- Buehne ---------- */
.qj-buehne {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--k-canvas);
}

.qj-menue {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 28px;
    text-align: center;
}

.qj-logo {
    image-rendering: auto;
    margin-bottom: 4px;
}

.qj-titel {
    font-size: 25px;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--k-ink);
    margin: 0;
}

.qj-untertitel {
    font-size: 12.5px;
    color: var(--k-ink-muted);
    margin: 0 0 8px;
    max-width: 34ch;
}

.qj-zustand {
    font-size: 18px;
    font-weight: 620;
    color: var(--k-ink);
    margin: 0;
}

.qj-zustand-symbol { color: var(--k-ink-faint); }

.qj-knoepfe {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 200px;
}

/* ---------- Ergebnis ---------- */
.qj-ergebnis {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    margin: 4px 0 10px;
}

.qj-ergebnis-wert {
    font-size: 25px;
    font-weight: 600;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    color: var(--k-ink);
}

.qj-bestwert {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 20px;
    padding: 0 7px;
    margin-top: 4px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 550;
    background: var(--k-warning-weak);
    color: var(--k-warning);
}

.qj-namensfeld {
    width: 260px;
}

/* ---------- Spielflaeche ----------
   Ab hier hat das Spiel seine eigene Handschrift. Nur der Rahmen um das
   Canvas folgt noch dem System, damit es im Fenster nicht schwebt. */
.qj-canvas-rahmen {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qj-canvas-rahmen canvas {
    border: 1px solid var(--k-line);
    border-radius: 6px;
    display: block;
}

.qj-pause {
    position: absolute;
    top: 8px;
    right: 8px;
}

/* ---------- Bestenliste ---------- */
.qj-liste {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.qj-liste th {
    height: 30px;
    padding: 0 10px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 650;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--k-ink-faint);
    background: var(--k-sunken);
    border-bottom: 1px solid var(--k-line);
    white-space: nowrap;
}

.qj-liste td {
    height: 36px;
    padding: 0 10px;
    border-bottom: 1px solid var(--k-line);
    white-space: nowrap;
}

.qj-liste tbody tr:hover { background: var(--k-row-hover); }

/* Zahlen rechts und in Festbreite - so sind Ergebnisse untereinander
   vergleichbar. */
.qj-zahl {
    text-align: right;
    font-family: var(--k-mono);
    font-variant-numeric: tabular-nums;
    font-size: 12px;
}

.qj-punkte { font-weight: 600; color: var(--k-ink); }
.qj-datum { color: var(--k-ink-muted); }

.qj-platz {
    width: 54px;
    text-align: center;
}

.qj-rangzahl {
    font-family: var(--k-mono);
    font-size: 12px;
    color: var(--k-ink-faint);
}

.qj-rang-1 { color: var(--k-warning); }
.qj-rang-2 { color: var(--k-neutral); }
.qj-rang-3 { color: var(--k-critical); }

.qj-leer {
    font-size: 12.5px;
    color: var(--k-ink-muted);
    margin: 8px 0;
}

/* ---------- Anleitung ---------- */
.qj-abschnitt {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--k-ink);
    margin: 14px 0 6px;
}

.qj-abschnitt:first-child { margin-top: 0; }

.qj-punkte-liste {
    margin: 0;
    padding-left: 18px;
    font-size: 12.5px;
    line-height: 1.6;
    color: var(--k-ink-muted);
}

.qj-plattformen {
    list-style: none;
    padding-left: 0;
}

.qj-plattformen li {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Die Marke zeigt die Farbe, die die Plattform im Spiel traegt - hier als
   Bedeutungspunkt, wie ueberall sonst in der Anwendung. */
.qj-marke {
    width: 9px;
    height: 9px;
    border-radius: 2px;
    flex: none;
}

.qj-marke-normal { background: var(--k-neutral); }
.qj-marke-bruechig { background: var(--k-critical); }
.qj-marke-beweglich { background: var(--k-accent); }
.qj-marke-feder { background: var(--k-success); }
</style>
