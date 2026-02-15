<template>
  <v-container fluid class="pa-4">
    <!-- Breadcrumb -->
    <v-breadcrumbs :items="breadcrumbs" class="px-0 pb-4">
      <template v-slot:divider>
        <v-icon>mdi-chevron-right</v-icon>
      </template>
    </v-breadcrumbs>

    <!-- Page Title -->
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold mb-2">
          <v-icon left>mdi-cog</v-icon>
          Mail-Einstellungen
        </h1>
        <p class="text-subtitle-1 text-medium-emphasis mb-4">
          Verwalten Sie Ihre Mail-Konten, Postfächer, Kontakte, Vorlagen und Signaturen
        </p>
      </v-col>
    </v-row>

    <!-- Tabs Navigation -->
    <v-card>
      <v-tabs
        v-model="activeTab"
        bg-color="primary"
        color="white"
        align-tabs="start"
        show-arrows
      >
        <v-tab value="accounts">
          <v-icon left>mdi-email-outline</v-icon>
          Konten
        </v-tab>
        <v-tab value="mailboxes">
          <v-icon left>mdi-inbox-multiple</v-icon>
          Unternehmenspostfächer
        </v-tab>
        <v-tab value="contacts">
          <v-icon left>mdi-account-multiple</v-icon>
          Kontakte
        </v-tab>
        <v-tab value="templates">
          <v-icon left>mdi-file-document-outline</v-icon>
          Vorlagen
        </v-tab>
        <v-tab value="signatures">
          <v-icon left>mdi-draw</v-icon>
          Signaturen
        </v-tab>
      </v-tabs>

      <v-divider></v-divider>

      <!-- Tab Content -->
      <v-window v-model="activeTab">
        <!-- Accounts Tab -->
        <v-window-item value="accounts">
          <MailAccountsSettings />
        </v-window-item>

        <!-- Company Mailboxes Tab -->
        <v-window-item value="mailboxes">
          <CompanyMailboxSettings />
        </v-window-item>

        <!-- Contacts Tab -->
        <v-window-item value="contacts">
          <ContactsSettings />
        </v-window-item>

        <!-- Templates Tab -->
        <v-window-item value="templates">
          <TemplatesSettings />
        </v-window-item>

        <!-- Signatures Tab -->
        <v-window-item value="signatures">
          <SignaturesSettings />
        </v-window-item>
      </v-window>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import MailAccountsSettings from '@/components/mail/settings/MailAccountsSettings.vue'
import CompanyMailboxSettings from '@/components/mail/settings/CompanyMailboxSettings.vue'
import ContactsSettings from '@/components/mail/settings/ContactsSettings.vue'
import TemplatesSettings from '@/components/mail/settings/TemplatesSettings.vue'
import SignaturesSettings from '@/components/mail/settings/SignaturesSettings.vue'

// Route
const route = useRoute()

// Active tab state
const activeTab = ref('accounts')

// Breadcrumbs
const breadcrumbs = [
  {
    title: 'Mail',
    disabled: false,
    to: '/mail'
  },
  {
    title: 'Einstellungen',
    disabled: true
  }
]

// On mount, check if there's a tab query parameter
onMounted(() => {
  if (route.query.tab && typeof route.query.tab === 'string') {
    activeTab.value = route.query.tab
  }
})
</script>

<style scoped>
.v-breadcrumbs {
  font-size: 0.875rem;
}
</style>
