<script setup lang="ts">
import { ref } from "vue";
import { api, mediaUrl, type Row } from "./api";
import VerifiedIcon from "./VerifiedIcon.vue";
import Icon from "./Icon.vue";
import RichText from "./RichText.vue";
const props = defineProps<{ post: Row; me: Row | null }>();
const emit = defineEmits([
  "refresh",
  "error",
  "edit",
  "contact",
  "profile",
  "company",
  "share",
  "open",
]);
const comments = ref<Row[]>([]),
  expanded = ref(false),
  body = ref(""),
  busy = ref(false);
const date = (s: string) =>
  new Date(s.replace(" ", "T") + "Z").toLocaleString("de-DE", {
    dateStyle: "medium",
    timeStyle: "short",
  });
async function bookmark() {
  try {
    await api("bookmark", { id: props.post.id, remove: props.post.bookmarked });
    emit("refresh");
  } catch (e) {
    emit("error", String(e));
  }
}
async function react(value: number) {
  try {
    await api("reaction", {
      id: props.post.id,
      value: props.post.reaction === value ? 0 : value,
    });
    emit("refresh");
  } catch (e) {
    emit("error", String(e));
  }
}
async function showComments() {
  try {
    comments.value = (
      await api("comments", undefined, { id: props.post.id })
    ).items;
    expanded.value = true;
  } catch (e) {
    emit("error", String(e));
  }
}
async function comment() {
  busy.value = true;
  try {
    await api("comment", { id: props.post.id, body: body.value });
    body.value = "";
    await showComments();
  } catch (e) {
    emit("error", String(e));
  } finally {
    busy.value = false;
  }
}
async function report() {
  const reason = prompt("Warum möchtest du diesen Beitrag melden?");
  if (!reason) return;
  try {
    await api("report", { kind: "post", id: props.post.id, reason });
    emit("error", "Meldung wurde übermittelt.");
  } catch (e) {
    emit("error", String(e));
  }
}
</script>
<template>
  <article class="post panel">
    <header class="post-author">
      <button class="avatar" @click="emit('profile', post.author_id)">
        <img
          v-if="post.author.avatar_id"
          :src="mediaUrl(post.author.avatar_id)"
          alt=""
        /><span v-else>{{ post.author.display_name.slice(0, 2) }}</span>
      </button>
      <div>
        <button
          class="text-button"
          @click="
            post.company
              ? emit('company', post.company.id)
              : emit('profile', post.author_id)
          "
        >
          <strong
            >{{ post.company?.name || post.author.display_name
            }}<VerifiedIcon v-if="Number(post.company?.verified)"
          /></strong></button
        ><small>@{{ post.author.handle }} · {{ date(post.created_at) }}</small
        ><small>{{
          {
            public: "Öffentlich",
            friends: "Freunde",
            friends_of_friends: "Freunde von Freunden",
          }[post.visibility as string]
        }}</small>
      </div>
      <button
        v-if="me && post.can_edit"
        class="icon-button end"
        aria-label="Beitrag bearbeiten"
        @click="emit('edit', post)"
      >
        <Icon name="edit" /></button
      ><button
        v-else-if="me && !me.delegated"
        class="icon-button end"
        aria-label="Beitrag melden"
        @click="report"
      >
        <Icon name="flag" />
      </button>
    </header>
    <div class="post-labels">
      <span v-if="Number(post.ai_generated)" class="badge">KI-generiert</span>
    </div>
    <h2 v-if="post.title">
      <button class="text-button" @click="emit('open', post.id)">
        {{ post.title }}
      </button>
    </h2>
    <RichText :text="post.body" />
    <div v-if="post.state !== 'published'" class="notice">
      {{
        {
          pending: "Wartet auf Freigabe",
          rejected: "Abgelehnt",
          hidden: "Ausgeblendet",
        }[post.state as string]
      }}<span v-if="post.review_reason"> · {{ post.review_reason }}</span>
    </div>
    <div class="post-media" :class="{ multiple: post.media.length > 1 }">
      <template v-for="file in post.media" :key="file.id"
        ><img
          v-if="file.mime.startsWith('image/')"
          :src="mediaUrl(file.id)"
          :alt="post.title || 'Beitragsbild'"
          loading="lazy"
        /><video
          v-else-if="file.state === 'ready'"
          :src="mediaUrl(file.id)"
          controls
          preload="metadata"
        ></video>
        <div v-else class="notice">
          {{
            file.state === "failed"
              ? "Verarbeitung fehlgeschlagen"
              : "Video wird verarbeitet"
          }}.
        </div></template
      >
    </div>
    <div v-if="post.shared_id" class="shared">
      <template v-if="post.shared"
        ><small>Geteilt von {{ post.shared.author.display_name }}</small
        ><button class="text-button" @click="emit('open', post.shared.id)">
          <strong>{{ post.shared.title || "Beitrag ansehen" }}</strong></button
        ><RichText :text="post.shared.body" /><template
          v-for="file in post.shared.media"
          :key="file.id"
          ><img
            v-if="file.mime.startsWith('image/')"
            :src="mediaUrl(file.id)"
            alt="Geteiltes Bild" /><video
            v-else-if="file.state === 'ready'"
            :src="mediaUrl(file.id)"
            controls
            preload="metadata"
          ></video></template></template
      ><span v-else>Der ursprüngliche Inhalt ist nicht mehr verfügbar.</span>
    </div>
    <div v-if="post.module === 'market'" class="listing-price">
      <strong>{{ Number(post.price).toLocaleString("de-DE") }} RP-$</strong
      ><span class="badge">{{
        { available: "Verfügbar", reserved: "Reserviert", sold: "Verkauft" }[
          post.sale_state as string
        ]
      }}</span
      ><button
        v-if="me && !me.delegated && me.id !== Number(post.author_id)"
        @click="emit('contact', post.author_id)"
      >
        Verkäufer kontaktieren
      </button>
    </div>
    <div v-if="post.category" class="post-category">
      Kategorie <span class="badge">{{ post.category }}</span>
    </div>
    <footer class="post-actions">
      <button
        v-if="me && !me.delegated"
        :aria-pressed="Boolean(post.bookmarked)"
        @click="bookmark"
        :aria-label="
          post.bookmarked ? 'Lesezeichen entfernen' : 'Beitrag speichern'
        "
      >
        {{ post.bookmarked ? "★ Gespeichert" : "☆ Merken" }}
      </button>
      <button
        :disabled="!me || me.delegated || post.state !== 'published'"
        :aria-pressed="post.reaction === 1"
        @click="react(1)"
      >
        <Icon name="like" />{{ post.likes }}</button
      ><button
        :disabled="!me || me.delegated || post.state !== 'published'"
        :aria-pressed="post.reaction === -1"
        @click="react(-1)"
      >
        <Icon name="dislike" />{{ post.dislikes }}</button
      ><button @click="showComments">
        <Icon name="comment" />{{ post.comments }}</button
      ><button
        :disabled="!me || me.delegated || post.state !== 'published'"
        @click="emit('share', post)"
      >
        <Icon name="share" />Teilen
      </button>
    </footer>
    <section v-if="expanded" class="comments">
      <p v-if="!comments.length" class="muted">Noch keine Kommentare.</p>
      <article v-for="c in comments" :key="c.id">
        <strong>{{ c.author.display_name }}</strong
        ><RichText :text="c.body" />
      </article>
      <form v-if="me && !me.delegated" @submit.prevent="comment">
        <label
          >Kommentar<textarea
            v-model="body"
            required
            maxlength="4000"
            rows="2"
          ></textarea></label
        ><button class="primary" :disabled="busy">Kommentieren</button>
      </form>
    </section>
  </article>
</template>
