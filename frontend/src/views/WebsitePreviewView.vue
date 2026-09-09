<template>
  <div class="website-preview">
    <div class="preview-header">
      <div class="header-title">
        <h1>{{ website.site_name || $t('website.previewTitle') }}</h1>
        <span class="preview-label">{{ $t('website.preview') }}</span>
      </div>
      <div class="header-actions">
        <button @click="goBack" class="back-btn">
          <i class="fas fa-arrow-left"></i> {{ $t('website.backToManager') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner">
        <i class="fas fa-circle-notch fa-spin"></i>
      </div>
      <p>{{ $t('website.loadingData') }}</p>
    </div>

    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h2>{{ $t('website.loadError') }}</h2>
      <p>{{ error }}</p>
      <button @click="goBack" class="btn-icon back-btn">
        <i class="fas fa-arrow-left"></i> {{ $t('website.backToManager') }}
      </button>
    </div>

    <div v-else-if="website.maintenance_mode" class="website-container maintenance-mode" :style="websiteStyles">
      <div class="maintenance-content">
        <div v-if="website.logo" class="site-logo">
          <img :src="website.logo" alt="Logo" />
        </div>
        <h1>{{ website.site_name }}</h1>
        <div class="maintenance-icon">
          <i class="fas fa-tools"></i>
        </div>
        <h2>{{ $t('website.maintenance') }}</h2>
        <p>{{ website.maintenance_message || $t('website.maintenanceDefault') }}</p>
      </div>
      <footer class="site-footer">
        <div class="footer-bottom">
          <p>{{ website.footer_text || '© ' + new Date().getFullYear() + ' ' + website.site_name }}</p>
        </div>
      </footer>
    </div>

    <!-- Use actual template components for rendering -->
    <div v-else class="website-container">
      <!-- OnePager Template -->
      <OnePagerTemplate
        v-if="isOnePager"
        :website="website"
        :sections="sections"
        :news="news"
        :preview-mode="true"
        :get-media-url="getMediaUrl"
      />

      <!-- Default Template -->
      <DefaultTemplate
        v-else
        :website="website"
        :pages="pages"
        :posts="posts"
        :categories="categories"
        :sections="sections"
        :news="news"
        :preview-mode="true"
        :get-media-url="getMediaUrl"
      />
    </div>
    
    <div class="preview-overlay">
      <div class="overlay-message">
        <i class="fas fa-eye"></i> {{ $t('website.previewMode') }}
      </div>
    </div>
  </div>
</template>

<script>
import { apiClientAuth } from '@/api';
import i18n from '@/plugins/i18n';
import { useI18n } from 'vue-i18n';
import OnePagerTemplate from '@/components/website/templates/OnePagerTemplate.vue';
import DefaultTemplate from '@/components/website/templates/DefaultTemplate.vue';

export default {
  name: 'WebsitePreviewView',
  components: {
    OnePagerTemplate,
    DefaultTemplate
  },
  setup() {
    const { t } = useI18n();
    return { t };
  },
  data() {
    return {
      website: {
        id: null,
        site_name: 'Beispiel-Website',
        site_slogan: 'Eine professionelle Webpräsenz',
        site_description: 'Dies ist eine Beispiel-Website für die Vorschaufunktion.',
        primary_color: 'var(--k-accent)',
        secondary_color: '#1e3a8a',
        background_color: '#ffffff',
        contact_email: 'kontakt@beispiel.de',
        contact_phone: '+49 123 456789',
        footer_text: '© ' + new Date().getFullYear() + ' Beispiel-Website',
        is_active: true,
        layout_template: 'default'
      },
      loading: true,
      error: null,
      sections: [],
      pages: [],
      posts: [],
      categories: [],
      news: []
    };
  },
  computed: {
    websiteStyles() {
      return {
        '--primary-color': this.website.primary_color || 'var(--k-accent)',
        '--secondary-color': this.website.secondary_color || '#1e3a8a',
        '--background-color': this.website.background_color || '#ffffff',
        'background-color': this.website.background_color || '#ffffff',
        'color': this.isDarkColor(this.website.background_color) ? '#ffffff' : '#333333'
      };
    },
    isOnePager() {
      return this.website.layout_template === 'onepager';
    }
  },
  async mounted() {
    try {
      // Lade Website-Daten anhand der ID aus der URL
      const websiteId = this.$route.params.id;
      if (!websiteId) {
        throw new Error('Keine Website-ID angegeben');
      }

      this.loading = true;
      const response = await apiClientAuth.get(`/company/website/?action=getWebsiteDetails&websiteId=${websiteId}`);

      if (!response.data.success) {
        throw new Error(response.data.error || 'Fehler beim Laden der Website');
      }

      // Fix: The response structure is response.data.data.website
      this.website = response.data.data?.website || response.data.website;

      // Convert is_active and maintenance_mode to boolean
      this.website.is_active = Boolean(this.website.is_active);
      this.website.maintenance_mode = Boolean(this.website.maintenance_mode);

      console.log('Website loaded:', this.website);
      console.log('Template type:', this.website.layout_template);

      // Load sections (for both templates)
      try {
        const sectionsResponse = await apiClientAuth.get(
          `/company/website/?action=getSections&websiteId=${websiteId}`
        );

        if (sectionsResponse.data?.success) {
          this.sections = (sectionsResponse.data.sections || []).map(section => {
            // Parse settings JSON string
            if (section.settings && typeof section.settings === 'string') {
              try {
                section.settings = JSON.parse(section.settings);
              } catch (e) {
                console.error('Error parsing section settings:', e);
                section.settings = {};
              }
            }
            return section;
          });
          console.log('Sections loaded:', this.sections);
        }
      } catch (error) {
        console.error('Error loading sections:', error);
        this.sections = [];
      }

      // Load pages (for Default template)
      if (this.website.layout_template !== 'onepager') {
        try {
          const pagesResponse = await apiClientAuth.get(
            `/company/website/?action=getPages&websiteId=${websiteId}`
          );

          if (pagesResponse.data?.success) {
            this.pages = pagesResponse.data.pages || [];
            console.log('Pages loaded:', this.pages);
          }
        } catch (error) {
          console.error('Error loading pages:', error);
          this.pages = [];
        }

        // Load posts (for Default template)
        try {
          const postsResponse = await apiClientAuth.get(
            `/company/website/?action=getPosts&websiteId=${websiteId}`
          );

          if (postsResponse.data?.success) {
            this.posts = postsResponse.data.posts || [];
            console.log('Posts loaded:', this.posts);
          }
        } catch (error) {
          console.error('Error loading posts:', error);
          this.posts = [];
        }

        // Load categories (for Default template)
        try {
          const categoriesResponse = await apiClientAuth.get(
            `/company/website/?action=getCategories&websiteId=${websiteId}`
          );

          if (categoriesResponse.data?.success) {
            this.categories = categoriesResponse.data.categories || [];
            console.log('Categories loaded:', this.categories);
          }
        } catch (error) {
          console.error('Error loading categories:', error);
          this.categories = [];
        }
      }

      this.loading = false;
    } catch (error) {
      console.error('Fehler beim Laden der Website-Vorschau:', error);
      this.error = error.message || 'Unbekannter Fehler beim Laden der Website';
      this.loading = false;
    }
  },
  methods: {
    goBack() {
      this.$router.push('/company/website');
    },
    getMediaUrl(fileName) {
      if (!fileName) return '';
      // If it's already a full URL, return it
      if (fileName.startsWith('http://') || fileName.startsWith('https://')) {
        return fileName;
      }
      // Otherwise, construct the path to the uploads folder
      return `/uploads/company/website/${this.website.id}/${fileName}`;
    },
    isDarkColor(color) {
      // Einfache Funktion zur Bestimmung, ob eine Farbe dunkel ist
      if (!color) return false;

      // Farbe in RGB-Werte umwandeln
      let r, g, b;

      if (color.startsWith('#')) {
        color = color.substring(1);
        if (color.length === 3) {
          r = parseInt(color[0] + color[0], 16);
          g = parseInt(color[1] + color[1], 16);
          b = parseInt(color[2] + color[2], 16);
        } else {
          r = parseInt(color.substring(0, 2), 16);
          g = parseInt(color.substring(2, 4), 16);
          b = parseInt(color.substring(4, 6), 16);
        }
      } else if (color.startsWith('rgb')) {
        const match = color.match(/(\d+),\s*(\d+),\s*(\d+)/);
        if (match) {
          r = parseInt(match[1]);
          g = parseInt(match[2]);
          b = parseInt(match[3]);
        } else {
          return false;
        }
      } else {
        return false;
      }

      // Berechne Helligkeit nach ITU-R BT.709
      const brightness = (r * 0.2126 + g * 0.7152 + b * 0.0722) / 255;

      // Wenn die Helligkeit unter 0.5 liegt, ist die Farbe dunkel
      return brightness < 0.5;
    }
  }
};
</script>
<style scoped>
.website-preview {
  position: relative;
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f0f2f5;
}

.preview-header {
  background-color: #3b5998;
  color: var(--k-ink);
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 2000;
}

.header-title {
  display: flex;
  align-items: center;
}

.header-title h1 {
  font-size: 1.2rem;
  margin: 0;
  margin-right: 10px;
}

.preview-label {
  background-color: var(--k-row-hover);
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: bold;
}

.back-btn {
  background-color: rgba(0, 0, 0, 0.2);
  color: var(--k-ink);
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
}

.back-btn i {
  margin-right: 5px;
}

.back-btn:hover {
  background-color: rgba(0, 0, 0, 0.3);
}

.loading-container, .error-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.spinner {
  font-size: 3rem;
  margin-bottom: 20px;
  color: #3b5998;
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 20px;
  color: #dc3545;
}

.website-container {
  flex: 1;
  overflow: auto;
  background-color: var(--background-color, #ffffff);
  color: #333;
}

/* Website Template Styles */
.site-header {
  background-color: var(--primary-color, var(--k-accent));
  color: var(--k-ink);
  padding: 20px;
  text-align: center;
}

.site-title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 5px;
}

.site-slogan {
  font-size: 1.2rem;
  margin-bottom: 20px;
  opacity: 0.8;
}

.site-nav ul {
  display: flex;
  justify-content: center;
  list-style: none;
  padding: 0;
  margin: 0;
}

.site-nav li {
  margin: 0 10px;
  position: relative;
  padding-bottom: 15px;
}

.site-nav a {
  color: var(--k-ink);
  text-decoration: none;
  padding: 5px 10px;
  border-radius: 4px;
  transition: background-color 0.3s;
  display: inline-block;
}

.site-nav a.active {
  background-color: var(--k-ink-faint);
  font-weight: bold;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.site-nav a:hover {
  background-color: var(--k-row-hover);
  transform: translateY(-2px);
  transition: all 0.2s ease;
}

/* Submenu styling */
.site-nav .submenu {
  position: absolute;
  top: calc(100% - 10px);
  left: 0;
  background-color: var(--primary-color);
  min-width: 180px;
  border-radius: 4px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  display: none;
  flex-direction: column;
  z-index: 100;
  padding: 5px 0;
  transition: opacity 0.3s ease;
  opacity: 0;
  pointer-events: none;
}

.site-nav li:hover .submenu {
  display: flex;
  opacity: 1;
  pointer-events: all;
}

/* Add padding above the dropdown to prevent gap */
.site-nav .submenu::before {
  content: '';
  position: absolute;
  top: -15px;
  left: 0;
  width: 100%;
  height: 15px;
  background: transparent;
}

.site-nav .submenu li {
  margin: 0;
  width: 100%;
  padding-bottom: 0; /* Reset padding for submenu items */
}

.site-nav .submenu a {
  padding: 8px 15px;
  width: 100%;
  border-radius: 0;
}

.site-nav .submenu a:hover {
  background-color: var(--k-row-hover);
}

.site-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.hero-section {
  text-align: center;
  padding: 50px 20px;
  background-color: var(--secondary-color, #1e3a8a);
  color: var(--k-ink);
  margin-bottom: 40px;
  border-radius: 8px;
}

.hero-section h1 {
  font-size: 2.5rem;
  margin-bottom: 20px;
}

.hero-section p {
  font-size: 1.2rem;
  margin-bottom: 30px;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
}

.cta-button {
  background-color: var(--primary-color, var(--k-accent));
  color: var(--k-ink);
  border: none;
  padding: 12px 30px;
  border-radius: 4px;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background-color 0.3s;
}

.cta-button:hover {
  opacity: 0.9;
}

.features-section {
  padding: 40px 0;
}

.features-section h2 {
  text-align: center;
  margin-bottom: 40px;
  font-size: 2rem;
  color: var(--primary-color, var(--k-accent));
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
}

.feature-card {
  background-color: var(--k-ink);
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
  transition: transform 0.3s;
}

.feature-card:hover {
  transform: translateY(-5px);
}

.feature-card i {
  font-size: 2.5rem;
  margin-bottom: 20px;
  color: var(--primary-color, var(--k-accent));
}

.feature-card h3 {
  font-size: 1.5rem;
  margin-bottom: 15px;
}

.site-footer {
  background-color: var(--secondary-color, #1e3a8a);
  color: var(--k-ink);
  padding: 20px;
  text-align: center;
  margin-top: auto;
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 40px;
  margin-bottom: 20px;
}

.footer-section h3 {
  font-size: 1.3rem;
  margin-bottom: 15px;
  position: relative;
}

.footer-section h3:after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 50px;
  height: 2px;
  background-color: var(--primary-color, var(--k-accent));
}

.footer-section p {
  margin-bottom: 10px;
}

.footer-section i {
  margin-right: 10px;
}

.footer-section ul {
  list-style: none;
  padding: 0;
}

.footer-section li {
  margin-bottom: 8px;
}

.footer-section a {
  color: var(--k-ink);
  text-decoration: none;
  opacity: 0.8;
  transition: opacity 0.3s;
}

.footer-section a:hover {
  opacity: 1;
}

.footer-bottom {
  border-top: 1px solid var(--k-line);
  padding-top: 20px;
  text-align: center;
  opacity: 0.7;
  font-size: 0.9rem;
}

/* Overlay für den Vorschaumodus */
.preview-overlay {
  position: fixed;
  top: 50px;
  right: 20px;
  z-index: 1000;
}

.overlay-message {
  background-color: rgba(0, 0, 0, 0.7);
  color: var(--k-ink);
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.overlay-message i {
  margin-right: 8px;
}

/* Dynamic page content styles */
.page-content {
  padding: 30px 0;
}

.page-content h1 {
  font-size: 2.2rem;
  color: var(--primary-color);
  margin-bottom: 20px;
  text-align: center;
}

.page-content h2 {
  font-size: 1.8rem;
  color: var(--secondary-color);
  margin: 20px 0 15px;
}

.page-content h3 {
  font-size: 1.5rem;
  margin: 18px 0 12px;
}

.page-content p {
  margin-bottom: 15px;
  line-height: 1.6;
}

.page-content ul, 
.page-content ol {
  margin: 15px 0;
  padding-left: 30px;
}

.page-content li {
  margin-bottom: 8px;
}

.page-content img {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  margin: 15px 0;
}

.page-content a {
  color: var(--primary-color);
  text-decoration: none;
}

.page-content a:hover {
  text-decoration: underline;
}

/* Blog Styles */
.blog-overview {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 20px;
}

.posts-list {
  margin-top: 30px;
}

.post-item {
  background-color: var(--k-ink);
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 30px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer;
}

.post-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
}

.post-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.post-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.post-item:hover .post-image img {
  transform: scale(1.05);
}

.post-item h2 {
  padding: 20px 20px 10px;
  margin: 0;
  color: var(--primary-color, var(--k-accent));
    font-size: 1.5rem;
  }
  
.post-meta {
  padding: 0 20px 10px;
  color: #666;
  font-size: 0.9rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
}

.post-categories {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 5px;
}

.category-tag {
  background-color: var(--primary-color, var(--k-accent));
  color: var(--k-ink);
  padding: 3px 8px;
  border-radius: 15px;
  font-size: 0.8rem;
  display: inline-block;
}

.post-excerpt {
  padding: 0 20px 20px;
  color: #444;
  line-height: 1.6;
}

.read-more {
  display: inline-block;
  margin: 0 20px 20px;
  color: var(--primary-color, var(--k-accent));
  text-decoration: none;
  font-weight: 500;
  position: relative;
  }
  
.read-more::after {
  content: '→';
  margin-left: 5px;
  transition: transform 0.3s ease;
  display: inline-block;
}

.read-more:hover::after {
  transform: translateX(5px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .site-nav ul {
    flex-direction: column;
    align-items: center;
  }
  
  .site-nav li {
    margin: 5px 0;
  }
  
  .post-meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .post-categories {
    margin-top: 10px;
  }
}

.no-navigation-message {
  padding: 10px;
  color: var(--k-ink-muted);
  text-align: center;
  font-style: italic;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  margin: 5px 0;
}

.no-posts-message,
.error-message {
  padding: 20px;
  text-align: center;
  background-color: var(--k-sunken);
  border-radius: 8px;
  margin: 20px 0;
  }
  
.error-message {
  background-color: #fff3f3;
  color: #d32f2f;
}

/* Make sure the loading indicator is visible */
.isPageLoading {
  opacity: 0.6;
  pointer-events: none;
}

.maintenance-mode {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 100%;
  text-align: center;
}

.maintenance-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  }

.maintenance-icon {
  font-size: 4rem;
  margin: 2rem 0;
  color: var(--primary-color);
}

/* ======================================== */
/* OnePager Template Styles */
/* ======================================== */

.onepager-main {
  padding: 0;
  max-width: 100%;
}

.onepager-sections {
  width: 100%;
}

.onepager-section {
  min-height: 400px;
  padding: 60px 20px;
  scroll-margin-top: 70px; /* For smooth scrolling */
}

/* Hero Section Styles */
.section-hero {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
  color: var(--k-ink);
  text-align: center;
  min-height: 500px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

.hero-content {
  max-width: 900px;
  margin: 0 auto;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 40px;
  border-radius: 12px;
}

.section-hero h1 {
  font-size: 3rem;
  margin-bottom: 20px;
  font-weight: bold;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
  font-size: 1.5rem;
  margin-bottom: 30px;
  opacity: 0.95;
}

.hero-button {
  display: inline-block;
  background-color: var(--k-ink);
  color: var(--primary-color);
  padding: 15px 40px;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  font-size: 1.1rem;
  margin-top: 30px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.hero-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* About Section Styles */
.section-about {
  background-color: var(--k-sunken);
  text-align: center;
}

.section-about h2 {
  font-size: 2.5rem;
  color: var(--primary-color);
  margin-bottom: 15px;
}

.section-subtitle {
  font-size: 1.3rem;
  color: #666;
  margin-bottom: 30px;
  font-style: italic;
}

.section-content {
  max-width: 900px;
  margin: 0 auto;
  line-height: 1.8;
  font-size: 1.1rem;
  color: #444;
}

/* Services Section Styles */
.section-services {
  background-color: var(--k-ink);
  text-align: center;
}

.section-services h2 {
  font-size: 2.5rem;
  color: var(--primary-color);
  margin-bottom: 15px;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 40px auto 0;
}

.service-card {
  background-color: var(--k-sunken);
  padding: 40px 30px;
  border-radius: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.service-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.service-card i {
  font-size: 3.5rem;
  color: var(--primary-color);
  margin-bottom: 20px;
  display: block;
}

.service-card h3 {
  font-size: 1.5rem;
  color: var(--secondary-color);
  margin-bottom: 15px;
}

.service-card p {
  font-size: 1rem;
  color: #666;
  line-height: 1.6;
}

/* Contact Section Styles */
.section-contact {
  background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
  color: var(--k-ink);
  text-align: center;
}

.section-contact h2 {
  font-size: 2.5rem;
  margin-bottom: 15px;
  color: var(--k-ink);
}

.section-contact .section-subtitle {
  color: var(--k-ink);
}

.section-contact .section-content {
  color: var(--k-ink);
  margin-bottom: 30px;
}

.contact-info {
  margin-top: 30px;
  font-size: 1.2rem;
}

.contact-info p {
  margin: 15px 0;
}

.contact-info i {
  margin-right: 10px;
  font-size: 1.3rem;
}

/* Generic Section Styles */
.section-generic {
  background-color: var(--k-ink);
  text-align: center;
}

.section-generic h2 {
  font-size: 2.5rem;
  color: var(--primary-color);
  margin-bottom: 15px;
}

/* Alternating background colors for sections */
.onepager-section:nth-child(even) .section-generic,
.onepager-section:nth-child(even) .section-about {
  background-color: var(--k-ink);
}

.onepager-section:nth-child(odd) .section-generic,
.onepager-section:nth-child(odd) .section-about {
  background-color: var(--k-sunken);
}

/* No sections message */
.no-sections-message {
  padding: 60px 20px;
  text-align: center;
  background-color: var(--k-sunken);
}

/* Responsive Design */
@media (max-width: 768px) {
  .section-hero h1 {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1.2rem;
  }

  .section-about h2,
  .section-services h2,
  .section-contact h2,
  .section-generic h2 {
    font-size: 1.8rem;
  }

  .services-grid {
    grid-template-columns: 1fr;
  }

  .onepager-section {
    padding: 40px 15px;
  }
}
</style> 