<template>
  <div class="page-content">
    <h1 v-if="showTitle">{{ page.title }}</h1>
    
    <website-renderer 
      :content="page.content" 
      :blocks="parsedBlocks" 
      :is-block-content="useBlocks"
    />
  </div>
</template>

<script>
import WebsiteRenderer from './WebsiteRenderer.vue';

export default {
  name: 'PageContent',
  components: {
    WebsiteRenderer
  },
  props: {
    page: {
      type: Object,
      required: true
    },
    showTitle: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    useBlocks() {
      return this.page.use_blocks && this.parsedBlocks.length > 0;
    },
    parsedBlocks() {
      if (!this.page.blocks) return [];
      
      try {
        return JSON.parse(this.page.blocks);
      } catch (e) {
        console.error('Error parsing blocks:', e);
        return [];
      }
    }
  }
}
</script>

<style scoped>
.page-content {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  margin-bottom: 30px;
  color: var(--primary-color, var(--k-accent));
}
</style> 