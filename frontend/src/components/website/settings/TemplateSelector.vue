<template>
    <div class="settings-section template-selector">
        <h3>Aufbau</h3>
        <p class="section-description">
            Wie deine Website gegliedert ist. Der Aufbau lässt sich später wechseln – die
            Inhalte bleiben dabei erhalten.
        </p>

        <!--
            Hier standen fuenf Vorlagen, drei davon mit "Bald verfuegbar" und
            ohne Code dahinter. Sie nahmen den groessten Teil der Flaeche ein
            und waren nicht anklickbar. Geblieben sind die beiden, die es
            wirklich gibt - benannt wie im Anlegen-Dialog, damit man dieselbe
            Sache nicht unter zwei Namen wiederfindet.
        -->
        <div class="template-grid">
            <button
                v-for="v in VORLAGEN"
                :key="v.wert"
                type="button"
                class="template-card"
                :class="{ active: modelValue.layout_template === v.wert }"
                @click="selectTemplate(v.wert)"
            >
                <div class="template-preview">
                    <i :class="['mdi', v.symbol]"></i>
                </div>
                <div class="template-info">
                    <h4>{{ v.name }}</h4>
                    <p>{{ v.text }}</p>
                    <ul class="template-features">
                        <li v-for="m in v.merkmale" :key="m">
                            <i class="mdi mdi-check"></i> {{ m }}
                        </li>
                    </ul>
                </div>
                <div v-if="modelValue.layout_template === v.wert" class="active-badge">
                    <i class="mdi mdi-check-circle"></i> Aktiv
                </div>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
interface TemplateSettings {
    layout_template: string;
    template_settings?: any;
}

interface Props {
    modelValue: TemplateSettings;
}

/*
   Dieselben zwei Aufbauten wie im Anlegen-Dialog, in derselben Sprache. Die
   Schluessel bleiben 'default' und 'onepager' - daran haengen die Vorlagen im
   oeffentlichen Teil.
*/
const VORLAGEN = [
    {
        wert: 'default',
        symbol: 'mdi-file-document-multiple-outline',
        name: 'Mehrere Seiten',
        text: 'Startseite, Über uns, Kontakt – jede Seite für sich, verbunden über ein Menü.',
        merkmale: ['Seiten und Menü', 'Beiträge mit Kategorien'],
    },
    {
        wert: 'onepager',
        symbol: 'mdi-view-agenda-outline',
        name: 'Eine Seite',
        text: 'Alles untereinander auf einer Seite, von Abschnitt zu Abschnitt gescrollt.',
        merkmale: ['Abschnitte statt Seiten', 'Startaufbau: Hero, Über uns, Leistungen, Kontakt'],
    },
];

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: TemplateSettings): void;
    (e: 'change'): void;
}>();

function selectTemplate(template: string) {
    if (template === props.modelValue.layout_template) return;

    emit('update:modelValue', {
        ...props.modelValue,
        layout_template: template,
    });
    emit('change');
}
</script>

<style scoped>
.settings-section {
    background-color: var(--k-sunken);
    padding: 18px 20px;
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.settings-section h3 {
    font-size: 15px;
    font-weight: 620;
    letter-spacing: -0.01em;
    margin-bottom: 4px;
    color: var(--k-ink);
}

.section-description {
    color: var(--k-ink-muted);
    font-size: 12.5px;
    line-height: 1.5;
    max-width: 62ch;
    margin-bottom: 16px;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
}

/*
   Die Karte ist ein button, kein div - sie ist anklickbar und muss mit der
   Tastatur erreichbar sein. Deshalb die Rueckstellungen oben.
*/
.template-card {
    appearance: none;
    text-align: left;
    font: inherit;
    background-color: var(--k-surface);
    border: 1px solid var(--k-line);
    border-radius: 7px;
    padding: 14px 15px;
    cursor: pointer;
    position: relative;
    transition:
        border-color 140ms ease,
        background 140ms ease;
}

.template-card:hover {
    border-color: var(--k-line-strong);
}

.template-card:focus-visible {
    outline: 2px solid var(--k-accent);
    outline-offset: 2px;
}

.template-card.active {
    border-color: var(--k-accent);
    background-color: color-mix(in srgb, var(--k-accent) 8%, var(--k-surface));
}

/*
   Vorher ein 120 px hoher Farbverlauf je Karte - Dekoration ohne Aussage, und
   in fuenf verschiedenen Verlaeufen. Jetzt ein Symbol, das zeigt, worin die
   beiden sich unterscheiden: mehrere Blaetter gegen einen Stapel Abschnitte.
*/
.template-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 7px;
    background: var(--k-sunken);
    border: 1px solid var(--k-line);
    margin-bottom: 11px;
}

.template-preview i {
    font-size: 20px;
    color: var(--k-ink-faint);
}

.template-card.active .template-preview {
    border-color: var(--k-accent);
}

.template-card.active .template-preview i {
    color: var(--k-accent);
}

.template-info h4 {
    color: var(--k-ink);
    font-size: 13.5px;
    font-weight: 620;
    margin-bottom: 4px;
}

.template-info p {
    color: var(--k-ink-muted);
    font-size: 12px;
    margin-bottom: 10px;
    line-height: 1.45;
}

.template-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.template-features li {
    color: var(--k-ink-faint);
    font-size: 11.5px;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.template-features i {
    color: var(--k-success);
    font-size: 12px;
}

/* Schrift auf einer Fuellfarbe ist --k-on-fill, nicht --k-ink. */
.active-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: var(--k-accent);
    color: var(--k-on-fill);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.active-badge i {
    font-size: 12px;
}

@media (max-width: 768px) {
    .template-grid {
        grid-template-columns: 1fr;
    }
}
</style>
