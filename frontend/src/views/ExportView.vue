<template>
    <div class="export-container pa-6">
        <v-container fluid>
            <!-- Header -->
            <div class="d-flex align-center mb-6 header-title">
                <v-icon icon="mdi-database-export" size="x-large" color="primary" class="mr-3 header-icon"></v-icon>
                <div class="flex-grow-1">
                    <h1 class="text-h4 font-weight-bold">{{ t('exportView.title') }}</h1>
                    <p class="text-medium-emphasis mt-1">{{ t('exportView.description') }}</p>
                </div>
                <!-- Presets -->
                <div class="d-flex ga-2">
                    <v-btn
                        v-if="savedPresets.length > 0"
                        variant="outlined"
                        size="small"
                        prepend-icon="mdi-folder-open"
                        @click="showLoadPresetDialog = true"
                    >
                        {{ t('exportView.loadPreset') }}
                    </v-btn>
                    <v-btn
                        v-if="selectedColumns.length > 0"
                        variant="outlined"
                        size="small"
                        prepend-icon="mdi-content-save"
                        @click="showSavePresetDialog = true"
                    >
                        {{ t('exportView.savePreset') }}
                    </v-btn>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loadingData" class="loading-state d-flex flex-column align-center justify-center pa-10">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4 text-medium-emphasis">{{ t('exportView.loading') }}</p>
            </div>

            <!-- Main Content -->
            <v-row v-else>
                <!-- Left Column: Available Columns -->
                <v-col cols="12" md="5">
                    <v-card class="config-card" variant="outlined">
                        <v-card-title class="text-subtitle-1">
                            <v-icon icon="mdi-view-column" size="small" class="mr-2"></v-icon>
                            {{ t('exportView.availableColumns') }}
                        </v-card-title>
                        <v-card-text>
                            <v-expansion-panels variant="accordion">
                                <!-- Employee Basic Fields -->
                                <v-expansion-panel>
                                    <v-expansion-panel-title class="text-body-2">
                                        <v-icon icon="mdi-account" size="small" class="mr-2"></v-icon>
                                        {{ t('exportView.basicFields') }}
                                        <v-btn
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            class="ml-2"
                                            @click.stop="addAllFromCategory('basic')"
                                        >
                                            {{ t('exportView.addAll') }}
                                        </v-btn>
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <v-chip
                                            v-for="col in availableBasicColumns"
                                            :key="col.id"
                                            @click="addColumn(col)"
                                            class="ma-1"
                                            size="small"
                                            variant="outlined"
                                            prepend-icon="mdi-plus"
                                        >
                                            {{ col.label }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>

                                <!-- Contact Fields -->
                                <v-expansion-panel>
                                    <v-expansion-panel-title class="text-body-2">
                                        <v-icon icon="mdi-phone" size="small" class="mr-2"></v-icon>
                                        {{ t('exportView.contactFields') }}
                                        <v-btn
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            class="ml-2"
                                            @click.stop="addAllFromCategory('contact')"
                                        >
                                            {{ t('exportView.addAll') }}
                                        </v-btn>
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <v-chip
                                            v-for="col in availableContactColumns"
                                            :key="col.id"
                                            @click="addColumn(col)"
                                            class="ma-1"
                                            size="small"
                                            variant="outlined"
                                            prepend-icon="mdi-plus"
                                        >
                                            {{ col.label }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>

                                <!-- Organization Fields -->
                                <v-expansion-panel>
                                    <v-expansion-panel-title class="text-body-2">
                                        <v-icon icon="mdi-office-building" size="small" class="mr-2"></v-icon>
                                        {{ t('exportView.organizationFields') }}
                                        <v-btn
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            class="ml-2"
                                            @click.stop="addAllFromCategory('organization')"
                                        >
                                            {{ t('exportView.addAll') }}
                                        </v-btn>
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <v-chip
                                            v-for="col in availableOrganizationColumns"
                                            :key="col.id"
                                            @click="addColumn(col)"
                                            class="ma-1"
                                            size="small"
                                            variant="outlined"
                                            prepend-icon="mdi-plus"
                                        >
                                            {{ col.label }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>

                                <!-- Licenses -->
                                <v-expansion-panel>
                                    <v-expansion-panel-title class="text-body-2">
                                        <v-icon icon="mdi-certificate" size="small" class="mr-2"></v-icon>
                                        {{ t('exportView.licenses') }}
                                        <v-btn
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            class="ml-2"
                                            @click.stop="addAllFromCategory('licenses')"
                                        >
                                            {{ t('exportView.addAll') }}
                                        </v-btn>
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <v-chip
                                            v-for="license in availableLicenses"
                                            :key="license.id"
                                            @click="addLicenseColumn(license)"
                                            class="ma-1"
                                            size="small"
                                            variant="outlined"
                                            prepend-icon="mdi-plus"
                                        >
                                            {{ license.license }}
                                        </v-chip>
                                        <v-chip
                                            @click="addColumn({ id: 'licenses_all', label: t('exportView.allLicenses'), type: 'multi', field: 'licenses' })"
                                            class="ma-1"
                                            size="small"
                                            variant="tonal"
                                            color="primary"
                                            prepend-icon="mdi-plus"
                                        >
                                            {{ t('exportView.allLicensesCombined') }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>

                                <!-- Trainings -->
                                <v-expansion-panel v-for="category in trainingCategories" :key="category.id">
                                    <v-expansion-panel-title class="text-body-2">
                                        <div
                                            class="category-color-dot mr-2"
                                            :style="{ backgroundColor: getCategoryColor(category.id) }"
                                        ></div>
                                        <v-icon icon="mdi-school" size="small" class="mr-2"></v-icon>
                                        {{ category.name }}
                                        <v-chip size="x-small" class="ml-1" v-if="category.short">{{ category.short }}</v-chip>
                                        <v-btn
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            class="ml-2"
                                            @click.stop="addAllTrainingsFromCategory(category.id)"
                                        >
                                            {{ t('exportView.addAll') }}
                                        </v-btn>
                                    </v-expansion-panel-title>
                                    <v-expansion-panel-text>
                                        <v-chip
                                            v-for="training in getTrainingsByCategory(category.id)"
                                            :key="training.id"
                                            @click="addTrainingColumn(training)"
                                            class="ma-1"
                                            size="small"
                                            variant="outlined"
                                            prepend-icon="mdi-plus"
                                            :style="{ borderColor: getCategoryColor(training.catId) }"
                                        >
                                            {{ training.name }}
                                        </v-chip>
                                    </v-expansion-panel-text>
                                </v-expansion-panel>
                            </v-expansion-panels>

                            <!-- Custom Column -->
                            <div class="mt-4">
                                <div class="text-subtitle-2 mb-2">{{ t('exportView.customColumn') }}</div>
                                <v-text-field
                                    v-model="newCustomColumnLabel"
                                    :label="t('exportView.columnName')"
                                    variant="outlined"
                                    density="compact"
                                    class="mb-2"
                                ></v-text-field>
                                <v-text-field
                                    v-model="newCustomColumnValue"
                                    :label="t('exportView.columnValue')"
                                    :hint="t('exportView.columnValueHint')"
                                    persistent-hint
                                    variant="outlined"
                                    density="compact"
                                    class="mb-2"
                                ></v-text-field>
                                <v-btn
                                    @click="addCustomColumn"
                                    :disabled="!newCustomColumnLabel.trim()"
                                    color="primary"
                                    variant="tonal"
                                    size="small"
                                    prepend-icon="mdi-plus"
                                    block
                                >
                                    {{ t('exportView.addCustom') }}
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Right Column: Selected Columns & Options -->
                <v-col cols="12" md="7">
                    <v-card class="config-card mb-4" variant="outlined">
                        <v-card-title class="text-subtitle-1 d-flex align-center">
                            <v-icon icon="mdi-format-list-checks" size="small" class="mr-2"></v-icon>
                            {{ t('exportView.selectedColumns') }}
                            <v-chip size="x-small" class="ml-2">{{ selectedColumns.length }}</v-chip>
                            <v-spacer></v-spacer>
                            <v-btn
                                v-if="selectedColumns.length > 0"
                                size="x-small"
                                variant="text"
                                color="error"
                                @click="clearAllColumns"
                            >
                                {{ t('exportView.clearAll') }}
                            </v-btn>
                        </v-card-title>
                        <v-card-text>
                            <div class="text-caption text-grey mb-2" v-if="selectedColumns.length > 0">
                                <v-icon icon="mdi-drag" size="x-small"></v-icon>
                                {{ t('exportView.dragToReorder') }}
                            </div>
                            <div
                                class="selected-columns-list"
                                @dragover.prevent
                                @drop="handleDrop"
                            >
                                <div
                                    v-for="(col, index) in selectedColumns"
                                    :key="col.id"
                                    class="draggable-column-item"
                                    :class="{ 'drag-over': dragOverIndex === index }"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, index)"
                                    @dragover.prevent="handleDragOver($event, index)"
                                    @dragend="handleDragEnd"
                                >
                                    <div class="d-flex align-center flex-grow-1">
                                        <v-icon icon="mdi-drag" size="small" class="drag-handle mr-2"></v-icon>
                                        <div
                                            v-if="col.type === 'training' && col.categoryId"
                                            class="category-color-dot mr-2"
                                            :style="{ backgroundColor: getCategoryColor(col.categoryId) }"
                                        ></div>
                                        <v-icon :icon="getColumnIcon(col.type)" size="small" class="mr-2"></v-icon>
                                        <span class="text-body-2">
                                            {{ col.label }}
                                            <span v-if="col.originalLabel" class="text-caption text-warning ml-1">
                                                ({{ col.originalLabel }})
                                            </span>
                                            <span v-else-if="col.type === 'custom' && col.customValue" class="text-caption text-grey ml-1">
                                                ({{ col.customValue }})
                                            </span>
                                            <span v-if="col.type === 'training' && col.categoryShort && !col.originalLabel" class="text-caption text-grey ml-1">
                                                [{{ col.categoryShort }}]
                                            </span>
                                        </span>
                                    </div>
                                    <div class="column-actions">
                                        <v-btn
                                            icon="mdi-pencil"
                                            size="x-small"
                                            variant="text"
                                            color="primary"
                                            @click="openRenameDialog(index)"
                                            :title="t('exportView.renameColumn')"
                                        ></v-btn>
                                        <v-btn
                                            v-if="col.originalLabel"
                                            icon="mdi-undo"
                                            size="x-small"
                                            variant="text"
                                            color="warning"
                                            @click="resetColumnName(index)"
                                            :title="t('exportView.resetColumnName')"
                                        ></v-btn>
                                        <v-btn
                                            icon="mdi-chevron-up"
                                            size="x-small"
                                            variant="text"
                                            :disabled="index === 0"
                                            @click="moveColumnUp(index)"
                                        ></v-btn>
                                        <v-btn
                                            icon="mdi-chevron-down"
                                            size="x-small"
                                            variant="text"
                                            :disabled="index === selectedColumns.length - 1"
                                            @click="moveColumnDown(index)"
                                        ></v-btn>
                                        <v-btn
                                            icon="mdi-close"
                                            size="x-small"
                                            variant="text"
                                            color="error"
                                            @click="removeColumn(index)"
                                        ></v-btn>
                                    </div>
                                </div>
                                <div v-if="selectedColumns.length === 0" class="text-body-2 text-grey text-center pa-4">
                                    {{ t('exportView.noColumnsSelected') }}
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>

                    <!-- Export Options -->
                    <v-card class="config-card mb-4" variant="outlined">
                        <v-card-title class="text-subtitle-1">
                            <v-icon icon="mdi-cog" size="small" class="mr-2"></v-icon>
                            {{ t('exportView.options') }}
                        </v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="multiValueFormat"
                                        :items="multiValueFormatOptions"
                                        :label="t('exportView.multiValueFormat')"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="selectedEmployeeFilter"
                                        :items="employeeFilterOptions"
                                        :label="t('exportView.employeeFilter')"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="employeeSortBy"
                                        :items="sortOptions"
                                        :label="t('exportView.sortBy')"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                    ></v-select>
                                </v-col>
                            </v-row>
                            <v-row class="mt-2">
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        v-model="showTrainingDate"
                                        :label="t('exportView.showTrainingDate')"
                                        density="compact"
                                        hide-details
                                    ></v-checkbox>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        v-model="showTrainingInstructor"
                                        :label="t('exportView.showTrainingInstructor')"
                                        density="compact"
                                        hide-details
                                    ></v-checkbox>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        v-model="useMergedHeaders"
                                        :label="t('exportView.useMergedHeaders')"
                                        density="compact"
                                        hide-details
                                    ></v-checkbox>
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-checkbox
                                        v-model="useCategoryColors"
                                        :label="t('exportView.useCategoryColors')"
                                        density="compact"
                                        hide-details
                                    ></v-checkbox>
                                </v-col>
                            </v-row>

                            <!-- Google Sheets Integration -->
                            <v-divider class="my-4"></v-divider>
                            <div class="d-flex align-center mb-2">
                                <v-icon icon="mdi-google-spreadsheet" color="green" size="small" class="mr-2"></v-icon>
                                <span class="text-subtitle-2">Google Sheets Integration</span>
                                <v-btn
                                    icon="mdi-help-circle"
                                    size="x-small"
                                    variant="text"
                                    color="info"
                                    class="ml-2"
                                    @click="showGoogleSheetsHelp = true"
                                ></v-btn>
                            </div>
                            <v-text-field
                                v-model="googleSheetsUrl"
                                :label="t('exportView.googleSheetsUrl')"
                                :placeholder="t('exportView.googleSheetsUrlPlaceholder')"
                                variant="outlined"
                                density="compact"
                                prepend-inner-icon="mdi-link"
                                hide-details
                                clearable
                            ></v-text-field>
                        </v-card-text>
                    </v-card>

                    <!-- Preview -->
                    <v-card v-if="selectedColumns.length > 0" class="config-card mb-4" variant="outlined">
                        <v-card-title class="text-subtitle-1">
                            <v-icon icon="mdi-eye" size="small" class="mr-2"></v-icon>
                            {{ t('exportView.preview') }}
                            <v-chip size="x-small" class="ml-2">{{ Math.min(5, filteredEmployees.length) }} / {{ filteredEmployees.length }}</v-chip>
                        </v-card-title>
                        <v-card-text>
                            <div class="preview-table-wrapper">
                                <table class="preview-table">
                                    <thead>
                                        <!-- First header row: Category headers or single column headers with rowspan -->
                                        <tr>
                                            <template v-for="(group, gIdx) in previewHeaderGroups" :key="'h1-' + gIdx">
                                                <!-- Category group with multiple trainings -->
                                                <th
                                                    v-if="group.type === 'category' && group.cols.length > 1"
                                                    :colspan="group.cols.length"
                                                    class="category-header"
                                                    :style="useCategoryColors ? { backgroundColor: getCategoryColor(group.categoryId, 0.5) } : {}"
                                                >
                                                    {{ group.categoryName }}
                                                </th>
                                                <!-- Single column or single training in category -->
                                                <th
                                                    v-else
                                                    :rowspan="previewNeedsTwoRows ? 2 : 1"
                                                    :style="group.cols[0].type === 'training' && useCategoryColors ? { backgroundColor: getCategoryColor(group.cols[0].categoryId, 0.3) } : {}"
                                                >
                                                    {{ group.cols[0].label }}
                                                </th>
                                            </template>
                                        </tr>
                                        <!-- Second header row: Training names under category headers -->
                                        <tr v-if="previewNeedsTwoRows">
                                            <template v-for="(group, gIdx) in previewHeaderGroups" :key="'h2-' + gIdx">
                                                <template v-if="group.type === 'category' && group.cols.length > 1">
                                                    <th
                                                        v-for="col in group.cols"
                                                        :key="col.id"
                                                        :style="useCategoryColors ? { backgroundColor: getCategoryColor(col.categoryId, 0.25) } : {}"
                                                    >
                                                        {{ col.label }}
                                                    </th>
                                                </template>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="employee in filteredEmployees.slice(0, 5)" :key="employee.id">
                                            <td
                                                v-for="col in selectedColumns"
                                                :key="col.id"
                                                :style="col.type === 'training' && useCategoryColors ? { backgroundColor: getCategoryColor(col.categoryId, 0.1) } : {}"
                                            >
                                                {{ getCellValue(employee, col) || '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-if="filteredEmployees.length > 5" class="text-caption text-grey mt-2">
                                ... {{ filteredEmployees.length - 5 }} {{ t('exportView.moreEmployees') }}
                            </p>
                        </v-card-text>
                    </v-card>

                    <!-- Export -->
                    <v-card class="config-card" variant="outlined">
                        <v-card-title class="text-subtitle-1">
                            <v-icon icon="mdi-export" size="small" class="mr-2"></v-icon>
                            {{ t('exportView.export') }}
                        </v-card-title>
                        <v-card-text>
                            <v-alert type="info" variant="tonal" density="compact" class="mb-4">
                                <strong>{{ filteredEmployees.length }}</strong> {{ t('exportView.employeesWillBeExported') }}
                            </v-alert>
                            <div class="d-flex ga-3">
                                <v-btn
                                    @click="generateExport"
                                    :disabled="selectedColumns.length === 0"
                                    :loading="exporting"
                                    color="orange"
                                    variant="elevated"
                                    prepend-icon="mdi-content-copy"
                                    class="flex-grow-1"
                                    size="large"
                                >
                                    {{ t('exportView.copyToClipboard') }}
                                </v-btn>
                                <v-btn
                                    @click="exportToGoogleSheets"
                                    :disabled="selectedColumns.length === 0 || !googleSheetsUrl"
                                    :loading="exportingToSheets"
                                    color="green"
                                    variant="elevated"
                                    prepend-icon="mdi-google-spreadsheet"
                                    class="flex-grow-1"
                                    size="large"
                                >
                                    {{ t('exportView.exportToSheets') }}
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>

        <!-- Save Preset Dialog -->
        <v-dialog v-model="showSavePresetDialog" max-width="450">
            <v-card>
                <v-card-title>{{ t('exportView.savePresetTitle') }}</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="newPresetName"
                        :label="t('exportView.presetName')"
                        variant="outlined"
                        density="compact"
                        autofocus
                        class="mb-2"
                    ></v-text-field>
                    <v-checkbox
                        v-if="canCreateGlobalPresets"
                        v-model="newPresetIsGlobal"
                        :label="t('exportView.makeGlobal')"
                        :hint="t('exportView.makeGlobalHint')"
                        persistent-hint
                        density="compact"
                        color="primary"
                    ></v-checkbox>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="showSavePresetDialog = false">{{ t('common.cancel') }}</v-btn>
                    <v-btn color="primary" :disabled="!newPresetName.trim()" @click="savePreset">{{ t('common.save') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Load Preset Dialog -->
        <v-dialog v-model="showLoadPresetDialog" max-width="550">
            <v-card>
                <v-card-title>{{ t('exportView.loadPresetTitle') }}</v-card-title>
                <v-card-text>
                    <div v-if="loadingPresets" class="d-flex justify-center pa-4">
                        <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
                    </div>
                    <v-list v-else density="compact">
                        <v-list-item
                            v-for="preset in savedPresets"
                            :key="preset.id || preset.name"
                            @click="loadPreset(preset)"
                            class="preset-item"
                        >
                            <template v-slot:prepend>
                                <v-icon :icon="preset.is_global ? 'mdi-earth' : 'mdi-file-document-outline'"></v-icon>
                            </template>
                            <v-list-item-title class="d-flex align-center">
                                {{ preset.name }}
                                <v-chip v-if="preset.is_global" size="x-small" color="primary" class="ml-2">
                                    {{ t('exportView.globalPreset') }}
                                </v-chip>
                            </v-list-item-title>
                            <v-list-item-subtitle>{{ preset.columns?.length || 0 }} {{ t('exportView.columns') }}</v-list-item-subtitle>
                            <template v-slot:append>
                                <v-btn
                                    v-if="preset.is_own || canCreateGlobalPresets"
                                    icon="mdi-delete"
                                    size="x-small"
                                    variant="text"
                                    color="error"
                                    @click.stop="deletePreset(preset.id!)"
                                ></v-btn>
                            </template>
                        </v-list-item>
                        <v-list-item v-if="savedPresets.length === 0">
                            <v-list-item-title class="text-center text-grey">
                                {{ t('exportView.noPresets') }}
                            </v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="showLoadPresetDialog = false">{{ t('common.close') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Rename Column Dialog -->
        <v-dialog v-model="showRenameDialog" max-width="400">
            <v-card>
                <v-card-title>{{ t('exportView.renameColumnTitle') }}</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="renameColumnLabel"
                        :label="t('exportView.columnLabel')"
                        variant="outlined"
                        density="compact"
                        autofocus
                        @keyup.enter="saveColumnRename"
                    ></v-text-field>
                    <p v-if="renameColumnIndex !== null && selectedColumns[renameColumnIndex]?.originalLabel" class="text-caption text-grey mt-2">
                        {{ t('exportView.originalName') }}: {{ selectedColumns[renameColumnIndex]?.originalLabel }}
                    </p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="showRenameDialog = false">{{ t('common.cancel') }}</v-btn>
                    <v-btn color="primary" :disabled="!renameColumnLabel.trim()" @click="saveColumnRename">{{ t('common.save') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Google Sheets Help Dialog -->
        <v-dialog v-model="showGoogleSheetsHelp" max-width="700">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon icon="mdi-google-spreadsheet" color="green" class="mr-2"></v-icon>
                    {{ t('exportView.googleSheetsHelpTitle') }}
                </v-card-title>
                <v-card-text>
                    <v-alert type="info" variant="tonal" density="compact" class="mb-4">
                        {{ t('exportView.googleSheetsHelpIntro') }}
                    </v-alert>

                    <div class="text-subtitle-2 mb-2">{{ t('exportView.googleSheetsStep1') }}</div>
                    <p class="text-body-2 text-grey mb-3">{{ t('exportView.googleSheetsStep1Desc') }}</p>

                    <div class="text-subtitle-2 mb-2">{{ t('exportView.googleSheetsStep2') }}</div>
                    <p class="text-body-2 text-grey mb-2">{{ t('exportView.googleSheetsStep2Desc') }}</p>
                    <div class="code-block mb-3">
                        <pre class="text-caption">{{ googleAppsScriptCode }}</pre>
                        <v-btn
                            size="x-small"
                            variant="tonal"
                            color="primary"
                            class="copy-code-btn"
                            @click="copyScriptCode"
                        >
                            <v-icon icon="mdi-content-copy" size="small"></v-icon>
                        </v-btn>
                    </div>

                    <div class="text-subtitle-2 mb-2">{{ t('exportView.googleSheetsStep3') }}</div>
                    <p class="text-body-2 text-grey mb-3">{{ t('exportView.googleSheetsStep3Desc') }}</p>

                    <div class="text-subtitle-2 mb-2">{{ t('exportView.googleSheetsStep4') }}</div>
                    <p class="text-body-2 text-grey">{{ t('exportView.googleSheetsStep4Desc') }}</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="showGoogleSheetsHelp = false">{{ t('common.close') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import { apiClientAuth } from '@/api';
import { usePermissionCheck } from '@/composables/usePermissionCheck';
import type { Employee, Rank, License, Company, Department } from '@/types/Members';

const { hasPermission } = usePermissionCheck();
const canCreateGlobalPresets = computed(() => hasPermission('employee.write') || hasPermission('employee.delete') || hasPermission('ALL'));

const { t } = useI18n();
const toast = useToast();

// --- Category Colors ---
const categoryColors = [
    'var(--k-accent)', // blue
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#ec4899', // pink
    '#06b6d4', // cyan
    '#84cc16', // lime
    '#f97316', // orange
    '#6366f1', // indigo
];

const getCategoryColor = (categoryId?: number, alpha: number = 1): string => {
    if (!categoryId) return 'transparent';
    const index = categoryId % categoryColors.length;
    const color = categoryColors[index];
    if (alpha < 1) {
        // Convert hex to rgba
        const r = parseInt(color.slice(1, 3), 16);
        const g = parseInt(color.slice(3, 5), 16);
        const b = parseInt(color.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    return color;
};

// --- State ---
const loadingData = ref(true);
const exporting = ref(false);

// Data
const employeeData = ref<Rank[]>([]);
const licenses = ref<License[]>([]);
const companies = ref<Company[]>([]);
const departments = ref<Department[]>([]);
const trainings = ref<any[]>([]);
const trainingCategories = ref<any[]>([]);
const trainingAssigns = ref<any[]>([]);

// Export configuration
interface ExportColumn {
    id: string;
    label: string;
    originalLabel?: string; // Original label before renaming
    type: 'basic' | 'contact' | 'organization' | 'license' | 'training' | 'multi' | 'custom';
    field?: string;
    licenseId?: string;
    trainingId?: number;
    customValue?: string;
    categoryId?: number;
    categoryName?: string;
    categoryShort?: string;
}

interface ExportPreset {
    id?: number;
    name: string;
    columns: ExportColumn[];
    options: {
        multiValueFormat: string;
        employeeFilter: string;
        sortBy: string;
        showTrainingDate: boolean;
        showTrainingInstructor: boolean;
        useMergedHeaders: boolean;
        useCategoryColors: boolean;
        googleSheetsUrl?: string;
    };
    is_global?: boolean;
    is_own?: boolean;
}

const selectedColumns = ref<ExportColumn[]>([]);
const newCustomColumnLabel = ref('');
const newCustomColumnValue = ref('');

// Options
const multiValueFormat = ref('comma');
const selectedEmployeeFilter = ref('active');
const employeeSortBy = ref('servicenumber');
const showTrainingDate = ref(true);
const showTrainingInstructor = ref(false);
const useMergedHeaders = ref(true);
const useCategoryColors = ref(true);

// Drag & Drop
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

// Presets
const showSavePresetDialog = ref(false);
const showLoadPresetDialog = ref(false);
const newPresetName = ref('');
const newPresetIsGlobal = ref(false);
const savedPresets = ref<ExportPreset[]>([]);
const loadingPresets = ref(false);

// Column rename
const showRenameDialog = ref(false);
const renameColumnIndex = ref<number | null>(null);
const renameColumnLabel = ref('');

// Google Sheets Integration
const googleSheetsUrl = ref('');
const exportingToSheets = ref(false);
const showGoogleSheetsHelp = ref(false);

const googleAppsScriptCode = `function doPost(e) {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    var data = JSON.parse(e.postData.contents);

    // Clear sheet before import
    if (data.clearFirst) {
      sheet.clear();
    }

    var startRow = 1;
    var numHeaderRows = 1;

    // Check if we have merged headers (two header rows)
    if (data.mergedHeaders && data.mergedHeaders.length > 0) {
      numHeaderRows = 2;

      // Write first header row (category names)
      var row1 = data.mergedHeaders.map(function(h) { return h.label; });
      sheet.getRange(1, 1, 1, row1.length).setValues([row1]);

      // Write second header row (column names)
      if (data.headers && data.headers.length > 0) {
        sheet.getRange(2, 1, 1, data.headers.length).setValues([data.headers]);
      }

      // Apply merges for category headers
      var col = 1;
      data.mergedHeaders.forEach(function(h) {
        if (h.colspan > 1) {
          sheet.getRange(1, col, 1, h.colspan).merge();
        }
        // Apply color to merged header
        if (h.color) {
          sheet.getRange(1, col, 1, h.colspan || 1).setBackground(h.color);
        }
        col += (h.colspan || 1);
      });

      // Apply colors to second header row
      if (data.headerColors && data.headerColors.length > 0) {
        for (var i = 0; i < data.headerColors.length; i++) {
          if (data.headerColors[i]) {
            sheet.getRange(2, i + 1).setBackground(data.headerColors[i]);
          }
        }
      }

      // Style both header rows
      sheet.getRange(1, 1, 2, data.headers.length)
        .setFontWeight('bold')
        .setHorizontalAlignment('center');

      startRow = 3;
    } else {
      // Single header row
      if (data.headers && data.headers.length > 0) {
        sheet.getRange(1, 1, 1, data.headers.length).setValues([data.headers]);
        sheet.getRange(1, 1, 1, data.headers.length)
          .setFontWeight('bold')
          .setBackground('#c9c9c9');

        // Apply individual header colors
        if (data.headerColors && data.headerColors.length > 0) {
          for (var i = 0; i < data.headerColors.length; i++) {
            if (data.headerColors[i]) {
              sheet.getRange(1, i + 1).setBackground(data.headerColors[i]);
            }
          }
        }
      }
      startRow = 2;
    }

    // Write data rows
    if (data.rows && data.rows.length > 0) {
      sheet.getRange(startRow, 1, data.rows.length, data.rows[0].length).setValues(data.rows);

      // Apply column colors to data cells
      if (data.columnColors && data.columnColors.length > 0) {
        for (var i = 0; i < data.columnColors.length; i++) {
          if (data.columnColors[i]) {
            sheet.getRange(startRow, i + 1, data.rows.length, 1)
              .setBackground(data.columnColors[i]);
          }
        }
      }
    }

    // Auto-resize columns
    sheet.autoResizeColumns(1, data.headers.length);

    return ContentService.createTextOutput(JSON.stringify({success: true, rows: data.rows.length}))
      .setMimeType(ContentService.MimeType.JSON);
  } catch(err) {
    return ContentService.createTextOutput(JSON.stringify({error: err.toString()}))
      .setMimeType(ContentService.MimeType.JSON);
  }
}`;

const multiValueFormatOptions = computed(() => [
    { title: t('exportView.formatComma'), value: 'comma' },
    { title: t('exportView.formatSeparateColumns'), value: 'columns' },
    { title: t('exportView.formatSeparateRows'), value: 'rows' },
]);

const employeeFilterOptions = computed(() => [
    { title: t('exportView.filterAll'), value: 'all' },
    { title: t('exportView.filterActive'), value: 'active' },
    { title: t('exportView.filterTerminated'), value: 'terminated' },
]);

const sortOptions = computed(() => [
    { title: t('exportView.sortServicenumber'), value: 'servicenumber' },
    { title: t('exportView.sortName'), value: 'name' },
    { title: t('exportView.sortRank'), value: 'rank' },
    { title: t('exportView.sortEntrydate'), value: 'entrydate' },
]);

// --- Available Columns ---
const availableBasicColumns = computed(() => [
    { id: 'servicenumber', label: t('exportView.fields.servicenumber'), type: 'basic', field: 'servicenumber' },
    { id: 'name', label: t('exportView.fields.name'), type: 'basic', field: 'name' },
    { id: 'personalid', label: t('exportView.fields.personalid'), type: 'basic', field: 'personalid' },
    { id: 'rank', label: t('exportView.fields.rank'), type: 'basic', field: 'rank' },
    { id: 'jobrole', label: t('exportView.fields.jobrole'), type: 'basic', field: 'jobrole_name' },
    { id: 'entrydate', label: t('exportView.fields.entrydate'), type: 'basic', field: 'entrydate' },
    { id: 'birthdate', label: t('exportView.fields.birthdate'), type: 'basic', field: 'birthdate' },
    { id: 'last_promotion', label: t('exportView.fields.lastPromotion'), type: 'basic', field: 'last_promotion' },
]);

const availableContactColumns = computed(() => [
    { id: 'phonenumber', label: t('exportView.fields.phone'), type: 'contact', field: 'phonenumber' },
    { id: 'mail', label: t('exportView.fields.email'), type: 'contact', field: 'mail' },
    { id: 'bankaccount', label: t('exportView.fields.bankaccount'), type: 'contact', field: 'bankaccount' },
    { id: 'sidejob', label: t('exportView.fields.sidejob'), type: 'contact', field: 'sidejob' },
    { id: 'notes', label: t('exportView.fields.notes'), type: 'contact', field: 'notes' },
]);

const availableOrganizationColumns = computed(() => [
    { id: 'companies', label: t('exportView.fields.companies'), type: 'multi', field: 'companies' },
    { id: 'departments', label: t('exportView.fields.departments'), type: 'multi', field: 'departments' },
]);

const availableLicenses = computed(() => licenses.value);

// --- Computed ---
const allEmployees = computed((): Employee[] => {
    const employees: Employee[] = [];
    employeeData.value.forEach(rank => {
        if (rank.employees) {
            rank.employees.forEach(emp => {
                employees.push({ ...emp, rank: rank.name });
            });
        }
    });
    return employees;
});

const filteredEmployees = computed(() => {
    let employees = allEmployees.value.filter(emp => {
        if (selectedEmployeeFilter.value === 'active') return !emp.is_terminated;
        if (selectedEmployeeFilter.value === 'terminated') return emp.is_terminated;
        return true;
    });

    // Sort employees
    employees.sort((a, b) => {
        switch (employeeSortBy.value) {
            case 'servicenumber':
                return Number(a.servicenumber) - Number(b.servicenumber);
            case 'name':
                return (a.name || '').localeCompare(b.name || '');
            case 'rank':
                return (a.rank || '').localeCompare(b.rank || '');
            case 'entrydate':
                return new Date(a.entrydate || 0).getTime() - new Date(b.entrydate || 0).getTime();
            default:
                return 0;
        }
    });

    return employees;
});

// Computed property for preview merged headers
interface PreviewHeaderGroup {
    type: 'single' | 'category';
    cols: ExportColumn[];
    categoryName?: string;
    categoryId?: number;
}

const previewHeaderGroups = computed((): PreviewHeaderGroup[] => {
    const columns = selectedColumns.value;
    const groups: PreviewHeaderGroup[] = [];

    if (!useMergedHeaders.value) {
        // No merging - each column is its own group
        columns.forEach(col => {
            groups.push({ type: 'single', cols: [col] });
        });
        return groups;
    }

    // Group consecutive training columns by category
    let currentCategory: number | null = null;
    let currentGroup: ExportColumn[] = [];

    columns.forEach((col) => {
        if (col.type === 'training' && col.categoryId) {
            if (currentCategory === col.categoryId) {
                currentGroup.push(col);
            } else {
                if (currentGroup.length > 0) {
                    const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                    groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
                }
                currentCategory = col.categoryId;
                currentGroup = [col];
            }
        } else {
            if (currentGroup.length > 0) {
                const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
                currentGroup = [];
                currentCategory = null;
            }
            groups.push({ type: 'single', cols: [col] });
        }
    });

    if (currentGroup.length > 0) {
        const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
        groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
    }

    return groups;
});

const previewNeedsTwoRows = computed(() => {
    return previewHeaderGroups.value.some(g => g.type === 'category' && g.cols.length > 1);
});

// --- Functions ---
const fetchData = async () => {
    loadingData.value = true;
    try {
        const [empRes, licRes, compRes, deptRes, trainRes, trainCatRes, trainAssignRes] = await Promise.all([
            apiClientAuth.get('/employee/?action=getEmployee'),
            apiClientAuth.get('/employee/?action=getLicenses'),
            apiClientAuth.get('/employee/?action=getCompanies'),
            apiClientAuth.get('/employee/?action=getDepartments'),
            apiClientAuth.get('/training/?action=getTrainings'),
            apiClientAuth.get('/admin/training/?action=getCategories'),
            apiClientAuth.get('/training/?action=getTrainingAssigns'),
        ]);
        employeeData.value = empRes.data.ranks || empRes.data || [];
        licenses.value = licRes.data || [];
        companies.value = compRes.data || [];
        departments.value = deptRes.data || [];
        trainings.value = trainRes.data || [];
        trainingCategories.value = trainCatRes.data || [];
        trainingAssigns.value = trainAssignRes.data || [];
    } catch (error) {
        console.error('Error loading data:', error);
        toast.error(t('exportView.loadError'));
    } finally {
        loadingData.value = false;
    }
};

const getTrainingsByCategory = (catId: number) => {
    return trainings.value.filter(t => t.catId === catId);
};

const getColumnIcon = (type: string): string => {
    switch (type) {
        case 'basic': return 'mdi-account';
        case 'contact': return 'mdi-phone';
        case 'organization': return 'mdi-office-building';
        case 'multi': return 'mdi-format-list-bulleted';
        case 'license': return 'mdi-certificate';
        case 'training': return 'mdi-school';
        case 'custom': return 'mdi-pencil';
        default: return 'mdi-help';
    }
};

const addColumn = (col: any) => {
    if (!selectedColumns.value.find(c => c.id === col.id)) {
        selectedColumns.value.push({ ...col });
    }
};

const addLicenseColumn = (license: License) => {
    const colId = `license_${license.id}`;
    if (!selectedColumns.value.find(c => c.id === colId)) {
        selectedColumns.value.push({
            id: colId,
            label: license.license,
            type: 'license',
            licenseId: license.id,
        });
    }
};

const addTrainingColumn = (training: any) => {
    const colId = `training_${training.id}`;
    if (!selectedColumns.value.find(c => c.id === colId)) {
        selectedColumns.value.push({
            id: colId,
            label: training.name,
            type: 'training',
            trainingId: training.id,
            categoryId: training.catId,
            categoryName: training.cat_name,
            categoryShort: training.cat_short,
        });
    }
};

const addAllFromCategory = (category: string) => {
    if (category === 'basic') {
        availableBasicColumns.value.forEach(col => addColumn(col));
    } else if (category === 'contact') {
        availableContactColumns.value.forEach(col => addColumn(col));
    } else if (category === 'organization') {
        availableOrganizationColumns.value.forEach(col => addColumn(col));
    } else if (category === 'licenses') {
        licenses.value.forEach(lic => addLicenseColumn(lic));
    }
};

const clearAllColumns = () => {
    selectedColumns.value = [];
};

const addAllTrainingsFromCategory = (catId: number) => {
    getTrainingsByCategory(catId).forEach(training => addTrainingColumn(training));
};

const addCustomColumn = () => {
    if (newCustomColumnLabel.value.trim()) {
        const customId = `custom_${Date.now()}`;
        selectedColumns.value.push({
            id: customId,
            label: newCustomColumnLabel.value.trim(),
            type: 'custom',
            customValue: newCustomColumnValue.value.trim(),
        });
        newCustomColumnLabel.value = '';
        newCustomColumnValue.value = '';
    }
};

const removeColumn = (index: number) => {
    selectedColumns.value.splice(index, 1);
};

const moveColumnUp = (index: number) => {
    if (index > 0) {
        const temp = selectedColumns.value[index];
        selectedColumns.value[index] = selectedColumns.value[index - 1];
        selectedColumns.value[index - 1] = temp;
    }
};

const moveColumnDown = (index: number) => {
    if (index < selectedColumns.value.length - 1) {
        const temp = selectedColumns.value[index];
        selectedColumns.value[index] = selectedColumns.value[index + 1];
        selectedColumns.value[index + 1] = temp;
    }
};

// Column rename functions
const openRenameDialog = (index: number) => {
    const col = selectedColumns.value[index];
    renameColumnIndex.value = index;
    renameColumnLabel.value = col.label;
    showRenameDialog.value = true;
};

const saveColumnRename = () => {
    if (renameColumnIndex.value === null || !renameColumnLabel.value.trim()) return;

    const col = selectedColumns.value[renameColumnIndex.value];
    // Store original label if not already stored
    if (!col.originalLabel) {
        col.originalLabel = col.label;
    }
    col.label = renameColumnLabel.value.trim();

    showRenameDialog.value = false;
    renameColumnIndex.value = null;
    renameColumnLabel.value = '';
};

const resetColumnName = (index: number) => {
    const col = selectedColumns.value[index];
    if (col.originalLabel) {
        col.label = col.originalLabel;
        delete col.originalLabel;
    }
};

// Drag & Drop handlers
const handleDragStart = (event: DragEvent, index: number) => {
    draggedIndex.value = index;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
};

const handleDragOver = (event: DragEvent, index: number) => {
    event.preventDefault();
    dragOverIndex.value = index;
};

const handleDrop = () => {
    if (draggedIndex.value !== null && dragOverIndex.value !== null && draggedIndex.value !== dragOverIndex.value) {
        const item = selectedColumns.value.splice(draggedIndex.value, 1)[0];
        selectedColumns.value.splice(dragOverIndex.value, 0, item);
    }
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

const handleDragEnd = () => {
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

// Preset functions - Using API instead of localStorage
const loadPresetsFromAPI = async () => {
    loadingPresets.value = true;
    try {
        const response = await apiClientAuth.get('/export/?action=getPresets');
        const data = response.data || [];

        // Filter out invalid presets (must have name and valid columns array)
        savedPresets.value = Array.isArray(data)
            ? data.filter((p: any) => p && p.name && Array.isArray(p.columns) && p.columns.length > 0)
            : [];

        // Clear old localStorage data since we now use database
        localStorage.removeItem('export_presets');
    } catch (e) {
        console.error('Error loading presets:', e);
        savedPresets.value = [];
    } finally {
        loadingPresets.value = false;
    }
};

const savePreset = async () => {
    if (!newPresetName.value.trim()) return;

    try {
        const presetData = {
            name: newPresetName.value.trim(),
            columns: [...selectedColumns.value],
            options: {
                multiValueFormat: multiValueFormat.value,
                employeeFilter: selectedEmployeeFilter.value,
                sortBy: employeeSortBy.value,
                showTrainingDate: showTrainingDate.value,
                showTrainingInstructor: showTrainingInstructor.value,
                useMergedHeaders: useMergedHeaders.value,
                useCategoryColors: useCategoryColors.value,
                googleSheetsUrl: googleSheetsUrl.value || '',
            },
            is_global: newPresetIsGlobal.value,
        };

        await apiClientAuth.post('/export/?action=savePreset', presetData);

        // Reload presets from API
        await loadPresetsFromAPI();

        showSavePresetDialog.value = false;
        newPresetName.value = '';
        newPresetIsGlobal.value = false;
        toast.success(t('exportView.presetSaved'));
    } catch (e: any) {
        console.error('Error saving preset:', e);
        toast.error(e?.response?.data?.error || t('exportView.presetSaveError'));
    }
};

const loadPreset = (preset: ExportPreset) => {
    selectedColumns.value = [...preset.columns];
    if (preset.options) {
        multiValueFormat.value = preset.options.multiValueFormat || 'comma';
        selectedEmployeeFilter.value = preset.options.employeeFilter || 'active';
        employeeSortBy.value = preset.options.sortBy || 'servicenumber';
        showTrainingDate.value = preset.options.showTrainingDate ?? true;
        showTrainingInstructor.value = preset.options.showTrainingInstructor ?? false;
        useMergedHeaders.value = preset.options.useMergedHeaders ?? true;
        useCategoryColors.value = preset.options.useCategoryColors ?? true;
        googleSheetsUrl.value = preset.options.googleSheetsUrl || '';
    }

    showLoadPresetDialog.value = false;
    toast.success(t('exportView.presetLoaded'));
};

const deletePreset = async (presetId: number) => {
    try {
        await apiClientAuth.post('/export/?action=deletePreset', { id: presetId });

        // Reload presets from API
        await loadPresetsFromAPI();

        toast.success(t('exportView.presetDeleted'));
    } catch (e: any) {
        console.error('Error deleting preset:', e);
        toast.error(e?.response?.data?.error || t('exportView.presetDeleteError'));
    }
};

const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString.split(' ')[0]);
        if (isNaN(date.getTime())) return '';
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    } catch {
        return '';
    }
};

const getEmployeeTrainingStatus = (employeeId: number, trainingId: number) => {
    const assign = trainingAssigns.value.find(
        a => a.employeeid === employeeId && a.training_id === trainingId
    );
    return assign || null;
};

const getCellValue = (employee: Employee, col: ExportColumn): string => {
    switch (col.type) {
        case 'basic':
        case 'contact':
            const value = (employee as any)[col.field || ''];
            if (col.field === 'entrydate' || col.field === 'birthdate' || col.field === 'last_promotion') {
                return formatDate(value);
            }
            return value || '';

        case 'multi':
            if (col.field === 'companies') {
                return employee.companies?.map(c => c.name).join(', ') || '';
            } else if (col.field === 'departments') {
                return employee.departments?.map(d => d.name).join(', ') || '';
            } else if (col.field === 'licenses') {
                return employee.licenses?.map(l => l.license).join(', ') || '';
            }
            return '';

        case 'license':
            const hasLicense = employee.licenses?.some(l => l.id === col.licenseId);
            return hasLicense ? '✓' : '';

        case 'training':
            const trainingStatus = getEmployeeTrainingStatus(employee.id, col.trainingId!);
            if (trainingStatus) {
                let content = '✓';
                if (showTrainingDate.value && trainingStatus.date) {
                    content += ` ${formatDate(trainingStatus.date)}`;
                }
                if (showTrainingInstructor.value && trainingStatus.instructor) {
                    content += ` (${trainingStatus.instructor})`;
                }
                return content;
            }
            return '✗';

        case 'custom':
            return col.customValue || '';

        default:
            return '';
    }
};

// Helper to get multi-value items for a column
const getMultiValueItems = (col: ExportColumn): string[] => {
    const items = new Set<string>();
    if (col.field === 'companies') {
        filteredEmployees.value.forEach(emp => {
            emp.companies?.forEach(c => items.add(c.name));
        });
    } else if (col.field === 'departments') {
        filteredEmployees.value.forEach(emp => {
            emp.departments?.forEach(d => items.add(d.name));
        });
    } else if (col.field === 'licenses') {
        filteredEmployees.value.forEach(emp => {
            emp.licenses?.forEach(l => items.add(l.license));
        });
    }
    return Array.from(items).sort();
};

// Helper to check if employee has a specific multi-value item
const employeeHasMultiValueItem = (employee: Employee, col: ExportColumn, item: string): boolean => {
    if (col.field === 'companies') {
        return employee.companies?.some(c => c.name === item) || false;
    } else if (col.field === 'departments') {
        return employee.departments?.some(d => d.name === item) || false;
    } else if (col.field === 'licenses') {
        return employee.licenses?.some(l => l.license === item) || false;
    }
    return false;
};

// Get all multi-values for an employee for a specific column
const getEmployeeMultiValues = (employee: Employee, col: ExportColumn): string[] => {
    if (col.field === 'companies') {
        return employee.companies?.map(c => c.name) || [];
    } else if (col.field === 'departments') {
        return employee.departments?.map(d => d.name) || [];
    } else if (col.field === 'licenses') {
        return employee.licenses?.map(l => l.license) || [];
    }
    return [];
};

// Helper to build merged headers for training categories
const buildMergedHeaders = (columns: ExportColumn[]): { headerRow1: string; headerRow2: string; needsTwoRows: boolean } => {
    const headerStyle = 'background-color:#c9c9c9; font-weight:bold; white-space:nowrap; padding:5px; text-align:center;';

    // Group consecutive training columns by category
    const groups: { type: 'single' | 'category'; cols: ExportColumn[]; categoryName?: string; categoryId?: number }[] = [];
    let currentCategory: number | null = null;
    let currentGroup: ExportColumn[] = [];

    columns.forEach((col) => {
        if (col.type === 'training' && col.categoryId) {
            if (currentCategory === col.categoryId) {
                currentGroup.push(col);
            } else {
                if (currentGroup.length > 0) {
                    const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                    groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
                }
                currentCategory = col.categoryId;
                currentGroup = [col];
            }
        } else {
            if (currentGroup.length > 0) {
                const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
                currentGroup = [];
                currentCategory = null;
            }
            groups.push({ type: 'single', cols: [col] });
        }
    });

    if (currentGroup.length > 0) {
        const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
        groups.push({ type: 'category', cols: currentGroup, categoryName: catName, categoryId: currentGroup[0].categoryId });
    }

    const needsTwoRows = groups.some(g => g.type === 'category' && g.cols.length > 1);

    if (!needsTwoRows) {
        let headerRow = '<tr>';
        columns.forEach(col => {
            const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                ? getCategoryColor(col.categoryId, 0.5)
                : '#c9c9c9';
            headerRow += `<td style="background-color:${bgColor}; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
        });
        headerRow += '</tr>';
        return { headerRow1: headerRow, headerRow2: '', needsTwoRows: false };
    }

    let headerRow1 = '<tr>';
    let headerRow2 = '<tr>';

    groups.forEach(group => {
        if (group.type === 'category' && group.cols.length > 1) {
            const catBgColor = useCategoryColors.value && group.categoryId
                ? getCategoryColor(group.categoryId, 0.7)
                : '#a0a0a0';
            headerRow1 += `<td style="background-color:${catBgColor}; font-weight:bold; white-space:nowrap; padding:5px; text-align:center;" colspan="${group.cols.length}">${group.categoryName}</td>`;
            group.cols.forEach(col => {
                const bgColor = useCategoryColors.value && col.categoryId
                    ? getCategoryColor(col.categoryId, 0.4)
                    : '#c9c9c9';
                headerRow2 += `<td style="background-color:${bgColor}; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
            });
        } else {
            const col = group.cols[0];
            headerRow1 += `<td style="${headerStyle}" rowspan="2">${col.label}</td>`;
        }
    });

    headerRow1 += '</tr>';
    headerRow2 += '</tr>';

    return { headerRow1, headerRow2, needsTwoRows: true };
};

const generateExport = async () => {
    exporting.value = true;
    try {
        const headerStyle = 'background-color:#c9c9c9; font-weight:bold; white-space:nowrap; padding:5px;';
        const cellStyle = 'white-space:nowrap; padding:5px;';

        const multiValueCols = selectedColumns.value.filter(col => col.type === 'multi');

        if (multiValueFormat.value === 'columns' && multiValueCols.length > 0) {
            let headerRow = '<tr>';
            const expandedCols: { col: ExportColumn; item?: string }[] = [];

            selectedColumns.value.forEach(col => {
                if (col.type === 'multi') {
                    const items = getMultiValueItems(col);
                    items.forEach(item => {
                        headerRow += `<td style="${headerStyle}">${item}</td>`;
                        expandedCols.push({ col, item });
                    });
                } else {
                    const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                        ? getCategoryColor(col.categoryId, 0.5)
                        : '#c9c9c9';
                    headerRow += `<td style="background-color:${bgColor}; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
                    expandedCols.push({ col });
                }
            });
            headerRow += '</tr>';

            let bodyRows = '';
            filteredEmployees.value.forEach(employee => {
                let row = '<tr>';
                expandedCols.forEach(({ col, item }) => {
                    if (item) {
                        const hasItem = employeeHasMultiValueItem(employee, col, item);
                        row += `<td style="${cellStyle}">${hasItem ? '✓' : ''}</td>`;
                    } else {
                        const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                            ? getCategoryColor(col.categoryId, 0.15)
                            : '';
                        const style = bgColor ? `${cellStyle} background-color:${bgColor};` : cellStyle;
                        const cellContent = getCellValue(employee, col) || '&nbsp;';
                        row += `<td style="${style}">${cellContent}</td>`;
                    }
                });
                row += '</tr>';
                bodyRows += row;
            });

            const tableHTML = `<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tbody>
${headerRow}
${bodyRows}
</tbody>
</table>`;
            await navigator.clipboard.writeText(tableHTML);

        } else if (multiValueFormat.value === 'rows' && multiValueCols.length > 0) {
            let headerRow = '<tr>';
            selectedColumns.value.forEach(col => {
                const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                    ? getCategoryColor(col.categoryId, 0.5)
                    : '#c9c9c9';
                headerRow += `<td style="background-color:${bgColor}; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
            });
            headerRow += '</tr>';

            let bodyRows = '';
            filteredEmployees.value.forEach(employee => {
                let maxMultiValues = 1;
                multiValueCols.forEach(col => {
                    const values = getEmployeeMultiValues(employee, col);
                    if (values.length > maxMultiValues) maxMultiValues = values.length;
                });

                for (let i = 0; i < maxMultiValues; i++) {
                    let row = '<tr>';
                    selectedColumns.value.forEach(col => {
                        const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                            ? getCategoryColor(col.categoryId, 0.15)
                            : '';
                        const style = bgColor ? `${cellStyle} background-color:${bgColor};` : cellStyle;

                        if (col.type === 'multi') {
                            const values = getEmployeeMultiValues(employee, col);
                            const value = values[i] || '';
                            row += `<td style="${style}">${value || '&nbsp;'}</td>`;
                        } else {
                            const cellContent = getCellValue(employee, col) || '&nbsp;';
                            row += `<td style="${style}">${cellContent}</td>`;
                        }
                    });
                    row += '</tr>';
                    bodyRows += row;
                }
            });

            const tableHTML = `<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tbody>
${headerRow}
${bodyRows}
</tbody>
</table>`;
            await navigator.clipboard.writeText(tableHTML);

        } else {
            const trainingCols = selectedColumns.value.filter(col => col.type === 'training');
            const shouldUseMergedHeaders = useMergedHeaders.value && trainingCols.length > 1;

            let headerRows = '';
            if (shouldUseMergedHeaders) {
                const { headerRow1, headerRow2, needsTwoRows } = buildMergedHeaders(selectedColumns.value);
                headerRows = headerRow1 + (needsTwoRows ? headerRow2 : '');
            } else {
                headerRows = '<tr>';
                selectedColumns.value.forEach(col => {
                    const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                        ? getCategoryColor(col.categoryId, 0.5)
                        : '#c9c9c9';
                    headerRows += `<td style="background-color:${bgColor}; font-weight:bold; white-space:nowrap; padding:5px;">${col.label}</td>`;
                });
                headerRows += '</tr>';
            }

            let bodyRows = '';
            filteredEmployees.value.forEach(employee => {
                let row = '<tr>';
                selectedColumns.value.forEach(col => {
                    const bgColor = (col.type === 'training' && useCategoryColors.value && col.categoryId)
                        ? getCategoryColor(col.categoryId, 0.15)
                        : '';
                    const style = bgColor ? `${cellStyle} background-color:${bgColor};` : cellStyle;
                    const cellContent = getCellValue(employee, col) || '&nbsp;';
                    row += `<td style="${style}">${cellContent}</td>`;
                });
                row += '</tr>';
                bodyRows += row;
            });

            const tableHTML = `<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tbody>
${headerRows}
${bodyRows}
</tbody>
</table>`;
            await navigator.clipboard.writeText(tableHTML);
        }

        toast.success(t('exportView.exportSuccess'));
    } catch (error) {
        console.error('Error exporting:', error);
        toast.error(t('exportView.exportError'));
    } finally {
        exporting.value = false;
    }
};

// Google Sheets export functions
const copyScriptCode = async () => {
    try {
        await navigator.clipboard.writeText(googleAppsScriptCode);
        toast.success(t('exportView.scriptCopied'));
    } catch (error) {
        console.error('Failed to copy script:', error);
        toast.error(t('exportView.scriptCopyError'));
    }
};

// Helper to convert hex color to lighter version for data cells
const lightenColor = (hex: string, factor: number = 0.7): string => {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const newR = Math.round(r + (255 - r) * factor);
    const newG = Math.round(g + (255 - g) * factor);
    const newB = Math.round(b + (255 - b) * factor);
    return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB.toString(16).padStart(2, '0')}`;
};

const exportToGoogleSheets = async () => {
    if (!googleSheetsUrl.value || selectedColumns.value.length === 0) {
        return;
    }

    exportingToSheets.value = true;
    try {
        const columns = selectedColumns.value;

        // Build headers array (column labels)
        const headers = columns.map(col => col.label);

        // Build header colors (for header row)
        const headerColors: (string | null)[] = columns.map(col => {
            if (col.type === 'training' && col.categoryId && useCategoryColors.value) {
                return lightenColor(categoryColors[col.categoryId % categoryColors.length], 0.4);
            }
            return null;
        });

        // Build column colors (for data cells - lighter version)
        const columnColors: (string | null)[] = columns.map(col => {
            if (col.type === 'training' && col.categoryId && useCategoryColors.value) {
                return lightenColor(categoryColors[col.categoryId % categoryColors.length], 0.85);
            }
            return null;
        });

        // Build merged headers for category grouping
        let mergedHeaders: { label: string; colspan: number; color: string | null }[] = [];

        if (useMergedHeaders.value) {
            // Group consecutive training columns by category
            let currentCategory: number | null = null;
            let currentGroup: ExportColumn[] = [];

            columns.forEach((col, idx) => {
                if (col.type === 'training' && col.categoryId) {
                    if (currentCategory === col.categoryId) {
                        currentGroup.push(col);
                    } else {
                        if (currentGroup.length > 0) {
                            const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                            const catColor = useCategoryColors.value && currentGroup[0].categoryId
                                ? lightenColor(categoryColors[currentGroup[0].categoryId % categoryColors.length], 0.3)
                                : '#a0a0a0';
                            mergedHeaders.push({
                                label: catName,
                                colspan: currentGroup.length,
                                color: catColor
                            });
                        }
                        currentCategory = col.categoryId;
                        currentGroup = [col];
                    }
                } else {
                    if (currentGroup.length > 0) {
                        const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                        const catColor = useCategoryColors.value && currentGroup[0].categoryId
                            ? lightenColor(categoryColors[currentGroup[0].categoryId % categoryColors.length], 0.3)
                            : '#a0a0a0';
                        mergedHeaders.push({
                            label: catName,
                            colspan: currentGroup.length,
                            color: catColor
                        });
                        currentGroup = [];
                        currentCategory = null;
                    }
                    mergedHeaders.push({
                        label: col.label,
                        colspan: 1,
                        color: null
                    });
                }
            });

            // Don't forget the last group
            if (currentGroup.length > 0) {
                const catName = currentGroup[0].categoryName || currentGroup[0].categoryShort || 'Trainings';
                const catColor = useCategoryColors.value && currentGroup[0].categoryId
                    ? lightenColor(categoryColors[currentGroup[0].categoryId % categoryColors.length], 0.3)
                    : '#a0a0a0';
                mergedHeaders.push({
                    label: catName,
                    colspan: currentGroup.length,
                    color: catColor
                });
            }

            // Only use merged headers if there are actual category groups with more than 1 column
            const hasRealMerge = mergedHeaders.some(h => h.colspan > 1);
            if (!hasRealMerge) {
                mergedHeaders = [];
            }
        }

        // Build rows array
        const rows = filteredEmployees.value.map(employee => {
            return columns.map(col => {
                const value = getCellValue(employee, col);
                return value || '';
            });
        });

        // Prepare payload for Google Apps Script
        const payload: any = {
            clearFirst: true,
            headers: headers,
            rows: rows,
            headerColors: useCategoryColors.value ? headerColors : [],
            columnColors: useCategoryColors.value ? columnColors : []
        };

        // Add merged headers if enabled and applicable
        if (mergedHeaders.length > 0) {
            payload.mergedHeaders = mergedHeaders;
        }

        // Send to Google Apps Script web app
        const response = await fetch(googleSheetsUrl.value, {
            method: 'POST',
            mode: 'no-cors', // Google Apps Script requires no-cors mode
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        // Due to no-cors, we can't read the response
        // We assume success if no error was thrown
        toast.success(t('exportView.sheetsExportSuccess', { count: rows.length }));

    } catch (error) {
        console.error('Error exporting to Google Sheets:', error);
        toast.error(t('exportView.sheetsExportError'));
    } finally {
        exportingToSheets.value = false;
    }
};

// --- Lifecycle ---
onMounted(async () => {
    await Promise.all([
        loadPresetsFromAPI(),
        fetchData()
    ]);
    if (selectedColumns.value.length === 0) {
        selectedColumns.value = [
            { id: 'servicenumber', label: t('exportView.fields.servicenumber'), type: 'basic', field: 'servicenumber' },
            { id: 'name', label: t('exportView.fields.name'), type: 'basic', field: 'name' },
            { id: 'rank', label: t('exportView.fields.rank'), type: 'basic', field: 'rank' },
        ];
    }
});
</script>

<style scoped>
.export-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
}

.header-title {
    animation: fadeIn 0.5s ease-out;
}

.header-icon {
    filter: drop-shadow(0 2px 6px var(--k-accent-line));
}

.config-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(8px);
    border-radius: 12px;
}

.category-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.selected-columns-list {
    background: rgba(30, 41, 59, 0.4) !important;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
}

.draggable-column-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid var(--k-line);
    cursor: grab;
    transition: background-color 0.2s;
}

.draggable-column-item:hover {
    background-color: var(--k-accent-weak);
}

.draggable-column-item.drag-over {
    background-color: var(--k-accent-line);
    border-top: 2px solid var(--k-accent);
}

.draggable-column-item:active {
    cursor: grabbing;
}

.draggable-column-item:last-child {
    border-bottom: none;
}

.drag-handle {
    opacity: 0.5;
    cursor: grab;
}

.column-actions {
    display: flex;
    gap: 2px;
}

.preset-item {
    cursor: pointer;
    border-radius: 8px;
    margin-bottom: 4px;
}

.preset-item:hover {
    background-color: var(--k-accent-weak);
}

.loading-state {
    min-height: 400px;
}

.preview-table-wrapper {
    overflow-x: auto;
    max-height: 250px;
    overflow-y: auto;
    border-radius: 8px;
    background: rgba(30, 41, 59, 0.4);
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
}

.preview-table th {
    background-color: var(--k-accent-weak);
    color: var(--k-accent-line);
    font-weight: 600;
    padding: 8px 12px;
    text-align: left;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 1;
    border: 1px solid var(--k-line);
}

.preview-table th.category-header {
    text-align: center;
    font-weight: 700;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.preview-table td {
    padding: 6px 12px;
    border-bottom: 1px solid var(--k-line);
    white-space: nowrap;
    color: var(--k-ink-muted);
}

.preview-table tr:hover td {
    background-color: var(--k-accent-weak);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.code-block {
    position: relative;
    background: rgba(0, 0, 0, 0.4);
    border-radius: 8px;
    padding: 12px;
    overflow-x: auto;
}

.code-block pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-all;
    color: #a5d6ff;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 11px;
    line-height: 1.4;
}

.copy-code-btn {
    position: absolute;
    top: 8px;
    right: 8px;
}
</style>
