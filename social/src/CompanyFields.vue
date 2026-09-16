<script setup lang="ts">
import { ref } from "vue";
import { upload, mediaUrl, type Row } from "./api";
import CompanyMap from "./CompanyMap.vue";
const form = defineModel<Row>({ required: true });
const error = ref("");
const uploading = ref(false);
const emit = defineEmits(["busy"]);
async function photo(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  uploading.value = true;
  emit("busy", true);
  error.value = "";
  try {
    const r = await upload(file, "social", "company");
    form.value.photo_id = r.id;
  } catch (e) {
    error.value = String(e);
  } finally {
    uploading.value = false;
    emit("busy", false);
    input.value = "";
  }
}
</script>
<template>
  <label
    >Leistungen / Zuständigkeit<textarea
      v-model="form.services"
      maxlength="1000"
      placeholder="Zum Beispiel Reparaturen, Abschleppdienst und Fahrzeugpflege"
    ></textarea></label
  ><label
    >Einsatzgebiet<input
      v-model="form.service_area"
      maxlength="500"
      placeholder="Zum Beispiel Los Santos und Blaine County" /></label
  ><label
    >Öffnungszeiten<textarea
      v-model="form.opening_hours"
      maxlength="1500"
      placeholder="Mo–Fr: 18:00–23:00 Uhr&#10;Sa: nach Vereinbarung"
    ></textarea></label
  ><label
    >Bild des Unternehmens<input
      type="file"
      accept="image/jpeg,image/png,image/webp"
      :disabled="uploading"
      @change="photo"
  /></label>
  <p v-if="error" role="alert">{{ error }}</p>
  <img
    v-if="form.photo_id"
    class="company-photo"
    :src="mediaUrl(form.photo_id)"
    alt="Unternehmensbild"
  /><button v-if="form.photo_id" type="button" @click="form.photo_id = null">
    Bild entfernen</button
  ><CompanyMap
    :x="form.map_x"
    :y="form.map_y"
    editable
    @point="Object.assign(form, $event)"
  />
</template>
