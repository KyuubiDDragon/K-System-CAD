/**
 * Design-Tokens für hellen und dunklen Modus.
 *
 * Einzige Quelle der Wahrheit für Flächen, Linien, Text und Bedeutungsfarben.
 * Sowohl die Vuetify-Themes (plugins/vuetify.ts) als auch die CSS-Variablen
 * (utils/themeLoader.ts) werden hieraus erzeugt — damit können beide nicht
 * auseinanderlaufen.
 *
 * Alle Farbpaare sind gegen WCAG geprüft: 4.5:1 für Text, 3:1 für Spaltenköpfe.
 *
 * WICHTIG — `onFill` kippt mit dem Modus: Im dunklen Modus sind alle Füllfarben
 * heller als der Grund, weiße Schrift darauf wäre unlesbar (bis herunter auf
 * 2.16:1). Deshalb ist die Schrift auf gefüllten Flächen dort fast schwarz.
 *
 * Der Akzent (`accent`) wird pro Behörde aus `kdd_authorities.primary_color`
 * überschrieben. Die Bedeutungsfarben bleiben davon unberührt, damit Rot auch
 * bei einer Behörde mit violetter Hausfarbe noch Rot bedeutet.
 */

export interface ThemeTokens {
    canvas: string;
    surface: string;
    sunken: string;
    raised: string;
    line: string;
    lineStrong: string;
    ink: string;
    inkMuted: string;
    inkFaint: string;
    accent: string;
    accentHover: string;
    accentWeak: string;
    accentLine: string;
    onFill: string;
    critical: string;
    criticalWeak: string;
    warning: string;
    warningWeak: string;
    success: string;
    successWeak: string;
    neutral: string;
    neutralWeak: string;
    rowHover: string;
    rowSelect: string;
}

export const LIGHT_TOKENS: ThemeTokens = {
    canvas: '#F5F6F8',
    surface: '#FFFFFF',
    sunken: '#FAFBFC',
    raised: '#FFFFFF',
    line: '#E2E5EA',
    lineStrong: '#CED4DD',
    ink: '#171B22',
    inkMuted: '#5C6675',
    inkFaint: '#7D8794',
    accent: '#2B62C4',
    accentHover: '#24539F',
    accentWeak: '#E8EEFA',
    accentLine: '#B9CDF0',
    onFill: '#FFFFFF',
    critical: '#C0322B',
    criticalWeak: '#FBEAE9',
    warning: '#8F590B',
    warningWeak: '#FBF1E0',
    success: '#1B7A4F',
    successWeak: '#E4F4EC',
    neutral: '#5E6773',
    neutralWeak: '#EEF0F3',
    rowHover: '#F5F7FA',
    rowSelect: '#EAF1FC',
};

export const DARK_TOKENS: ThemeTokens = {
    canvas: '#0F1216',
    surface: '#161A20',
    sunken: '#12161B',
    raised: '#1C2128',
    line: '#262C35',
    lineStrong: '#363E4A',
    ink: '#E4E7EC',
    inkMuted: '#9AA4B2',
    inkFaint: '#6B7684',
    accent: '#558EE0',
    accentHover: '#6C9EE7',
    accentWeak: '#17263C',
    accentLine: '#2E4468',
    onFill: '#0F1216',
    critical: '#E5675C',
    criticalWeak: '#351D1C',
    warning: '#E0A648',
    warningWeak: '#332714',
    success: '#4FBF89',
    successWeak: '#14291F',
    neutral: '#8B96A4',
    neutralWeak: '#1E242B',
    rowHover: '#1A1F26',
    rowSelect: '#1A2534',
};

/** Maße des Dichte-Systems. Alle Werte liegen auf einem 4-px-Raster. */
export const DENSITY = {
    rowHeight: 36,
    rowHeightCompact: 30,
    rowHeightRelaxed: 44,
    controlHeight: 30,
    navItemHeight: 27,
    barHeight: 42,
    radiusSm: 3,
    radius: 5,
    radiusLg: 6,
} as const;

export function tokensFor(isDark: boolean): ThemeTokens {
    return isDark ? DARK_TOKENS : LIGHT_TOKENS;
}

/**
 * Wandelt die Tokens in CSS-Variablennamen um (`--k-canvas`, `--k-ink` …).
 * Der Präfix `k-` grenzt sie von den gewachsenen Variablen (`--primary`,
 * `--background` …) ab, die weiterhin von den Behörden-Einstellungen bedient
 * werden.
 */
export function tokensToCssVars(tokens: ThemeTokens): Record<string, string> {
    const vars: Record<string, string> = {};
    for (const [key, value] of Object.entries(tokens)) {
        const name = key.replace(/[A-Z]/g, m => '-' + m.toLowerCase());
        vars[`--k-${name}`] = value;
    }
    return vars;
}
