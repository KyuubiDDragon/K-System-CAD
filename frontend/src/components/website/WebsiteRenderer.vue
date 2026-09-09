<template>
  <div class="website-renderer">
    <!-- Block-based content renderer -->
    <div v-if="isBlockContent" class="block-content">
      <div v-for="block in blocks" :key="block.id" class="block-wrapper">
        <!-- Text Block -->
        <div v-if="block.type === 'text'" class="text-block">
          <div v-html="block.content.text || ''"></div>
        </div>

        <!-- Team Block -->
        <div v-else-if="block.type === 'team'" class="team-block">
          <h2 v-if="block.content.title">{{ block.content.title }}</h2>
          <p v-if="block.content.description" class="team-description">
            {{ block.content.description }}
          </p>

          <div class="team-members">
            <div v-for="(member, index) in block.content.members" :key="index" class="team-member">
              <div class="member-image">
                <img v-if="member.image" :src="getMediaUrl(member.image)" :alt="member.name" />
                <div v-else class="placeholder-image">
                  {{ getInitials(member.name) }}
                </div>
              </div>
              <h3>{{ member.name }}</h3>
              <p class="member-position">{{ member.position }}</p>
              <p class="member-bio">{{ member.bio }}</p>
            </div>
          </div>
        </div>

        <!-- Columns Block -->
        <div v-else-if="block.type === 'columns'" class="columns-block">
          <h2 v-if="block.content.title">{{ block.content.title }}</h2>
          <div class="columns-container" :class="`columns-${block.content.columnCount}`">
            <div v-for="(column, index) in block.content.columns" :key="index" class="column">
              <h3 v-if="column.title">{{ column.title }}</h3>
              <div class="column-content" v-html="column.text"></div>
            </div>
          </div>
        </div>

        <!-- Testimonials Block -->
        <div v-else-if="block.type === 'testimonials'" class="testimonials-block">
          <h2 v-if="block.content.title">{{ block.content.title }}</h2>

          <div class="testimonials-container">
            <div v-for="(item, index) in block.content.items" :key="index" class="testimonial">
              <div class="testimonial-quote">
                <blockquote>{{ item.quote }}</blockquote>
              </div>
              <div class="testimonial-author">
                <div class="author-image">
                  <img v-if="item.image" :src="getMediaUrl(item.image)" :alt="item.name" />
                  <div v-else class="placeholder-image">
                    {{ getInitials(item.name) }}
                  </div>
                </div>
                <div class="author-info">
                  <h3>{{ item.name }}</h3>
                  <p class="author-position">{{ item.position }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Regular HTML content -->
    <div v-else-if="content" class="dynamic-content" v-html="content"></div>
    
    <!-- Fallback if no content is provided -->
    <div v-else class="no-content">
      <p>{{ $t('desktop.noContent') }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: "WebsiteRenderer",
  props: {
    content: {
      type: String,
      default: ""
    },
    blocks: {
      type: Array,
      default: () => []
    },
    isBlockContent: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    getMediaUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      return `/uploads/${path}`;
    },
    getInitials(name) {
      if (!name) return '';
      return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
    }
  }
}
</script>

<style scoped>
.website-renderer {
  width: 100%;
}

/* Block Content Styling */
.block-wrapper {
  margin-bottom: 40px;
}

.text-block {
  line-height: 1.6;
}

.team-block {
  text-align: center;
}

.team-description {
  max-width: 700px;
  margin: 0 auto 30px;
}

.team-members {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 30px;
}

.team-member {
  width: 280px;
  padding: 20px;
  border-radius: 8px;
  background-color: var(--k-sunken);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.member-image {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  margin: 0 auto 15px;
  overflow: hidden;
  background-color: var(--primary-color, var(--k-accent));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--k-ink);
  font-size: 2.5rem;
}

.member-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.member-position {
  color: #666;
  font-style: italic;
  margin-bottom: 15px;
}

/* Columns Block */
.columns-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
}

.columns-2 .column {
  flex: 0 0 calc(50% - 15px);
}

.columns-3 .column {
  flex: 0 0 calc(33.333% - 20px);
}

.columns-4 .column {
  flex: 0 0 calc(25% - 22.5px);
}

.column h3 {
  color: var(--primary-color, var(--k-accent));
  margin-bottom: 15px;
}

/* Testimonials Block */
.testimonials-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  justify-content: center;
}

.testimonial {
  flex: 0 0 calc(33.333% - 20px);
  min-width: 280px;
  padding: 20px;
  border-radius: 8px;
  background-color: var(--k-sunken);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.testimonial-quote {
  font-style: italic;
  margin-bottom: 20px;
  position: relative;
  padding: 0 15px;
}

.testimonial-quote blockquote {
  margin: 0;
  padding: 0;
}

.testimonial-author {
  display: flex;
  align-items: center;
}

.author-image {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 15px;
  overflow: hidden;
  background-color: var(--primary-color, var(--k-accent));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--k-ink);
}

.author-info h3 {
  margin: 0;
  font-size: 1rem;
}

.author-position {
  margin: 0;
  font-size: 0.9rem;
  color: #666;
}

.no-content {
  padding: 20px;
  text-align: center;
  color: #666;
  font-style: italic;
}

/* Responsive Styles */
@media (max-width: 768px) {
  .columns-container {
    flex-direction: column;
  }
  
  .columns-2 .column,
  .columns-3 .column,
  .columns-4 .column {
    flex: 0 0 100%;
  }
  
  .testimonial {
    flex: 0 0 100%;
  }
}
</style> 