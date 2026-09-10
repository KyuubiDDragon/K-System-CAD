<template>
    <div class="settings-section">
        <h3>Kontaktinformationen</h3>
        <div class="form-group">
            <label for="contact-email">Kontakt E-Mail</label>
            <input
                type="email"
                id="contact-email"
                v-model="localSettings.contact_email"
                class="form-control"
                @input="emitChange"
            />
        </div>
        <div class="form-group">
            <label for="contact-phone">Kontakt Telefon</label>
            <input
                type="text"
                id="contact-phone"
                v-model="localSettings.contact_phone"
                class="form-control"
                @input="emitChange"
            />
        </div>
        <div class="form-group">
            <label for="social-links">Social Media Links</label>
            <div
                v-for="(link, index) in localSocialLinks"
                :key="index"
                class="social-link-row"
            >
                <div class="social-link-platform">
                    <select
                        v-model="link.platform"
                        class="form-control"
                        @change="updateSocialLinks"
                    >
                        <option value="mail">E-Mail</option>
                        <option value="social">Social Media</option>
                    </select>
                </div>
                <div class="social-link-url">
                    <input
                        type="url"
                        v-model="link.url"
                        class="form-control"
                        placeholder="https://"
                        @input="updateSocialLinks"
                    />
                </div>
                <div class="social-link-actions">
                    <button @click="removeSocialLink(index)" class="btn-icon">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
            <button @click="addSocialLink" class="btn btn-sm btn-secondary mt-2">
                <i class="mdi mdi-plus"></i> Social Media Link hinzufügen
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface SocialLink {
    platform: string;
    url: string;
}

interface SocialLinksSettings {
    contact_email: string;
    contact_phone: string;
}

interface Props {
    modelValue: SocialLinksSettings;
    socialLinks: SocialLink[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: SocialLinksSettings): void;
    (e: 'change'): void;
    (e: 'updateSocialLinks', links: SocialLink[]): void;
}>();

const localSettings = ref<SocialLinksSettings>({ ...props.modelValue });
const localSocialLinks = ref<SocialLink[]>([...props.socialLinks]);

watch(
    () => props.modelValue,
    (newValue) => {
        localSettings.value = { ...newValue };
    },
    { deep: true }
);

watch(
    () => props.socialLinks,
    (newValue) => {
        localSocialLinks.value = [...newValue];
    },
    { deep: true }
);

function emitChange() {
    emit('update:modelValue', { ...localSettings.value });
    emit('change');
}

function addSocialLink() {
    let platform = 'mail';
    if (localSocialLinks.value.length > 0) {
        const hasMailLink = localSocialLinks.value.some((link) => link.platform === 'mail');
        if (hasMailLink) {
            platform = 'social';
        }
    }

    localSocialLinks.value.push({
        platform: platform,
        url: '',
    });
    updateSocialLinks();
}

function removeSocialLink(index: number) {
    localSocialLinks.value.splice(index, 1);
    updateSocialLinks();
}

function updateSocialLinks() {
    emit('updateSocialLinks', [...localSocialLinks.value]);
    emit('change');
}
</script>

<style scoped>
.settings-section {
    margin-bottom: 2rem;
}

.settings-section h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--k-ink);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--k-ink);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--k-line);
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--k-accent);
    box-shadow: 0 0 0 3px var(--k-accent-weak);
}

.social-link-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    align-items: center;
}

.social-link-platform {
    flex: 0 0 150px;
}

.social-link-url {
    flex: 1;
}

.social-link-actions {
    flex: 0 0 auto;
}

.btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    border: none;
    background-color: var(--k-critical);
    color: var(--k-ink);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.btn-icon:hover {
    background-color: var(--k-critical);
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

.btn-secondary {
    background-color: var(--k-neutral);
    color: var(--k-ink);
}

.btn-secondary:hover {
    background-color: var(--k-neutral);
}

.mt-2 {
    margin-top: 0.5rem;
}
</style>
