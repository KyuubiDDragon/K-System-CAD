import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api';
import type { DocArea } from '@/types';

const { t } = useI18n();

// Dokumentenbereiche
const documentAreas = ref<DocArea[]>([]);
const loadingDocAreas = ref(false);

// Fetch document areas on component mount
onMounted(async () => {
    // Dokumentenbereiche laden, wenn der Benutzer eingeloggt ist
    if (authStore.isLoggedIn) {
        await fetchDocumentAreas();
    }
});

// Dokumentenbereiche abrufen
async function fetchDocumentAreas() {
    loadingDocAreas.value = true;
    try {
        const response = await apiClientAuth.post('/document/?action=getAreas');
        documentAreas.value = response.data.filter((area: DocArea) => 
            area.is_active && area.permissions?.can_read
        );
    } catch (error) {
        console.error('Failed to fetch document areas:', error);
        documentAreas.value = [];
    } finally {
        loadingDocAreas.value = false;
    }
}

<template>
    <!-- Dokumente Menü -->
    <v-list-group
        v-if="hasFeature('document')"
        value="documents"
        prepend-icon="mdi-file-document-multiple-outline"
    >
        <template v-slot:activator="{ props }">
            <v-list-item
                v-bind="props"
                :title="t('menu.documents')"
            />
        </template>
        
        <!-- Standardbereiche für Abwärtskompatibilität -->
        <v-list-item
            to="/document"
            :active="route.path === '/document'"
            v-if="hasPermission('READ_DOCUMENT_DOCUMENT')"
            prepend-icon="mdi-file-document-outline"
            :title="t('menu.documents')"
        />
        
        <v-list-item
            to="/document/global"
            :active="route.path === '/document/global'"
            v-if="hasPermission('READ_DOCUMENT_GLOBAL')"
            prepend-icon="mdi-account-group"
            :title="t('menu.authorityExchange')"
        />
        
        <!-- Dynamische Dokumentenbereiche -->
        <v-list-item
            v-for="area in documentAreas"
            :key="area.id"
            :to="`/documentarea/${area.key}`"
            :active="route.path === `/documentarea/${area.key}`"
            :prepend-icon="area.icon"
            :title="area.name"
        />
        
        <!-- Admin-Eintrag für Dokumentenbereiche -->
        <v-list-item
            to="/admin/documentareas"
            :active="route.path === '/admin/documentareas'"
            v-if="hasPermission('ADMIN_DOCUMENT_AREAS')"
            prepend-icon="mdi-cog-outline"
            :title="t('menu.manageAreas')"
        />
    </v-list-group>
</template> 