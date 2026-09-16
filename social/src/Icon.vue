<script setup lang="ts">
defineProps<{ name: string }>();
const paths: Record<string, string> = {
  "app-social":
    "M3 7h12v9H8l-5 4z M10 3h11v10l-4-2 M6 13V10h2v3 M10 13V8h2v5 M17 4V1 M15 2l2 2 3-2",
  "app-gram":
    "M3 5l14-3 4 17-14 3z M8 16l2-5 3 3 3-2 M11 10V6 M7 7l4-1 3 1 M2 9v12h3",
  "app-market":
    "M8 9l2-5h11l1 5z M10 10v10h11V10 M13 20v-5h5v5 M3 22V7 M1 4l2 3 4-2 M3 7l-3 2 M3 7l5 2 M3 7l1-5 M8 22h14",
  "app-video":
    "M3 10h18v11H3z M7 10V6l5-4 5 4v4 M10 13l5 3-5 3z M5 13v1 M5 17v1 M19 13v1 M19 17v1 M10 7h4",
  apps: "M3 3h6v6H3z M15 3h6v6h-6z M3 15h6v6H3z M15 15h6v6h-6z",
  social: "M4 4h16v12H9l-5 4z M8 8h8 M8 12h5",
  gram: "M5 3h16v16H5z M2 7v15h15 M8 15l4-5 3 3 3-2",
  market:
    "M3 8l2-5h14l2 5 M3 8v3c0 3 4 3 4 0 0 3 5 3 5 0 0 3 5 3 5 0 0 3 4 3 4 0V8H3 M5 14v7h14v-7 M10 21v-5h4v5",
  video: "M3 5h18v16H3z M3 9h18 M7 5l3 4 M14 5l3 4 M10 12l5 3-5 3z",
  messages: "M3 5h18v14H3z M3 5l9 8 9-8",
  friends:
    "M9 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M2 21v-2a7 7 0 0 1 14 0v2 M17 4a4 4 0 0 1 0 8 M18 15a5 5 0 0 1 4 5",
  bell: "M5 17h14l-2-3V9a5 5 0 0 0-10 0v5z M10 21h4",
  search: "M15 15l6 6 M17 10a7 7 0 1 0-14 0 7 7 0 0 0 14 0",
  sun: "M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 1v3 M12 20v3 M1 12h3 M20 12h3 M4 4l2 2 M18 18l2 2 M4 20l2-2 M18 6l2-2",
  like: "M8 10l4-7c3 0 2 5 2 6h6l-2 12H8z M3 10h5v11H3z",
  dislike: "M8 14l4 7c3 0 2-5 2-6h6L18 3H8z M3 3h5v11H3z",
  comment: "M3 4h18v13H9l-6 4z",
  share: "M8 8h12l-4-4 M20 8l-4 4 M16 16H4l4 4 M4 16l4-4",
  user: "M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M4 22v-3a8 8 0 0 1 16 0v3",
  settings:
    "M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2",
  plus: "M12 4v16 M4 12h16",
  close: "M5 5l14 14 M19 5L5 19",
  link: "M10 14l4-4 M8 16l-2 2a4 4 0 0 1-6-6l5-5a4 4 0 0 1 6 0 M16 8l2-2a4 4 0 0 1 6 6l-5 5a4 4 0 0 1-6 0",
  flag: "M5 22V3h14l-3 5 3 5H5",
  logout: "M9 3H3v18h6 M9 12h12l-4-4 M21 12l-4 4",
  edit: "M3 21l1-6L17 2l5 5L9 20z M14 5l5 5",
  arrow: "M5 12h14 M14 6l6 6-6 6",
  shield: "M12 2l9 4v7c0 5-9 9-9 9s-9-4-9-9V6z M7 12l3 3 7-7",
};
</script>
<template>
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="paths[name] || paths.apps" />
  </svg>
</template>
