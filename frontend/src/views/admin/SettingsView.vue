<template>
    <v-container fluid>
        <v-row justify="center">
            <v-col cols="12" md="8" lg="6">
                <v-card :loading="loadingSettings || savingSettings" class="settings-card">
                    <v-card-title class="settings-title">
                        <v-icon icon="mdi-cog-outline" class="mr-2" />
                        {{ t('settingsView.title') }}
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-form ref="formRef" v-model="isFormValid">
                            <!-- Basiseinstellungen -->
                            <v-expand-panel-group v-model="expandedPanels" multiple>
                                <v-expand-panel value="basic" elevation="0" class="settings-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-application" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium"
                                                >{{ t('settingsView.basicSettings') }}</span
                                            >
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-text-field
                                            v-model="globalSettings.settings.siteName"
                                            :label="t('settingsView.siteName')"
                                            :hint="t('settingsView.siteNameHint')"
                                            persistent-hint
                                            :rules="[rules.required]"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="globalSettings.settings.siteLogo"
                                            :label="t('settingsView.siteLogoUrl')"
                                            :hint="t('settingsView.siteLogoHint')"
                                            persistent-hint
                                            :rules="[rules.required]"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        >
                                            <template v-slot:append-inner>
                                                <v-avatar size="24" class="mr-n2">
                                                    <v-img
                                                        v-if="globalSettings.settings.siteLogo"
                                                        :src="globalSettings.settings.siteLogo"
                                                        alt="Logo Preview"
                                                        @error="onLogoError"
                                                    ></v-img>
                                                    <v-icon v-else>mdi-image-off-outline</v-icon>
                                                </v-avatar>
                                            </template>
                                        </v-text-field>
                                        <v-text-field
                                            v-model="globalSettings.settings.employeeNavigation"
                                            :label="t('settingsView.employeeNavigation')"
                                            :hint="t('settingsView.employeeNavigationHint')"
                                            persistent-hint
                                            :rules="[rules.required]"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="globalSettings.settings.companyName"
                                            :label="t('settingsView.companyName')"
                                            :hint="t('settingsView.companyNameHint')"
                                            persistent-hint
                                            :rules="[rules.required]"
                                            variant="outlined"
                                            density="compact"
                                        ></v-text-field>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- Theme-Einstellungen -->
                                <v-expand-panel value="theme" elevation="0" class="settings-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-palette" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium"
                                                >Designeinstellungen</span
                                            >
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <div class="d-flex align-center mb-4">
                                            <v-switch
                                                v-model="globalSettings.settings.darkMode"
                                                density="compact"
                                                color="primary"
                                                :label="
                                                    globalSettings.settings.darkMode
                                                        ? 'Dunkles Design (Standard)'
                                                        : 'Helles Design'
                                                "
                                                hide-details
                                            ></v-switch>
                                            <v-spacer></v-spacer>
                                            <v-btn
                                                size="small"
                                                variant="text"
                                                prepend-icon="mdi-refresh"
                                                @click="resetThemeDefaults"
                                            >
                                                Standardfarben
                                            </v-btn>
                                        </div>
                                        
                                        <v-alert
                                            type="info"
                                            variant="tonal"
                                            closable
                                            class="mb-4 text-caption"
                                            dense
                                        >
                                            <p class="font-weight-medium mb-2">Farbsystem Erklärung:</p>
                                            <ul class="ml-4">
                                                <li><b>Primärfarbe:</b> Hauptfarbe der Anwendung, für wichtige Elemente und Buttons</li>
                                                <li><b>Sekundärfarbe:</b> Zweite Farbe für alternative Elemente und Kontraste</li>
                                                <li><b>Akzentfarbe:</b> Hervorhebungen, Links und interaktive Elemente</li>
                                                <li><b>Hintergrund:</b> Gesamter App-Hintergrund</li>
                                                <li><b>Oberfläche:</b> Karten, Container und modale Fenster</li>
                                                <li><b>"Text auf"-Farben:</b> Legen die Textfarbe auf den jeweiligen Hintergründen fest</li>
                                            </ul>
                                        </v-alert>

                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Primärfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.primary = !colorPickers.primary
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.primaryColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div
                                                    class="color-preview primary-preview"
                                                    :style="{
                                                        backgroundColor: getValidColor(
                                                            globalSettings.settings.primaryColor
                                                        ),
                                                    }"
                                                ></div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.primary"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.primaryColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                        
                                        <!-- Sekundärfarbe -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Sekundärfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.secondary = !colorPickers.secondary
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.secondaryColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div
                                                    class="color-preview secondary-preview"
                                                    :style="{
                                                        backgroundColor: getValidColor(
                                                            globalSettings.settings.secondaryColor
                                                        ),
                                                    }"
                                                ></div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.secondary"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.secondaryColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>

                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Akzentfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.accent = !colorPickers.accent
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.accentColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div
                                                    class="color-preview accent-preview"
                                                    :style="{
                                                        backgroundColor: getValidColor(
                                                            globalSettings.settings.accentColor
                                                        ),
                                                    }"
                                                ></div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.accent"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.accentColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>

                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Hintergrund</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.background =
                                                            !colorPickers.background
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="
                                                        globalSettings.settings.backgroundColor
                                                    "
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div
                                                    class="color-preview bg-preview"
                                                    :style="{
                                                        backgroundColor: getValidColor(
                                                            globalSettings.settings.backgroundColor
                                                        ),
                                                    }"
                                                ></div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.background"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.backgroundColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>

                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Oberflächen-Farbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.surface = !colorPickers.surface
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.surfaceColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div
                                                    class="color-preview surface-preview"
                                                    :style="{
                                                        backgroundColor: getValidColor(
                                                            globalSettings.settings.surfaceColor
                                                        ),
                                                    }"
                                                ></div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.surface"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.surfaceColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>

                                        <div class="d-flex align-center justify-space-between mt-3">
                                            <v-switch
                                                v-model="globalSettings.settings.enableGradients"
                                                density="compact"
                                                color="primary"
                                                label="Hintergrund-Verläufe aktivieren"
                                                hide-details
                                            ></v-switch>
                                        </div>
                                        
                                        <!-- Text-Farben ("On" Colors) -->
                                        <v-divider class="my-4"></v-divider>
                                        <div class="d-flex align-center mb-3">
                                            <span class="text-h6">Text & Kontrast-Farben</span>
                                            <v-spacer></v-spacer>
                                            <v-tooltip location="top" text="Diese Farben werden für Text und Elemente auf farbigen Hintergründen verwendet">
                                                <template v-slot:activator="{ props }">
                                                    <v-icon v-bind="props" icon="mdi-information-outline" size="small"></v-icon>
                                                </template>
                                            </v-tooltip>
                                        </div>
                                        
                                        <!-- On-Primary Color -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Text auf Primärfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.onPrimary = !colorPickers.onPrimary
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.onPrimaryColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div class="d-flex color-preview-container">
                                                    <div
                                                        class="color-preview-base"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.primaryColor
                                                            ),
                                                        }"
                                                    ></div>
                                                    <div
                                                        class="color-preview on-preview"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.primaryColor
                                                            ),
                                                            color: getValidColor(
                                                                globalSettings.settings.onPrimaryColor
                                                            ),
                                                        }"
                                                    >
                                                        Aa
                                                    </div>
                                                </div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.onPrimary"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.onPrimaryColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                        
                                        <!-- On-Secondary Color -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Text auf Sekundärfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.onSecondary = !colorPickers.onSecondary
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.onSecondaryColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div class="d-flex color-preview-container">
                                                    <div
                                                        class="color-preview-base"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.secondaryColor
                                                            ),
                                                        }"
                                                    ></div>
                                                    <div
                                                        class="color-preview on-preview"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.secondaryColor
                                                            ),
                                                            color: getValidColor(
                                                                globalSettings.settings.onSecondaryColor
                                                            ),
                                                        }"
                                                    >
                                                        Aa
                                                    </div>
                                                </div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.onSecondary"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.onSecondaryColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                        
                                        <!-- On-Accent Color -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Text auf Akzentfarbe</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.onAccent = !colorPickers.onAccent
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.onAccentColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div class="d-flex color-preview-container">
                                                    <div
                                                        class="color-preview-base"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.accentColor
                                                            ),
                                                        }"
                                                    ></div>
                                                    <div
                                                        class="color-preview on-preview"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.accentColor
                                                            ),
                                                            color: getValidColor(
                                                                globalSettings.settings.onAccentColor
                                                            ),
                                                        }"
                                                    >
                                                        Aa
                                                    </div>
                                                </div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.onAccent"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.onAccentColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                        
                                        <!-- On-Background Color -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Text auf Hintergrund</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.onBackground = !colorPickers.onBackground
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.onBackgroundColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div class="d-flex color-preview-container">
                                                    <div
                                                        class="color-preview-base"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.backgroundColor
                                                            ),
                                                        }"
                                                    ></div>
                                                    <div
                                                        class="color-preview on-preview"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.backgroundColor
                                                            ),
                                                            color: getValidColor(
                                                                globalSettings.settings.onBackgroundColor
                                                            ),
                                                        }"
                                                    >
                                                        Aa
                                                    </div>
                                                </div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.onBackground"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.onBackgroundColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                        
                                        <!-- On-Surface Color -->
                                        <div class="color-selector mb-4">
                                            <div class="d-flex align-center mb-2">
                                                <span class="text-subtitle-2">Text auf Oberfläche</span>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                    icon="mdi-palette"
                                                    size="small"
                                                    variant="text"
                                                    @click="
                                                        colorPickers.onSurface = !colorPickers.onSurface
                                                    "
                                                ></v-btn>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-text-field
                                                    v-model="globalSettings.settings.onSurfaceColor"
                                                    label="HEX-Wert"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-inner-icon="mdi-pound"
                                                    :rules="[rules.hexColor]"
                                                    class="mr-2"
                                                ></v-text-field>
                                                <div class="d-flex color-preview-container">
                                                    <div
                                                        class="color-preview-base"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.surfaceColor
                                                            ),
                                                        }"
                                                    ></div>
                                                    <div
                                                        class="color-preview on-preview"
                                                        :style="{
                                                            backgroundColor: getValidColor(
                                                                globalSettings.settings.surfaceColor
                                                            ),
                                                            color: getValidColor(
                                                                globalSettings.settings.onSurfaceColor
                                                            ),
                                                        }"
                                                    >
                                                        Aa
                                                    </div>
                                                </div>
                                            </div>
                                            <v-expand-transition>
                                                <v-card
                                                    v-if="colorPickers.onSurface"
                                                    flat
                                                    border
                                                    class="mt-2"
                                                >
                                                    <v-color-picker
                                                        v-model="
                                                            globalSettings.settings.onSurfaceColor
                                                        "
                                                        mode="hexa"
                                                        hide-inputs
                                                        show-swatches
                                                    ></v-color-picker>
                                                </v-card>
                                            </v-expand-transition>
                                        </div>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- Systemeinstellungen -->
                                <v-expand-panel value="system" elevation="0" class="settings-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-tune" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium"
                                                >Systemeinstellungen</span
                                            >
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-select
                                            v-model="globalSettings.settings.defaultLanguage"
                                            label="Standardsprache"
                                            :items="languageOptions"
                                            item-title="name"
                                            item-value="code"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-select>

                                        <v-select
                                            v-model="globalSettings.settings.dateFormat"
                                            label="Datumsformat"
                                            :items="dateFormatOptions"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-select>

                                        <v-select
                                            v-model="globalSettings.settings.defaultStartPage"
                                            label="Standard-Startseite"
                                            :items="startPageOptions"
                                            item-title="name"
                                            item-value="route"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-select>

                                        <v-slider
                                            v-model="globalSettings.settings.sessionTimeout"
                                            label="Session-Timeout (Minuten)"
                                            hint="Zeit der Inaktivität bis zur automatischen Abmeldung"
                                            persistent-hint
                                            min="5"
                                            max="240"
                                            step="5"
                                            thumb-label
                                            color="primary"
                                            class="mb-4"
                                        ></v-slider>

                                        <v-switch
                                            v-model="globalSettings.settings.enableNotifications"
                                            density="compact"
                                            color="primary"
                                            label="Desktop-Benachrichtigungen aktivieren"
                                            hint="Zeigt Benachrichtigungen für neue Nachrichten und andere wichtige Ereignisse an"
                                            persistent-hint
                                            class="mb-4"
                                        ></v-switch>

                                        <v-switch
                                            v-model="globalSettings.settings.enableUserTracking"
                                            density="compact"
                                            color="primary"
                                            label="Benutzeraktivitäten protokollieren"
                                            hint="Protokolliert Anmeldungen und wichtige Systemaktionen für Sicherheits- und Audit-Zwecke"
                                            persistent-hint
                                        ></v-switch>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- Branding und Export -->
                                <v-expand-panel
                                    value="branding"
                                    elevation="0"
                                    class="settings-panel"
                                >
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-export-variant" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium"
                                                >Branding & Export</span
                                            >
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-text-field
                                            v-model="globalSettings.settings.pdfHeaderLogo"
                                            label="PDF Header Logo URL"
                                            hint="Logo das in exportierten PDFs angezeigt wird"
                                            persistent-hint
                                            :rules="[rules.url]"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        >
                                            <template v-slot:append-inner>
                                                <v-avatar size="24" class="mr-n2">
                                                    <v-img
                                                        v-if="globalSettings.settings.pdfHeaderLogo"
                                                        :src="globalSettings.settings.pdfHeaderLogo"
                                                        alt="PDF Logo"
                                                        @error="onLogoError"
                                                    ></v-img>
                                                    <v-icon v-else>mdi-image-off-outline</v-icon>
                                                </v-avatar>
                                            </template>
                                        </v-text-field>

                                        <v-text-field
                                            v-model="globalSettings.settings.pdfFooterText"
                                            label="PDF Fußzeilentext"
                                            hint="Text der in der Fußzeile von exportierten PDFs erscheint"
                                            persistent-hint
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-text-field>

                                        <v-select
                                            v-model="globalSettings.settings.exportDateFormat"
                                            label="Export Datumsformat"
                                            :items="dateFormatOptions"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-select>

                                        <v-select
                                            v-model="globalSettings.settings.defaultExportFormat"
                                            label="Standard Exportformat"
                                            :items="['PDF', 'XLSX', 'CSV', 'JSON']"
                                            variant="outlined"
                                            density="compact"
                                        ></v-select>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- Kontakt- und Support-Informationen -->
                                <v-expand-panel
                                    value="contact"
                                    elevation="0"
                                    class="settings-panel"
                                >
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-help-circle-outline" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium"
                                                >Kontakt & Support</span
                                            >
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-text-field
                                            v-model="globalSettings.settings.supportEmail"
                                            label="Support E-Mail"
                                            hint="E-Mail-Adresse für Supportanfragen"
                                            persistent-hint
                                            :rules="[rules.email]"
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-text-field>

                                        <v-text-field
                                            v-model="globalSettings.settings.supportPhone"
                                            label="Support Telefon"
                                            hint="Telefonnummer für Supportanfragen"
                                            persistent-hint
                                            variant="outlined"
                                            density="compact"
                                            class="mb-4"
                                        ></v-text-field>

                                        <v-textarea
                                            v-model="globalSettings.settings.helpPageContent"
                                            label="Hilfeseiten-Inhalt"
                                            hint="Kurzer Text oder Link zu Hilfedokumenten"
                                            persistent-hint
                                            variant="outlined"
                                            density="compact"
                                            auto-grow
                                            rows="3"
                                        ></v-textarea>
                                    </v-expand-panel-text>
                                </v-expand-panel>
                            </v-expand-panel-group>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-3">
                        <v-btn
                            color="info"
                            variant="text"
                            @click="previewSettings"
                            :disabled="!isFormValid || savingSettings"
                            class="me-2"
                        >
                            <v-icon icon="mdi-eye" class="mr-1" />
                            Vorschau
                        </v-btn>
                        <v-btn
                            @click="saveSettings"
                            :disabled="!isFormValid"
                            :loading="savingSettings"
                            color="primary"
                            variant="flat"
                        >
                            <v-icon icon="mdi-content-save" class="mr-1" />
                            Speichern
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <!-- Preview Dialog -->
        <v-dialog v-model="previewDialogOpen" max-width="800" scrollable>
            <v-card class="preview-dialog">
                <v-card-title class="preview-title">
                    <v-icon icon="mdi-eye" class="mr-2" />
                    Designvorschau
                    <v-spacer></v-spacer>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        @click="previewDialogOpen = false"
                    ></v-btn>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text class="preview-content">
                    <div class="preview-container" :style="previewStyles">
                        <div class="preview-header">
                            <div class="preview-logo">
                                <v-img
                                    :src="globalSettings.settings.siteLogo"
                                    height="32"
                                    width="32"
                                    contain
                                    class="mr-2"
                                ></v-img>
                                <span class="preview-site-name">{{
                                    globalSettings.settings.siteName
                                }}</span>
                            </div>
                            <div class="preview-nav">
                                <div class="preview-nav-item active">Dashboard</div>
                                <div class="preview-nav-item">
                                    {{ globalSettings.settings.employeeNavigation }}
                                </div>
                                <div class="preview-nav-item">Einstellungen</div>
                            </div>
                        </div>
                        <div class="preview-main">
                            <div class="preview-sidebar">
                                <div class="preview-sidebar-item active">Übersicht</div>
                                <div class="preview-sidebar-item">Nachrichten</div>
                                <div class="preview-sidebar-item">Kalender</div>
                                <div class="preview-sidebar-item">Dokumente</div>
                            </div>
                            <div class="preview-content-area">
                                <div class="preview-card">
                                    <div class="preview-card-title">Dashboard</div>
                                    <div class="preview-card-content">
                                        <p>{{ t('website.welcome') }} {{ globalSettings.settings.siteName }}</p>
                                        <div class="preview-button-row">
                                            <button class="preview-btn primary">
                                                Primärbutton
                                            </button>
                                            <button class="preview-btn secondary">
                                                Sekundärbutton
                                            </button>
                                        </div>
                                        <div class="preview-form-row">
                                            <div class="preview-input">Eingabefeld</div>
                                            <div class="preview-select">Dropdown</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-3">
                    <v-spacer></v-spacer>
                    <v-btn color="primary" variant="flat" @click="previewDialogOpen = false">
                        Schließen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useToast } from 'vue-toastification'; // Import Toastification
import { applyThemeToDOM, clearThemeCache } from '@/utils/themeLoader';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth'; // Import auth store

// --- Interfaces (optional but recommended) ---
interface Settings {
    siteName: string;
    siteLogo: string;
    employeeNavigation: string;
    companyName: string;
    // Theme settings
    darkMode: boolean;
    primaryColor: string;
    secondaryColor: string;
    accentColor: string;
    backgroundColor: string;
    surfaceColor: string;
    tertiaryColor: string;
    infoColor: string;
    successColor: string;
    warningColor: string;
    errorColor: string;
    // Text on color settings
    onPrimaryColor: string;
    onSecondaryColor: string;
    onAccentColor: string;
    onBackgroundColor: string;
    onSurfaceColor: string;
    onSuccessColor: string;
    onInfoColor: string;
    onWarningColor: string;
    onErrorColor: string;
    // Other theme settings
    borderRadius: number;
    enableGradients: boolean;
    // System settings
    defaultLanguage: string;
    dateFormat: string;
    defaultStartPage: string;
    sessionTimeout: number;
    enableNotifications: boolean;
    enableUserTracking: boolean;
    // Branding & Export
    pdfHeaderLogo: string;
    pdfFooterText: string;
    exportDateFormat: string;
    defaultExportFormat: string;
    // Contact & Support
    supportEmail: string;
    supportPhone: string;
    helpPageContent: string;
    // Index signature to allow dynamic access
    [key: string]: any;
}

interface GlobalSettingsResponse {
    settings: Settings;
}

// --- Component State ---
const loadingSettings = ref(false);
const savingSettings = ref(false);
const formRef = ref<any>(null);
const isFormValid = ref(false);
const previewDialogOpen = ref(false);
const expandedPanels = ref(['basic']);

// Color picker states
const colorPickers = reactive({
    primary: false,
    accent: false,
    background: false,
    tertiary: false,
    info: false,
    success: false,
    warning: false,
    error: false,
    secondary: false,
    surface: false,
    onPrimary: false,
    onSecondary: false,
    onAccent: false,
    onBackground: false,
    onSurface: false,
});

// Default colors for reset
const defaultColors = {
    primary: 'var(--k-accent)', // Blue
    secondary: '#343541',
    accent: '#10B981', // Green
    background: '#111723',
    surface: '#111827',
    tertiary: '#444654',
    info: '#007bff',
    success: '#138D75',
    warning: '#FFC107',
    error: '#dc3545',
    // Text on color settings
    onPrimary: '#FFFFFF',
    onSecondary: '#FFFFFF',
    onAccent: '#121212',
    onBackground: '#FFFFFF',
    onSurface: '#FFFFFF',
    onSuccess: '#FFFFFF',
    onInfo: '#FFFFFF',
    onWarning: '#121212',
    onError: '#FFFFFF',
    // Other settings
    borderRadius: 8,
    darkMode: true,
    enableGradients: true,
};

// Default settings for basic information
const defaultSettings = {
    siteName: 'FireGuard Solutions',
    siteLogo: '/img/fireguard-logo-transparent.png',
    employeeNavigation: 'Mitarbeiter',
    companyName: 'FireGuard Solutions GmbH',
};

// Use reactive for the nested settings object
const globalSettings = reactive<GlobalSettingsResponse>({
    settings: {
        siteName: '',
        siteLogo: '',
        employeeNavigation: '',
        companyName: '',
        // Theme settings
        darkMode: true,
        primaryColor: defaultColors.primary,
        secondaryColor: defaultColors.secondary,
        accentColor: defaultColors.accent,
        backgroundColor: defaultColors.background,
        surfaceColor: defaultColors.surface,
        tertiaryColor: defaultColors.tertiary,
        infoColor: defaultColors.info,
        successColor: defaultColors.success,
        warningColor: defaultColors.warning,
        errorColor: defaultColors.error,
        
        // Text on color settings
        onPrimaryColor: defaultColors.onPrimary,
        onSecondaryColor: defaultColors.onSecondary,
        onAccentColor: defaultColors.onAccent,
        onBackgroundColor: defaultColors.onBackground,
        onSurfaceColor: defaultColors.onSurface,
        onSuccessColor: defaultColors.onSuccess,
        onInfoColor: defaultColors.onInfo,
        onWarningColor: defaultColors.onWarning,
        onErrorColor: defaultColors.onError,
        
        // Andere Theme-Einstellungen
        borderRadius: defaultColors.borderRadius,
        enableGradients: defaultColors.enableGradients,
        
        // Systemeinstellungen
        defaultLanguage: 'de',
        dateFormat: 'DD.MM.YYYY',
        defaultStartPage: '/desktop',
        sessionTimeout: 30,
        enableNotifications: true,
        enableUserTracking: true,
        
        // Branding & Export
        pdfHeaderLogo: '',
        pdfFooterText: '',
        exportDateFormat: 'DD.MM.YYYY',
        defaultExportFormat: 'PDF',
        
        // Kontakt & Support
        supportEmail: '',
        supportPhone: '',
        helpPageContent: '',
    },
});

// --- Stores & Composables ---
const authStore = useAuthStore();
const toast = useToast();
const { t } = useI18n();

// --- Options for selects ---
const languageOptions = [
    { code: 'de', name: t('settingsView.german') },
    { code: 'en', name: t('settingsView.english') },
    { code: 'fr', name: t('settingsView.french') },
    { code: 'es', name: t('settingsView.spanish') },
    { code: 'it', name: t('settingsView.italian') },
];

const dateFormatOptions = [
    'DD.MM.YYYY',
    'MM/DD/YYYY',
    'YYYY-MM-DD',
    'DD.MM.YYYY HH:mm',
    'MM/DD/YYYY hh:mm a',
    'YYYY-MM-DD HH:mm',
];

const startPageOptions = [
    { route: '/dashboard', name: t('settingsView.dashboard') },
    { route: '/profile', name: t('settingsView.profile') },
    { route: '/messages', name: t('settingsView.messages') },
    { route: '/calendar', name: t('settingsView.calendar') },
    { route: '/documents', name: t('settingsView.documents') },
];

// --- Validation Rules ---
const rules = {
    required: (value: string) => !!value || t('settingsView.fieldRequired'),
    url: (value: string) => {
        if (!value) return true; // Optional field
        // Simple URL pattern check (adjust if needed)
        const pattern = new RegExp(
            '^(https?:\\/\\/)?' + // protocol
                '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
                '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
                '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
                '(\\#[-a-z\\d_]*)?$',
            'i'
        ); // fragment locator
        return !value || pattern.test(value) || t('settingsView.invalidUrl');
    },
    email: (value: string) => {
        if (!value) return true; // Optional field
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(value) || t('settingsView.invalidEmail');
    },
    hexColor: (value: string) => {
        if (!value) return true; // Optional field
        const pattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        return pattern.test(value) || t('settingsView.invalidHexColor');
    },
};

// --- Data Fetching ---
const fetchSettings = async () => {
    loadingSettings.value = true;
    try {
        // Get authority branding from auth store
        const authorityBranding = authStore.user?.authority_branding || {};
        const themeSettings = authorityBranding.theme_settings || {};

        console.log('Loading settings from authority_branding:', authorityBranding);
        
        // Build settings from authority_branding + theme_settings
        const mergedSettings = {
            // Basiseinstellungen from authority_branding
            siteName: authorityBranding.app_title || defaultSettings.siteName,
            siteLogo: authorityBranding.logo_url || defaultSettings.siteLogo,
            primaryColor: authorityBranding.primary_color || defaultColors.primary,
            secondaryColor: authorityBranding.secondary_color || defaultColors.secondary,
            companyName: authorityBranding.display_name || defaultSettings.companyName,
            defaultBackground: authorityBranding.default_background || null,

            // Default values for theme
            darkMode: defaultColors.darkMode,
            accentColor: defaultColors.accent,
            backgroundColor: defaultColors.background,
            surfaceColor: defaultColors.surface,
            tertiaryColor: defaultColors.tertiary,
            infoColor: defaultColors.info,
            successColor: defaultColors.success,
            warningColor: defaultColors.warning,
            errorColor: defaultColors.error,
            onPrimaryColor: defaultColors.onPrimary,
            onSecondaryColor: defaultColors.onSecondary,
            onAccentColor: defaultColors.onAccent,
            onBackgroundColor: defaultColors.onBackground,
            onSurfaceColor: defaultColors.onSurface,
            onSuccessColor: defaultColors.onSuccess,
            onInfoColor: defaultColors.onInfo,
            onWarningColor: defaultColors.onWarning,
            onErrorColor: defaultColors.onError,
            borderRadius: defaultColors.borderRadius,
            enableGradients: defaultColors.enableGradients,

            // Default system settings
            employeeNavigation: 'Mitarbeiter',
            defaultLanguage: 'de',
            dateFormat: 'DD.MM.YYYY',
            defaultStartPage: '/desktop',
            sessionTimeout: 30,
            enableNotifications: true,
            enableUserTracking: true,
            pdfHeaderLogo: '',
            pdfFooterText: '',
            exportDateFormat: 'DD.MM.YYYY',
            defaultExportFormat: 'PDF',
            supportEmail: '',
            supportPhone: '',
            helpPageContent: '',

            // Merge in theme_settings from database
            ...themeSettings
        };
        
        // Das mergedSettings-Objekt enthält jetzt die Werte aus der Datenbank oder die Standardwerte
        Object.assign(globalSettings.settings, mergedSettings);
        
        // Theme sofort anwenden
        applyThemeToDOM({
            primaryColor: globalSettings.settings.primaryColor,
            secondaryColor: globalSettings.settings.secondaryColor,
            accentColor: globalSettings.settings.accentColor,
            backgroundColor: globalSettings.settings.backgroundColor,
            surfaceColor: globalSettings.settings.surfaceColor,
            tertiaryColor: globalSettings.settings.tertiaryColor,
            infoColor: globalSettings.settings.infoColor,
            successColor: globalSettings.settings.successColor,
            warningColor: globalSettings.settings.warningColor,
            errorColor: globalSettings.settings.errorColor,
            onPrimaryColor: globalSettings.settings.onPrimaryColor,
            onSecondaryColor: globalSettings.settings.onSecondaryColor,
            onAccentColor: globalSettings.settings.onAccentColor,
            onBackgroundColor: globalSettings.settings.onBackgroundColor,
            onSurfaceColor: globalSettings.settings.onSurfaceColor,
            onSuccessColor: globalSettings.settings.onSuccessColor,
            onInfoColor: globalSettings.settings.onInfoColor,
            onWarningColor: globalSettings.settings.onWarningColor,
            onErrorColor: globalSettings.settings.onErrorColor,
            borderRadius: globalSettings.settings.borderRadius,
            darkMode: globalSettings.settings.darkMode,
            enableGradients: globalSettings.settings.enableGradients
        });

        toast.success(t('settingsView.settingsLoaded'));

    } catch (error: any) {
        console.error('Error loading settings from authority_branding:', error);
        toast.error(t('settingsView.loadError'));
        // Wenn Fehler auftreten, verwenden wir die Standardwerte
        resetThemeDefaults();
    } finally {
        loadingSettings.value = false;
    }
};

// --- Computed properties ---
const previewStyles = computed(() => {
    const primaryColor = getValidColor(globalSettings.settings.primaryColor);
    const secondaryColor = getValidColor(globalSettings.settings.secondaryColor);
    const accentColor = getValidColor(globalSettings.settings.accentColor);
    const backgroundColor = getValidColor(globalSettings.settings.backgroundColor);
    const surfaceColor = getValidColor(globalSettings.settings.surfaceColor);
    const tertiaryColor = getValidColor(globalSettings.settings.tertiaryColor);
    const infoColor = getValidColor(globalSettings.settings.infoColor);
    const successColor = getValidColor(globalSettings.settings.successColor);
    const warningColor = getValidColor(globalSettings.settings.warningColor);
    const errorColor = getValidColor(globalSettings.settings.errorColor);
    
    const onPrimaryColor = getValidColor(globalSettings.settings.onPrimaryColor);
    const onSecondaryColor = getValidColor(globalSettings.settings.onSecondaryColor);
    const onAccentColor = getValidColor(globalSettings.settings.onAccentColor);
    const onBackgroundColor = getValidColor(globalSettings.settings.onBackgroundColor);
    const onSurfaceColor = getValidColor(globalSettings.settings.onSurfaceColor);
    const onSuccessColor = getValidColor(globalSettings.settings.onSuccessColor);
    const onInfoColor = getValidColor(globalSettings.settings.onInfoColor);
    const onWarningColor = getValidColor(globalSettings.settings.onWarningColor);
    const onErrorColor = getValidColor(globalSettings.settings.onErrorColor);
    
    const isDark = globalSettings.settings.darkMode;
    const textColor = isDark ? '#e2e8f0' : '#334155';

    return {
        '--primary-color': primaryColor,
        '--secondary-color': secondaryColor,
        '--accent-color': accentColor,
        '--background-color': backgroundColor,
        '--surface-color': surfaceColor,
        '--tertiary-color': tertiaryColor,
        '--info-color': infoColor,
        '--success-color': successColor,
        '--warning-color': warningColor,
        '--error-color': errorColor,
        
        '--on-primary-color': onPrimaryColor,
        '--on-secondary-color': onSecondaryColor,
        '--on-accent-color': onAccentColor,
        '--on-background-color': onBackgroundColor,
        '--on-surface-color': onSurfaceColor,
        '--on-success-color': onSuccessColor,
        '--on-info-color': onInfoColor,
        '--on-warning-color': onWarningColor,
        '--on-error-color': onErrorColor,
        
        '--text-color': textColor,
        '--border-radius': `${globalSettings.settings.borderRadius}px`,
        'background-color': backgroundColor,
        color: onBackgroundColor,
        ...(globalSettings.settings.enableGradients
            ? {
                  'background-image': `
        radial-gradient(circle at 15% 20%, ${adjustColorOpacity(primaryColor, 0.05)} 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, ${adjustColorOpacity(accentColor, 0.05)} 0%, transparent 30%)
      `,
              }
            : {}),
    };
});

// --- Utility Functions ---
const getValidColor = (color: string): string => {
    // Validate if it's a proper hex color, if not return a default
    return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color) ? color : defaultColors.primary;
};

const adjustColorOpacity = (color: string, opacity: number): string => {
    if (!color.startsWith('#')) return `rgba(0, 0, 0, ${opacity})`;

    const hex = color.slice(1);
    const r = parseInt(hex.length === 3 ? hex[0] + hex[0] : hex.substring(0, 2), 16);
    const g = parseInt(hex.length === 3 ? hex[1] + hex[1] : hex.substring(2, 4), 16);
    const b = parseInt(hex.length === 3 ? hex[2] + hex[2] : hex.substring(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

// --- Methods ---
const resetThemeDefaults = () => {
    globalSettings.settings.primaryColor = defaultColors.primary;
    globalSettings.settings.secondaryColor = defaultColors.secondary;
    globalSettings.settings.accentColor = defaultColors.accent;
    globalSettings.settings.backgroundColor = defaultColors.background;
    globalSettings.settings.surfaceColor = defaultColors.surface;
    globalSettings.settings.tertiaryColor = defaultColors.tertiary;
    globalSettings.settings.infoColor = defaultColors.info;
    globalSettings.settings.successColor = defaultColors.success;
    globalSettings.settings.warningColor = defaultColors.warning;
    globalSettings.settings.errorColor = defaultColors.error;
    
    globalSettings.settings.onPrimaryColor = defaultColors.onPrimary;
    globalSettings.settings.onSecondaryColor = defaultColors.onSecondary;
    globalSettings.settings.onAccentColor = defaultColors.onAccent;
    globalSettings.settings.onBackgroundColor = defaultColors.onBackground;
    globalSettings.settings.onSurfaceColor = defaultColors.onSurface;
    globalSettings.settings.onSuccessColor = defaultColors.onSuccess;
    globalSettings.settings.onInfoColor = defaultColors.onInfo;
    globalSettings.settings.onWarningColor = defaultColors.onWarning;
    globalSettings.settings.onErrorColor = defaultColors.onError;
    
    globalSettings.settings.borderRadius = defaultColors.borderRadius;
    globalSettings.settings.darkMode = defaultColors.darkMode;
    globalSettings.settings.enableGradients = defaultColors.enableGradients;

    toast.info(t('settingsView.themeReset'));
    // Close all color pickers
    colorPickers.primary = false;
    colorPickers.secondary = false;
    colorPickers.accent = false;
    colorPickers.background = false;
    colorPickers.surface = false;
    colorPickers.tertiary = false;
    colorPickers.info = false;
    colorPickers.success = false;
    colorPickers.warning = false;
    colorPickers.error = false;
    colorPickers.onPrimary = false;
    colorPickers.onSecondary = false;
    colorPickers.onAccent = false;
    colorPickers.onBackground = false;
    colorPickers.onSurface = false;
};

const onLogoError = (errorValue: string | undefined) => {
    // In v-img, the error event provides a string or undefined value
    console.warn(`Failed to load logo: ${errorValue || 'unknown source'}`);
    
    // Since we can't access the image element directly,
    // we can set a flag to hide or replace the broken image
    // or use another approach if needed
};

const resetSettings = async () => {
    if (
        confirm(
            'Möchten Sie alle Einstellungen auf die Standardwerte zurücksetzen? Diese Aktion kann nicht rückgängig gemacht werden.'
        )
    ) {
        await fetchSettings(); // Reload from server instead of resetting to empty values
        toast.info(t('settingsView.settingsReset'));
    }
};

const previewSettings = () => {
    previewDialogOpen.value = true;
};

const reloadSettings = async () => {
    // Implement the logic to reload settings from the server
    await fetchSettings();
    toast.info(t('settingsView.latestSettingsLoaded'));
};

const saveSettings = async () => {
    // Trigger validation manually if needed, or rely on button disabled state
    // const { valid } = await formRef.value?.validate();
    // if (!valid) return;
    if (!isFormValid.value) return;

    savingSettings.value = true;
    try {
        // Wir senden alle Einstellungen im globalSettings-Objekt zum Backend
        const response = await apiClientAuth.post('/admin/settings?action=updateSettings', globalSettings);
        
        if (response.data && response.data.success) {
            toast.success(t('settingsView.settingsSaved'));
            
            // Force-Refresh Flag setzen, damit der ThemeLoader die Einstellungen beim nächsten Laden neu holt
            localStorage.setItem('force_refresh_theme', 'true');
            
            // Einstellungen in Local Storage speichern, damit CSS-Variablen sofort aktualisiert werden
            localStorage.setItem('theme-settings', JSON.stringify({
                primaryColor: globalSettings.settings.primaryColor,
                secondaryColor: globalSettings.settings.secondaryColor,
                accentColor: globalSettings.settings.accentColor,
                backgroundColor: globalSettings.settings.backgroundColor,
                surfaceColor: globalSettings.settings.surfaceColor,
                tertiaryColor: globalSettings.settings.tertiaryColor,
                infoColor: globalSettings.settings.infoColor,
                successColor: globalSettings.settings.successColor,
                warningColor: globalSettings.settings.warningColor,
                errorColor: globalSettings.settings.errorColor,
                onPrimaryColor: globalSettings.settings.onPrimaryColor,
                onSecondaryColor: globalSettings.settings.onSecondaryColor,
                onAccentColor: globalSettings.settings.onAccentColor,
                onBackgroundColor: globalSettings.settings.onBackgroundColor,
                onSurfaceColor: globalSettings.settings.onSurfaceColor,
                onSuccessColor: globalSettings.settings.onSuccessColor,
                onInfoColor: globalSettings.settings.onInfoColor,
                onWarningColor: globalSettings.settings.onWarningColor,
                onErrorColor: globalSettings.settings.onErrorColor,
                borderRadius: globalSettings.settings.borderRadius,
                darkMode: globalSettings.settings.darkMode,
                enableGradients: globalSettings.settings.enableGradients,
                // Zeitstempel für Cache-Invalidierung hinzufügen
                lastUpdated: new Date().getTime()
            }));
            
            // Wenn wir globale CSS-Variablen ändern, aktualisieren wir die sofort - jetzt mit der importierten Funktion
            applyThemeToDOM({
                primaryColor: globalSettings.settings.primaryColor,
                secondaryColor: globalSettings.settings.secondaryColor,
                accentColor: globalSettings.settings.accentColor,
                backgroundColor: globalSettings.settings.backgroundColor,
                surfaceColor: globalSettings.settings.surfaceColor,
                tertiaryColor: globalSettings.settings.tertiaryColor,
                infoColor: globalSettings.settings.infoColor,
                successColor: globalSettings.settings.successColor,
                warningColor: globalSettings.settings.warningColor,
                errorColor: globalSettings.settings.errorColor,
                onPrimaryColor: globalSettings.settings.onPrimaryColor,
                onSecondaryColor: globalSettings.settings.onSecondaryColor,
                onAccentColor: globalSettings.settings.onAccentColor,
                onBackgroundColor: globalSettings.settings.onBackgroundColor,
                onSurfaceColor: globalSettings.settings.onSurfaceColor,
                onSuccessColor: globalSettings.settings.onSuccessColor,
                onInfoColor: globalSettings.settings.onInfoColor,
                onWarningColor: globalSettings.settings.onWarningColor,
                onErrorColor: globalSettings.settings.onErrorColor,
                borderRadius: globalSettings.settings.borderRadius,
                darkMode: globalSettings.settings.darkMode,
                enableGradients: globalSettings.settings.enableGradients
            });
        } else {
            toast.error(response.data?.error || t('settingsView.unknownSaveError'));
        }
    } catch (error: any) {
        console.error('Error saving settings:', error);
        toast.error(
            error.response?.data?.error || t('settingsView.saveError')
        );
    } finally {
        savingSettings.value = false;
    }
};

// Button-Handler zum Löschen des Theme-Caches und Neuladen der Seite
const clearCacheAndReload = () => {
    clearThemeCache();
    toast.info(t('settingsView.cacheCleared'));
    setTimeout(() => {
        window.location.reload();
    }, 1500);
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchSettings();

    // Initialize with expanded panels based on screen size
    const screenWidth = window.innerWidth;
    if (screenWidth >= 1024) {
        // On larger screens, expand more sections by default
        expandedPanels.value = ['basic', 'theme', 'system'];
    }
});
</script>
<style scoped>
/* Main card styling */
.settings-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}

.settings-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.settings-title {
    background: linear-gradient(90deg, rgba(30, 58, 138, 0.7), var(--k-accent));
    padding: 16px;
    font-weight: 600;
}

/* Panel styling */
.settings-panel {
    background: transparent !important;
    margin-bottom: 8px;
}

.settings-panel :deep(.v-expansion-panel-title) {
    padding: 12px 16px;
    background: rgba(30, 41, 59, 0.3) !important;
    border-radius: 8px;
    min-height: 48px;
    transition: background-color 0.2s ease;
}

.settings-panel :deep(.v-expansion-panel-title:hover) {
    background: rgba(30, 41, 59, 0.5) !important;
}

.settings-panel :deep(.v-expansion-panel-text__wrapper) {
    padding: 16px;
}

/* Color preview styling */
.color-preview {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid var(--k-line);
}

.color-preview-container {
    display: flex;
    align-items: center;
    gap: 4px;
}

.color-preview-base {
    width: 20px;
    height: 40px;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
    border: 1px solid var(--k-line);
}

.on-preview {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.primary-preview {
    box-shadow: 0 0 10px var(--k-accent-line);
}

.secondary-preview {
    box-shadow: 0 0 10px rgba(52, 53, 65, 0.3);
}

.accent-preview {
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}

.tertiary-preview {
    box-shadow: 0 0 10px rgba(68, 70, 84, 0.3);
}

.info-preview {
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

.success-preview {
    box-shadow: 0 0 10px rgba(19, 141, 117, 0.3);
}

.warning-preview {
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
}

.error-preview {
    box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
}

.surface-preview {
    box-shadow: 0 0 10px rgba(17, 24, 39, 0.3);
}

.bg-preview {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

/* Border radius preview */
.preview-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.radius-preview {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--v-theme-primary), var(--k-accent));
    border: 1px solid var(--k-line);
}

.radius-preview-btn {
    height: 40px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--v-theme-primary);
    color: var(--k-ink);
    font-weight: 500;
}

.radius-preview-input {
    height: 40px;
    width: 100px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid var(--k-line);
}

/* Design preview dialog */
.preview-dialog {
    background: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
}

.preview-title {
    background: linear-gradient(90deg, rgba(30, 58, 138, 0.8), var(--k-accent));
    padding: 16px;
}

.preview-content {
    padding: 24px;
    background-color: var(--k-canvas);
}

.preview-container {
    border-radius: 8px;
    overflow: hidden;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}

.preview-header {
    height: 64px;
    background-color: var(--surface-color);
    border-bottom: 1px solid var(--k-line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}

.preview-logo {
    display: flex;
    align-items: center;
}

.preview-site-name {
    font-weight: 600;
    font-size: 18px;
}

.preview-nav {
    display: flex;
    align-items: center;
    gap: 16px;
}

.preview-nav-item {
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
}

.preview-nav-item.active {
    background-color: var(--primary-color);
    color: var(--k-ink);
}

.preview-main {
    display: flex;
    flex-grow: 1;
    min-height: 300px;
}

.preview-sidebar {
    width: 200px;
    background-color: rgba(0, 0, 0, 0.2);
    padding: 16px 0;
}

.preview-sidebar-item {
    padding: 10px 16px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.preview-sidebar-item:hover {
    background-color: var(--k-row-hover);
}

.preview-sidebar-item.active {
    background-color: var(--primary-color);
    color: var(--k-ink);
}

.preview-content-area {
    flex-grow: 1;
    padding: 20px;
    overflow: auto;
}

.preview-card {
    background-color: var(--surface-color);
    border-radius: var(--border-radius);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.preview-card-title {
    padding: 16px;
    font-weight: 600;
    border-bottom: 1px solid var(--k-line);
    background: rgba(0, 0, 0, 0.1);
}

.preview-card-content {
    padding: 16px;
}

.preview-button-row {
    display: flex;
    gap: 8px;
    margin: 16px 0;
}

.preview-btn {
    padding: 8px 16px;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 500;
}

.preview-btn.primary {
    background-color: var(--primary-color);
    color: var(--k-ink);
}

.preview-btn.secondary {
    background-color: var(--accent-color);
    color: var(--k-ink);
}

.preview-form-row {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.preview-input,
.preview-select {
    height: 40px;
    padding: 0 12px;
    border-radius: var(--border-radius);
    background-color: var(--k-row-hover);
    border: 1px solid var(--k-line);
    display: flex;
    align-items: center;
    width: 140px;
}

/* Responsive adjustments */
@media (max-width: 959px) {
    .preview-container {
        height: 500px;
        overflow: auto;
    }

    .preview-main {
        flex-direction: column;
    }

    .preview-sidebar {
        width: 100%;
        display: flex;
        overflow-x: auto;
        padding: 8px;
    }

    .preview-sidebar-item {
        padding: 8px 12px;
        white-space: nowrap;
    }
}
</style>
