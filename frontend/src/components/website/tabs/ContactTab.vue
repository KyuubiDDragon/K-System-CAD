<template>
    <div class="contact-tab">
        <h2>Kontaktanfragen</h2>
        <p>
            Hier können Sie eingehende Kontaktanfragen von Ihrer Website einsehen
            und verwalten.
        </p>

        <div class="filter-controls">
            <div class="filter-group">
                <label for="contact-filter">Filter:</label>
                <select
                    id="contact-filter"
                    v-model="localFilter"
                    class="form-control"
                >
                    <option value="all">Alle Anfragen</option>
                    <option value="unread">Ungelesene Anfragen</option>
                    <option value="read">Gelesene Anfragen</option>
                </select>
            </div>
        </div>

        <div v-if="isLoading" class="loading-contacts">
            <i class="mdi mdi-spinner fa-spin"></i> Anfragen werden geladen...
        </div>

        <div v-else-if="filteredContactList.length > 0" class="contacts-list">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="status-col"></th>
                        <th>Absender</th>
                        <th>Betreff</th>
                        <th>Datum</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="contact in filteredContactList"
                        :key="contact.id"
                        :class="{ unread: !contact.is_read }"
                        @click="$emit('view', contact)"
                    >
                        <td class="status-col">
                            <i
                                v-if="!contact.is_read"
                                class="mdi mdi-circle unread-indicator"
                            ></i>
                        </td>
                        <td>{{ contact.name }} &lt;{{ contact.email }}&gt;</td>
                        <td>{{ contact.subject || 'Keine Betreffzeile' }}</td>
                        <td>{{ formatDate(contact.created_at) }}</td>
                        <td class="actions">
                            <button
                                @click.stop="$emit('toggleRead', contact)"
                                class="btn-icon"
                                :title="
                                    contact.is_read
                                        ? 'Als ungelesen markieren'
                                        : 'Als gelesen markieren'
                                "
                            >
                                <i
                                    :class="
                                        contact.is_read
                                            ? 'mdi mdi-envelope-open'
                                            : 'mdi mdi-envelope'
                                    "
                                ></i>
                            </button>
                            <button
                                @click.stop="$emit('delete', contact)"
                                class="btn-icon"
                                title="Löschen"
                            >
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="empty-state">
            <p>Keine Kontaktanfragen vorhanden.</p>
        </div>

        <div v-if="selectedContact" class="contact-detail-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>{{ selectedContact.subject || 'Keine Betreffzeile' }}</h3>
                    <button @click="$emit('closeDetail')" class="btn-icon">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="contact-info">
                        <p>
                            <strong>Von:</strong> {{ selectedContact.name }} &lt;{{
                                selectedContact.email
                            }}&gt;
                        </p>
                        <p>
                            <strong>Datum:</strong>
                            {{ formatDate(selectedContact.created_at, true) }}
                        </p>
                    </div>
                    <div class="contact-message">
                        <p>{{ selectedContact.message }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="$emit('reply', selectedContact)" class="btn btn-primary">
                        <i class="mdi mdi-reply"></i> Antworten
                    </button>
                    <button
                        @click="$emit('toggleRead', selectedContact, true)"
                        class="btn btn-secondary"
                    >
                        <i
                            :class="
                                selectedContact.is_read
                                    ? 'mdi mdi-envelope'
                                    : 'mdi mdi-envelope-open'
                            "
                        ></i>
                        {{
                            selectedContact.is_read
                                ? 'Als ungelesen markieren'
                                : 'Als gelesen markieren'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

interface Contact {
    id: number;
    name: string;
    email: string;
    subject?: string;
    message: string;
    created_at: string;
    is_read: boolean;
}

interface Props {
    contacts: Contact[];
    isLoading?: boolean;
    filter?: string;
    selectedContact?: Contact | null;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
    filter: 'all',
    selectedContact: null,
});

const emit = defineEmits<{
    view: [contact: Contact];
    delete: [contact: Contact];
    toggleRead: [contact: Contact, closeAfter?: boolean];
    reply: [contact: Contact];
    closeDetail: [];
    'update:filter': [value: string];
}>();

const localFilter = ref(props.filter);

watch(localFilter, (newValue) => {
    emit('update:filter', newValue);
});

watch(() => props.filter, (newValue) => {
    localFilter.value = newValue;
});

const filteredContactList = computed(() => {
    if (localFilter.value === 'all') {
        return props.contacts;
    } else if (localFilter.value === 'unread') {
        return props.contacts.filter(contact => !contact.is_read);
    } else {
        return props.contacts.filter(contact => contact.is_read);
    }
});

function formatDate(dateString: string, includeTime = false): string {
    if (!dateString) return '';

    const date = new Date(dateString);
    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    };

    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }

    return date.toLocaleDateString('de-DE', options);
}
</script>

<style scoped>
/* Contact tab specific styles can be imported from parent or defined here */
</style>
