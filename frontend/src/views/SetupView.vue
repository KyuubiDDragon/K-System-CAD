<template>
    <main class="einrichtung">
        <section class="einrichtung-karte">
            <!--
                Linke Spalte: worum es geht. Rechts das Formular - dieselbe
                Zweiteilung wie bei der Anmeldung, damit der erste Bildschirm
                der Anlage nicht aus dem System faellt.
            -->
            <aside class="einrichtung-aside">
                <div class="marke">
                    <span class="marke-zeichen">K</span>
                    <div>
                        <div class="marke-name">K-Systems</div>
                        <div class="marke-zusatz">Command &amp; Control</div>
                    </div>
                </div>

                <div>
                    <h1 class="titel">{{ $t('setup.title') }}</h1>
                    <p class="lede">{{ $t('setup.lede') }}</p>
                </div>

                <p class="hinweis">{{ $t('setup.onceOnly') }}</p>
            </aside>

            <div class="einrichtung-form">
                <h2 class="form-titel">{{ $t('setup.formTitle') }}</h2>

                <v-alert
                    v-if="fehler"
                    type="error"
                    variant="tonal"
                    density="compact"
                    class="mb-3"
                >{{ fehler }}</v-alert>

                <v-alert
                    v-if="fertig"
                    type="success"
                    variant="tonal"
                    density="compact"
                    class="mb-3"
                >{{ $t('setup.done') }}</v-alert>

                <v-form :disabled="laeuft || fertig" @submit.prevent="einrichten">
                    <v-text-field
                        v-model="benutzername"
                        :label="$t('setup.username')"
                        variant="outlined"
                        density="compact"
                        autocomplete="username"
                        :error-messages="fehlerFeld.username"
                    />

                    <v-text-field
                        v-model="email"
                        :label="$t('setup.email')"
                        type="email"
                        variant="outlined"
                        density="compact"
                        autocomplete="email"
                        :error-messages="fehlerFeld.email"
                    />

                    <v-text-field
                        v-model="passwort"
                        :label="$t('setup.password')"
                        :type="passwortSichtbar ? 'text' : 'password'"
                        variant="outlined"
                        density="compact"
                        autocomplete="new-password"
                        :hint="$t('setup.passwordHint', { n: MINDESTLAENGE })"
                        persistent-hint
                        :append-inner-icon="passwortSichtbar ? 'mdi-eye-off' : 'mdi-eye'"
                        :error-messages="fehlerFeld.password"
                        @click:append-inner="passwortSichtbar = !passwortSichtbar"
                    />

                    <v-text-field
                        v-model="passwortWdh"
                        :label="$t('setup.passwordRepeat')"
                        :type="passwortSichtbar ? 'text' : 'password'"
                        variant="outlined"
                        density="compact"
                        autocomplete="new-password"
                        :error-messages="fehlerFeld.passwordRepeat"
                        class="mt-4"
                    />

                    <v-btn
                        type="submit"
                        color="primary"
                        block
                        :loading="laeuft"
                        :disabled="fertig"
                        class="mt-4"
                    >{{ $t('setup.submit') }}</v-btn>
                </v-form>

                <p class="fuss">{{ $t('setup.rightsNote') }}</p>
            </div>
        </section>
    </main>
</template>

<script setup lang="ts">
/**
 * Ersteinrichtung.
 *
 * Diese Ansicht erscheint genau einmal: solange die Datenbank keinen einzigen
 * Benutzer kennt. Der hier angelegte Zugang bekommt die Rolle
 * "System Administrator" und damit saemtliche Rechte - der Grundstock der
 * Datenbank liefert bewusst kein Konto mehr mit.
 *
 * Die Weiche steht im Router: solange die Einrichtung offen ist, fuehrt jeder
 * Weg hierher, danach fuehrt keiner mehr her.
 */
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { apiClientPublic } from '@/api';
import { einrichtungNeuPruefen } from '@/router/einrichtung';

const MINDESTLAENGE = 12;

const router = useRouter();
const { t } = useI18n();

const benutzername = ref('');
const email = ref('');
const passwort = ref('');
const passwortWdh = ref('');
const passwortSichtbar = ref(false);

const laeuft = ref(false);
const fertig = ref(false);
const fehler = ref('');
const fehlerFeld = reactive<Record<string, string>>({
    username: '',
    email: '',
    password: '',
    passwordRepeat: '',
});

function feldfehlerLeeren() {
    for (const k of Object.keys(fehlerFeld)) fehlerFeld[k] = '';
    fehler.value = '';
}

/**
 * Prueft vor dem Absenden. Was hier auffaellt, muss nicht erst den Weg zum
 * Server und zurueck nehmen.
 */
function istVollstaendig(): boolean {
    feldfehlerLeeren();
    let ok = true;

    if (!benutzername.value.trim()) {
        fehlerFeld.username = t('setup.errors.usernameRequired');
        ok = false;
    }
    if (!email.value.trim()) {
        fehlerFeld.email = t('setup.errors.emailRequired');
        ok = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        fehlerFeld.email = t('setup.errors.emailInvalid');
        ok = false;
    }
    if (passwort.value.length < MINDESTLAENGE) {
        fehlerFeld.password = t('setup.errors.passwordShort', { n: MINDESTLAENGE });
        ok = false;
    }
    if (passwort.value !== passwortWdh.value) {
        fehlerFeld.passwordRepeat = t('setup.errors.passwordMismatch');
        ok = false;
    }

    return ok;
}

async function einrichten() {
    if (laeuft.value || fertig.value) return;
    if (!istVollstaendig()) return;

    laeuft.value = true;
    try {
        await apiClientPublic.post('/setup/?action=createFirstUser', {
            username: benutzername.value.trim(),
            email: email.value.trim(),
            password: passwort.value,
        });

        fertig.value = true;
        // Der Router darf den Zustand nicht aus dem Zwischenspeicher nehmen.
        einrichtungNeuPruefen();
        setTimeout(() => router.push('/'), 1200);
    } catch (e: any) {
        fehler.value = e?.response?.data?.error || t('setup.errors.failed');
    } finally {
        laeuft.value = false;
    }
}
</script>

<style scoped>
.einrichtung {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--k-canvas);
    padding: 24px;
}

.einrichtung-karte {
    width: min(980px, 100%);
    display: grid;
    grid-template-columns: 1fr;
    background: var(--k-surface);
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--k-shadow-lg, 0 18px 44px rgba(0, 0, 0, 0.28));
}

@media (min-width: 760px) {
    .einrichtung-karte {
        grid-template-columns: 1.05fr 1fr;
    }
}

.einrichtung-aside {
    background: var(--k-sunken);
    border-right: 1px solid var(--k-line);
    padding: 30px 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 28px;
}

.marke {
    display: flex;
    align-items: center;
    gap: 10px;
}

.marke-zeichen {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: var(--k-accent);
    color: var(--k-on-fill);
    font-family: var(--k-mono);
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.marke-name {
    font-size: 15px;
    font-weight: 620;
    letter-spacing: -0.01em;
    color: var(--k-ink);
}

.marke-zusatz {
    font-size: 10.5px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--k-ink-faint);
}

.titel {
    font-size: 18px;
    font-weight: 620;
    letter-spacing: -0.01em;
    color: var(--k-ink);
    margin: 0 0 6px;
}

.lede {
    font-size: 12.5px;
    color: var(--k-ink-muted);
    margin: 0;
    max-width: 36ch;
    line-height: 1.55;
}

.hinweis {
    font-size: 12px;
    color: var(--k-ink-faint);
    margin: 0;
    max-width: 36ch;
    line-height: 1.5;
}

.einrichtung-form {
    padding: 30px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: var(--k-surface);
}

.form-titel {
    font-size: 16px;
    font-weight: 620;
    color: var(--k-ink);
    margin: 0 0 14px;
}

.fuss {
    font-size: 11.5px;
    color: var(--k-ink-faint);
    margin: 14px 0 0;
    line-height: 1.5;
}
</style>
