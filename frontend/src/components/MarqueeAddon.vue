<template>
  <div class="marquee-container">
    <div class="marquee">
      <span><slot></slot></span>
    </div>
  </div>
</template>

<script setup lang="ts">
// No script logic needed for this simple presentation component.
// Props could be added here later if needed (e.g., speed, direction).
// ;
// defineProps({
//   speed: { type: String, default: '25s' },
// });
</script>

<style scoped>
.marquee-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  white-space: nowrap;
  box-sizing: border-box;
  display: inline-block;
  vertical-align: middle;
  padding: 2px 0;
}

.marquee-container::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 40px;
  background: linear-gradient(to right, var(--surface), transparent);
  z-index: 1;
}

.marquee-container::after {
  content: '';
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 40px;
  background: linear-gradient(to left, var(--surface), transparent);
  z-index: 1;
}

.marquee {
  width: 100%;
  white-space: nowrap;
  position: relative;
}

.marquee span {
  display: inline-block;
  padding-left: 100%;
  animation: marquee-animation 25s linear infinite;
  font-weight: 500;
  background: linear-gradient(90deg, var(--text), var(--primary-light));
  -webkit-background-clip: text;
  background-clip: text;
}

@keyframes marquee-animation {
  0% {
    transform: translate(0, 0);
  }
  100% {
    transform: translate(-100%, 0);
  }
}

/* Pausiere Animation beim Hover für bessere Lesbarkeit */
.marquee:hover span {
  animation-play-state: paused;
}
</style>