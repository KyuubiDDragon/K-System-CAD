<template>
    <div class="onepager-template" :class="{ 'preview-mode': previewMode }" :style="websiteStyles">
        <!-- Fixed Navigation Header -->
        <header class="onepager-header" :class="{ 'scrolled': isScrolled, 'preview-header': previewMode }">
            <div class="header-container">
                <div class="site-branding">
                    <img
                        v-if="website.logo"
                        :src="getMediaUrl(website.logo)"
                        :alt="website.site_name"
                        class="site-logo"
                    />
                    <h1 class="site-title">{{ website.site_name }}</h1>
                </div>

                <!-- Scroll-Spy Navigation -->
                <nav class="onepager-nav">
                    <ul class="nav-menu">
                        <li
                            v-for="section in activeSections"
                            :key="section.id"
                            :class="{ active: activeSection === section.id }"
                        >
                            <a
                                :href="`#section-${section.id}`"
                                @click.prevent="scrollToSection(section.id)"
                            >
                                {{ section.title || getSectionTitle(section.section_type) }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" @click="mobileMenuOpen = !mobileMenuOpen">
                    <i class="mdi" :class="mobileMenuOpen ? 'mdi-close' : 'mdi-menu'"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
                <ul>
                    <li
                        v-for="section in activeSections"
                        :key="section.id"
                        @click="mobileMenuOpen = false"
                    >
                        <a
                            :href="`#section-${section.id}`"
                            @click.prevent="scrollToSection(section.id)"
                        >
                            {{ section.title || getSectionTitle(section.section_type) }}
                        </a>
                    </li>
                </ul>
            </div>
        </header>

        <!-- Sections Container -->
        <main class="sections-container">
            <!-- Render each section -->
            <section
                v-for="section in activeSections"
                :key="section.id"
                :id="`section-${section.id}`"
                :class="['onepager-section', `section-${section.section_type}`]"
                :style="getSectionStyle(section)"
            >
                <!-- Section Divider - Top -->
                <SectionDivider
                    v-if="getDividerSettings(section).shape !== 'none' &&
                          (getDividerSettings(section).position === 'top' || getDividerSettings(section).position === 'both')"
                    :shape="getDividerSettings(section).shape"
                    position="top"
                    :color="getDividerColor(section, 'top')"
                    :flip="getDividerSettings(section).flip"
                    :height="getDividerSettings(section).height"
                />

                <!-- Content Wrapper (flex container for centering) -->
                <div class="section-content-wrapper">
                    <!-- Hero Section -->
                    <div v-if="section.section_type === 'hero'" class="hero-content">
                    <div class="hero-overlay"></div>
                    <div class="hero-inner">
                        <h1 class="hero-title">{{ section.title || website.site_name }}</h1>
                        <p v-if="section.subtitle" class="hero-subtitle">{{ section.subtitle }}</p>
                        <div v-if="section.content" class="hero-description" v-html="section.content"></div>
                        <div v-if="website.cta_text" class="hero-cta">
                            <button class="cta-button" @click="handleCTA">
                                {{ website.cta_text }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- About Section -->
                <div v-else-if="section.section_type === 'about'" class="section-content about-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="section-text" v-html="section.content"></div>
                    </div>
                </div>

                <!-- Services Section -->
                <div v-else-if="section.section_type === 'services'" class="section-content services-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="services-grid">
                            <div
                                v-for="(service, index) in parseServices(section)"
                                :key="index"
                                class="service-card"
                            >
                                <div class="service-icon">
                                    <i :class="service.icon || 'mdi mdi-check-circle'"></i>
                                </div>
                                <h3>{{ service.title }}</h3>
                                <p>{{ service.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!--
                    Neuigkeiten.

                    Wird nicht angelegt, sondern erscheint, sobald es einen
                    veroeffentlichten Beitrag gibt - siehe activeSections.
                -->
                <div v-else-if="section.section_type === 'posts'" class="section-content posts-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <div class="posts-grid">
                            <article
                                v-for="beitrag in sichtbareBeitraege"
                                :key="beitrag.id"
                                class="post-card"
                                :class="{ offen: offenerBeitrag === beitrag.id }"
                            >
                                <div
                                    v-if="beitrag.featured_image"
                                    class="post-card-bild"
                                    :style="{ backgroundImage: `url(${getMediaUrl(beitrag.featured_image)})` }"
                                ></div>

                                <div class="post-card-inhalt">
                                    <time v-if="datumVon(beitrag)" class="post-card-datum">
                                        {{ datumVon(beitrag) }}
                                    </time>
                                    <h3 class="post-card-titel">{{ beitrag.title }}</h3>

                                    <div v-if="offenerBeitrag === beitrag.id" class="post-card-text" v-html="beitrag.content"></div>
                                    <p v-else class="post-card-anriss">{{ anrissVon(beitrag) }}</p>

                                    <button type="button" class="post-card-mehr" @click="beitragUmschalten(beitrag.id)">
                                        {{ offenerBeitrag === beitrag.id ? 'Weniger anzeigen' : 'Weiterlesen' }}
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Team Section -->
                <div v-else-if="section.section_type === 'team'" class="section-content team-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="team-grid">
                            <div
                                v-for="(member, index) in parseTeam(section)"
                                :key="index"
                                class="team-member"
                            >
                                <div class="member-image">
                                    <img
                                        v-if="member.image"
                                        :src="getMediaUrl(member.image)"
                                        :alt="member.name"
                                    />
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
                </div>

                <!-- Contact Section -->
                <div v-else-if="section.section_type === 'contact'" class="section-content contact-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>

                        <div class="contact-info">
                            <div v-if="website.contact_email" class="contact-item">
                                <i class="mdi mdi-email"></i>
                                <a :href="`mailto:${website.contact_email}`">{{ website.contact_email }}</a>
                            </div>
                            <div v-if="website.contact_phone" class="contact-item">
                                <i class="mdi mdi-phone"></i>
                                <a :href="`tel:${website.contact_phone}`">{{ website.contact_phone }}</a>
                            </div>
                        </div>

                        <!-- Contact Form (if enabled) -->
                        <form v-if="website.show_contact_form" class="contact-form" @submit.prevent="submitContact">
                            <div class="form-group">
                                <input v-model="contactForm.name" type="text" placeholder="Name" required />
                            </div>
                            <div class="form-group">
                                <input v-model="contactForm.email" type="email" placeholder="E-Mail" required />
                            </div>
                            <div class="form-group">
                                <textarea v-model="contactForm.message" placeholder="Nachricht" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="submit-button">Senden</button>
                        </form>
                    </div>
                </div>

                <!-- Gallery Section -->
                <div v-else-if="section.section_type === 'gallery'" class="section-content gallery-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="gallery-grid" :class="`gallery-columns-${parseGallery(section).columns || 3}`">
                            <div v-for="(image, index) in parseGallery(section).images" :key="index" class="gallery-item">
                                <img :src="getMediaUrl(image.url)" :alt="image.caption || ''" />
                                <div v-if="image.caption" class="gallery-caption">{{ image.caption }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Section -->
                <div v-else-if="section.section_type === 'video'" class="section-content video-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="video-container">
                            <iframe v-if="parseVideo(section)" :src="parseVideo(section)" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div v-else-if="section.section_type === 'faq'" class="section-content faq-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="faq-items">
                            <div v-for="(item, index) in parseFAQ(section)" :key="index" class="faq-item" @click="toggleFAQ(index)">
                                <div class="faq-question">
                                    <h3>{{ item.question }}</h3>
                                    <i class="mdi" :class="openFAQs.includes(index) ? 'mdi-minus' : 'mdi-plus'"></i>
                                </div>
                                <div v-if="openFAQs.includes(index)" class="faq-answer" v-html="item.answer"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div v-else-if="section.section_type === 'statistics'" class="section-content statistics-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="statistics-grid" :class="`stats-columns-${parseStatistics(section).columns || 4}`">
                            <div v-for="(stat, index) in parseStatistics(section).items" :key="index" class="statistic-item">
                                <i v-if="stat.icon" :class="stat.icon" class="stat-icon"></i>
                                <div class="stat-value">{{ stat.value }}</div>
                                <div class="stat-label">{{ stat.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div v-else-if="section.section_type === 'features'" class="section-content features-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="features-grid" :class="`features-columns-${parseFeatures(section).columns || 3}`">
                            <div v-for="(feature, index) in parseFeatures(section).items" :key="index" class="feature-box">
                                <i v-if="feature.icon" :class="feature.icon" class="feature-icon"></i>
                                <h3>{{ feature.title }}</h3>
                                <p>{{ feature.text }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div v-else-if="section.section_type === 'cta'" class="section-content cta-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <div v-if="section.content" v-html="section.content" class="cta-text"></div>
                        <button v-if="parseCTA(section).buttonText" class="cta-button" @click="handleCTA">
                            {{ parseCTA(section).buttonText }}
                        </button>
                    </div>
                </div>

                <!-- Pricing Section -->
                <div v-else-if="section.section_type === 'pricing'" class="section-content pricing-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="pricing-grid">
                            <div
                                v-for="(plan, index) in parsePricing(section)"
                                :key="index"
                                class="pricing-card"
                            >
                                <h3 class="plan-name">{{ plan.name }}</h3>
                                <div class="plan-price">
                                    <span class="price-currency">{{ plan.currency || '€' }}</span>
                                    <span class="price-amount">{{ plan.price }}</span>
                                    <span v-if="plan.period" class="price-period">{{ plan.period }}</span>
                                </div>
                                <ul v-if="plan.features && plan.features.length" class="plan-features">
                                    <li v-for="(feature, fIndex) in plan.features" :key="fIndex">
                                        <i class="mdi mdi-check-circle"></i>
                                        <span>{{ feature }}</span>
                                    </li>
                                </ul>
                                <button v-if="plan.buttonText" class="plan-button">{{ plan.buttonText }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testimonials Section -->
                <div v-else-if="section.section_type === 'testimonials'" class="section-content testimonials-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="testimonials-grid">
                            <div
                                v-for="(testimonial, index) in parseTestimonials(section)"
                                :key="index"
                                class="testimonial-card"
                            >
                                <div class="testimonial-quote">
                                    <i class="mdi mdi-format-quote-open quote-icon"></i>
                                    <p class="testimonial-text">{{ testimonial.text }}</p>
                                </div>
                                <div class="testimonial-author">
                                    <div v-if="testimonial.image" class="author-image">
                                        <img :src="getMediaUrl(testimonial.image)" :alt="testimonial.name" />
                                    </div>
                                    <div v-else class="author-image placeholder">
                                        {{ getInitials(testimonial.name) }}
                                    </div>
                                    <div class="author-info">
                                        <h4 class="author-name">{{ testimonial.name }}</h4>
                                        <p class="author-position">{{ testimonial.position }}</p>
                                        <p v-if="testimonial.company" class="author-company">{{ testimonial.company }}</p>
                                    </div>
                                </div>
                                <div v-if="testimonial.rating" class="testimonial-rating">
                                    <i v-for="n in testimonial.rating" :key="n" class="mdi mdi-star"></i>
                                    <i v-for="n in (5 - testimonial.rating)" :key="`empty-${n}`" class="mdi mdi-star-outline"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Portfolio Section -->
                <div v-else-if="section.section_type === 'portfolio'" class="section-content portfolio-content">
                    <div class="container">
                        <h2 class="section-title">{{ section.title }}</h2>
                        <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                        <div class="portfolio-grid" :class="`portfolio-columns-${parsePortfolio(section).columns || 3}`">
                            <div
                                v-for="(project, index) in parsePortfolio(section).items"
                                :key="index"
                                class="portfolio-item"
                            >
                                <div class="portfolio-image">
                                    <img
                                        v-if="project.image"
                                        :src="getMediaUrl(project.image)"
                                        :alt="project.title"
                                    />
                                    <div v-else class="portfolio-placeholder">
                                        <i class="mdi mdi-image"></i>
                                    </div>
                                    <div class="portfolio-overlay">
                                        <i class="mdi mdi-eye"></i>
                                    </div>
                                </div>
                                <div class="portfolio-info">
                                    <h3 class="portfolio-title">{{ project.title }}</h3>
                                    <p v-if="project.category" class="portfolio-category">{{ project.category }}</p>
                                    <p v-if="project.description" class="portfolio-description">{{ project.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Custom Section -->
                    <div v-else class="section-content custom-content">
                        <div class="container">
                            <h2 v-if="section.title" class="section-title">{{ section.title }}</h2>
                            <p v-if="section.subtitle" class="section-subtitle">{{ section.subtitle }}</p>
                            <div v-if="section.content" v-html="section.content"></div>
                        </div>
                    </div>
                </div>
                <!-- End Content Wrapper -->

                <!-- Section Divider - Bottom -->
                <SectionDivider
                    v-if="getDividerSettings(section).shape !== 'none' &&
                          (getDividerSettings(section).position === 'bottom' || getDividerSettings(section).position === 'both')"
                    :shape="getDividerSettings(section).shape"
                    position="bottom"
                    :color="getDividerColor(section, 'bottom')"
                    :flip="getDividerSettings(section).flip"
                    :height="getDividerSettings(section).height"
                />
            </section>
        </main>

        <!-- Footer -->
        <footer class="onepager-footer">
            <div class="container">
                <p>{{ website.footer_text || `© ${new Date().getFullYear()} ${website.site_name}` }}</p>
            </div>
        </footer>

        <!-- Scroll to Top Button -->
        <button
            v-if="isScrolled"
            class="scroll-to-top"
            @click="scrollToTop"
        >
            <i class="mdi mdi-arrow-up"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import SectionDivider from '@/components/website/SectionDivider.vue';

interface Props {
    website: any;
    sections?: any[];
    /*
       Beitraege wurden vom TemplateRouter laengst hierher gereicht, nur nahm
       die Vorlage sie nie entgegen. Stattdessen stand hier news - eine Prop,
       die nie ausgegeben wurde. Die ist mitsamt dem Reiter entfallen.
    */
    posts?: any[];
    previewMode?: boolean;
    getMediaUrl?: (fileName: string | null) => string;
}

const props = withDefaults(defineProps<Props>(), {
    sections: () => [],
    posts: () => [],
    previewMode: false,
    getMediaUrl: (fileName) => fileName || ''
});

// State
const activeSection = ref<number | null>(null);
const isScrolled = ref(false);
const mobileMenuOpen = ref(false);
const openFAQs = ref<number[]>([]);
const scrollContainer = ref<HTMLElement | null>(null);
const contactForm = ref({
    name: '',
    email: '',
    message: ''
});

// Preset Color Schemes (same as in WebsiteManager)
const presetColorSchemes: Record<string, any> = {
    // ===== WHITE MODE DESIGNS =====
    'Blue Ocean': {
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#ffffff',
        text: '#111827',
    },
    'Forest Green': {
        primary: '#10b981',
        secondary: '#34d399',
        accent: '#6ee7b7',
        background: '#ffffff',
        text: '#111827',
    },
    'Royal Purple': {
        primary: '#8b5cf6',
        secondary: '#a78bfa',
        accent: '#c4b5fd',
        background: '#ffffff',
        text: '#111827',
    },
    'Sunset Orange': {
        primary: '#f59e0b',
        secondary: '#fbbf24',
        accent: '#fcd34d',
        background: '#ffffff',
        text: '#111827',
    },
    'Rose Pink': {
        primary: '#ec4899',
        secondary: '#f472b6',
        accent: '#fbcfe8',
        background: '#ffffff',
        text: '#111827',
    },

    // ===== DARK MODE DESIGNS =====
    'Dark Blue': {
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#111827',
        text: '#f9fafb',
    },
    'Dark Forest': {
        primary: '#10b981',
        secondary: '#34d399',
        accent: '#6ee7b7',
        background: '#0f1419',
        text: '#f0fdf4',
    },
    'Dark Purple': {
        primary: '#a855f7',
        secondary: '#c084fc',
        accent: '#e9d5ff',
        background: '#1e1b2e',
        text: '#faf5ff',
    },
    'Dark Amber': {
        primary: '#f59e0b',
        secondary: '#fbbf24',
        accent: '#fde68a',
        background: '#1c1917',
        text: '#fffbeb',
    },
    'Dark Slate': {
        primary: '#64748b',
        secondary: '#94a3b8',
        accent: '#cbd5e1',
        background: '#0f172a',
        text: '#f1f5f9',
    },
};

// Computed
/** Nur was veroeffentlicht ist, und das Neueste zuerst. */
const sichtbareBeitraege = computed(() => {
    return [...props.posts]
        .filter((b) => b.is_published == 1 || b.status === 'published')
        .sort((a, b) => {
            const da = new Date(a.published_at || a.publish_date || a.created_at || 0).getTime();
            const db = new Date(b.published_at || b.publish_date || b.created_at || 0).getTime();
            return db - da;
        })
        .slice(0, HOECHSTZAHL_BEITRAEGE);
});

/** So viele Beitraege zeigt der Einseiter - mehr wuerde die eine Seite sprengen. */
const HOECHSTZAHL_BEITRAEGE = 6;

/*
   Die Abschnitte der Seite, und dazwischen die Neuigkeiten.

   Der Abschnitt wird nicht angelegt, sondern eingehaengt: sobald es einen
   veroeffentlichten Beitrag gibt, steht er da - ohne dass jemand vorher etwas
   einrichten muss. Verschwinden die Beitraege wieder, verschwindet auch der
   Abschnitt.

   Er kommt vor den Kontakt, weil der Kontakt eine Seite ueblicherweise
   abschliesst. Gibt es keinen Kontaktabschnitt, haengt er hinten an.

   Weil der Kopf sein Menue aus derselben Liste baut, erscheint der Menuepunkt
   von allein mit.
*/
const activeSections = computed(() => {
    const echte = props.sections.filter((s) => s.is_active !== 0);
    if (sichtbareBeitraege.value.length === 0) return echte;

    const neuigkeiten = {
        id: 'neuigkeiten',
        section_type: 'posts',
        title: 'Neuigkeiten',
        subtitle: '',
        is_active: 1,
    };

    const kontakt = echte.findIndex((s) => s.section_type === 'contact');
    if (kontakt === -1) return [...echte, neuigkeiten];
    return [...echte.slice(0, kontakt), neuigkeiten, ...echte.slice(kontakt)];
});

const websiteStyles = computed(() => {
    let colors: any = {};

    // If use_custom_colors is false, use the selected preset scheme
    if (props.website.use_custom_colors === false && props.website.selected_scheme) {
        colors = presetColorSchemes[props.website.selected_scheme] || presetColorSchemes['Blue Ocean'];
        console.log('Using preset scheme:', props.website.selected_scheme, colors);
    } else if (props.website.customColors) {
        // Use custom colors
        colors = props.website.customColors;
        console.log('Using custom colors:', colors);
    } else {
        // Fallback to website primary/secondary colors
        colors = {
            primary: props.website.primary_color || 'var(--k-accent)',
            secondary: props.website.secondary_color || 'var(--k-accent-hover)',
            accent: 'var(--k-accent)',
            background: props.website.background_color || '#ffffff',
            text: '#111827',
        };
        console.log('Using fallback colors:', colors);
    }

    return {
        '--primary-color': colors.primary,
        '--secondary-color': colors.secondary,
        '--accent-color': colors.accent,
        '--background-color': colors.background,
        '--text-color': colors.text,
    };
});

// Methods
function getSectionTitle(type: string): string {
    const titles: Record<string, string> = {
        hero: 'Start',
        about: 'Über uns',
        services: 'Leistungen',
        portfolio: 'Portfolio',
        team: 'Team',
        testimonials: 'Referenzen',
        contact: 'Kontakt',
        features: 'Features',
        pricing: 'Preise',
        cta: 'Jetzt starten',
        gallery: 'Galerie',
        video: 'Video',
        faq: 'FAQ',
        statistics: 'Statistiken'
    };
    return titles[type] || type.charAt(0).toUpperCase() + type.slice(1);
}

function getSectionStyle(section: any) {
    // Parse settings if it's a string
    let settings = section.settings || {};
    if (typeof settings === 'string') {
        try {
            settings = JSON.parse(settings);
        } catch (e) {
            console.error('Error parsing section settings:', e);
            settings = {};
        }
    }

    console.log(`🔍 [${section.section_type}] Settings:`, settings);

    const style: any = {};

    // Spacing & Layout
    if (settings.minHeight !== undefined && settings.minHeight !== null && settings.minHeight > 0) {
        style.minHeight = `${settings.minHeight}vh`;
    }

    const paddingTop = settings.paddingTop ?? 6;
    const paddingBottom = settings.paddingBottom ?? 6;
    style.paddingTop = `${paddingTop}rem`;
    style.paddingBottom = `${paddingBottom}rem`;

    // Content width as CSS variable
    const contentWidth = settings.contentWidth || 1200;
    style['--content-width'] = `${contentWidth}px`;

    // Text Alignment (add as CSS variable so it can be used in scoped styles)
    // Support BOTH text-align (for block) and justify-content (for flex)
    const titleAlignment = settings.titleAlignment || 'center';
    style['--title-alignment'] = titleAlignment;

    // Map alignment to flex justify-content (in case element has display: flex)
    const justifyMap: Record<string, string> = {
        'left': 'flex-start',
        'center': 'center',
        'right': 'flex-end'
    };
    style['--title-justify'] = justifyMap[titleAlignment] || 'center';

    console.log(`✏️ [${section.section_type}] titleAlignment SET TO:`, titleAlignment, '| justify:', justifyMap[titleAlignment]);

    // Background
    if (settings.backgroundImage) {
        style.backgroundImage = `url(${props.getMediaUrl(settings.backgroundImage)})`;
        style.backgroundSize = 'cover';
        style.backgroundPosition = 'center';
        style.backgroundRepeat = 'no-repeat';

        // Add overlay opacity as CSS variable
        const overlayOpacity = settings.overlayOpacity ?? 0.3;
        style['--overlay-opacity'] = overlayOpacity.toString();
    }

    if (settings.backgroundColor) {
        style.backgroundColor = settings.backgroundColor;
    }

    console.log(`📦 [${section.section_type}] Final style:`, style);
    return style;
}

function getOverlayOpacity(section: any): number {
    let settings = section.settings || {};
    if (typeof settings === 'string') {
        try {
            settings = JSON.parse(settings);
        } catch (e) {
            console.error('Error parsing section settings:', e);
            return 0.3;
        }
    }
    return settings.overlayOpacity ?? 0.3;
}

function parseServices(section: any): any[] {
    try {
        // Parse settings if it's a string
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        // Backend returns items, not services
        return settings.items || settings.services || [];
    } catch (e) {
        console.error('Error parsing services:', e);
        return [];
    }
}

function parseTeam(section: any): any[] {
    try {
        // Parse settings if it's a string
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.members || [];
    } catch (e) {
        console.error('Error parsing team:', e);
        return [];
    }
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map(n => n.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function parseGallery(section: any): { images: any[]; columns: number } {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            images: settings.images || [],
            columns: settings.columns || 3
        };
    } catch (e) {
        console.error('Error parsing gallery:', e);
        return { images: [], columns: 3 };
    }
}

function parseVideo(section: any): string | null {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.embedUrl || null;
    } catch (e) {
        console.error('Error parsing video:', e);
        return null;
    }
}

function parseFAQ(section: any): any[] {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.items || [];
    } catch (e) {
        console.error('Error parsing FAQ:', e);
        return [];
    }
}

function parseStatistics(section: any): { items: any[]; columns: number } {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            items: settings.statistics || [],
            columns: settings.columns || 4
        };
    } catch (e) {
        console.error('Error parsing statistics:', e);
        return { items: [], columns: 4 };
    }
}

function parseFeatures(section: any): { items: any[]; columns: number } {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            items: settings.features || [],
            columns: settings.columns || 3
        };
    } catch (e) {
        console.error('Error parsing features:', e);
        return { items: [], columns: 3 };
    }
}

function parseCTA(section: any): { buttonText: string | null; buttonLink: string | null } {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            buttonText: settings.buttonText || null,
            buttonLink: settings.buttonLink || null
        };
    } catch (e) {
        console.error('Error parsing CTA:', e);
        return { buttonText: null, buttonLink: null };
    }
}

function parsePricing(section: any): any[] {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.plans || [];
    } catch (e) {
        console.error('Error parsing pricing:', e);
        return [];
    }
}

function parseTestimonials(section: any): any[] {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.testimonials || [];
    } catch (e) {
        console.error('Error parsing testimonials:', e);
        return [];
    }
}

function parsePortfolio(section: any): { items: any[]; columns: number } {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            items: settings.projects || settings.portfolio || [],
            columns: settings.columns || 3
        };
    } catch (e) {
        console.error('Error parsing portfolio:', e);
        return { items: [], columns: 3 };
    }
}

/** Welcher Beitrag gerade ausgeklappt ist - der Einseiter hat keine Unterseiten. */
const offenerBeitrag = ref<number | null>(null);

function beitragUmschalten(id: number) {
    offenerBeitrag.value = offenerBeitrag.value === id ? null : id;
}

function datumVon(beitrag: any): string {
    const roh = beitrag.published_at || beitrag.publish_date || beitrag.created_at;
    if (!roh) return '';
    const d = new Date(roh);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString('de-DE', { day: '2-digit', month: 'long', year: 'numeric' });
}

/*
   Der Anriss, notfalls aus dem Inhalt gewonnen. Die Auszeichnungen muessen
   raus, sonst stehen spitze Klammern in der Kachel.
*/
function anrissVon(beitrag: any): string {
    if (beitrag.excerpt) return beitrag.excerpt;
    const nurText = String(beitrag.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    return nurText.length > 160 ? nurText.slice(0, 160).trimEnd() + ' …' : nurText;
}

function toggleFAQ(index: number) {
    const idx = openFAQs.value.indexOf(index);
    if (idx > -1) {
        openFAQs.value.splice(idx, 1);
    } else {
        openFAQs.value.push(index);
    }
}

function getDividerSettings(section: any): { shape: string; position: string; flip: boolean; height: number; colorMode: string; color: string } {
    try {
        // Parse settings if it's a string
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return {
            shape: settings.dividerShape || 'none',
            position: settings.dividerPosition || 'bottom',
            flip: settings.dividerFlip || false,
            height: settings.dividerHeight || 80,
            colorMode: settings.dividerColorMode || 'primary',
            color: settings.dividerColor || 'var(--k-accent)'
        };
    } catch (e) {
        console.error('Error parsing divider settings:', e);
        return { shape: 'none', position: 'bottom', flip: false, height: 80, colorMode: 'primary', color: 'var(--k-accent)' };
    }
}

function getDividerColor(section: any, position: 'top' | 'bottom'): string {
    const settings = getDividerSettings(section);

    if (settings.colorMode === 'custom') {
        return settings.color;
    }

    if (settings.colorMode === 'auto') {
        // Find next/previous section based on position
        const currentIndex = activeSections.value.findIndex(s => s.id === section.id);

        if (position === 'bottom' && currentIndex < activeSections.value.length - 1) {
            // Use next section's background
            const nextSection = activeSections.value[currentIndex + 1];
            let nextSettings = nextSection.settings || {};
            if (typeof nextSettings === 'string') {
                try {
                    nextSettings = JSON.parse(nextSettings);
                } catch (e) {
                    console.error('Error parsing next section settings:', e);
                }
            }
            return nextSettings.backgroundColor || 'var(--background-color)';
        } else if (position === 'top' && currentIndex > 0) {
            // Use previous section's background
            const prevSection = activeSections.value[currentIndex - 1];
            let prevSettings = prevSection.settings || {};
            if (typeof prevSettings === 'string') {
                try {
                    prevSettings = JSON.parse(prevSettings);
                } catch (e) {
                    console.error('Error parsing prev section settings:', e);
                }
            }
            return prevSettings.backgroundColor || 'var(--background-color)';
        }

        // Fallback to current section background or primary
        let currentSettings = section.settings || {};
        if (typeof currentSettings === 'string') {
            try {
                currentSettings = JSON.parse(currentSettings);
            } catch (e) {
                console.error('Error parsing current section settings:', e);
            }
        }
        return currentSettings.backgroundColor || 'var(--background-color)';
    }

    // Default: primary color
    return 'var(--primary-color)';
}

function scrollToSection(sectionId: number | string) {
    const element = document.getElementById(`section-${sectionId}`);
    if (!element) {
        console.error('Abschnitt nicht gefunden:', `section-${sectionId}`);
        return;
    }

    /*
       Vorher rechnete diese Funktion eine Zielposition aus und gab sie an
       window.scrollTo() bzw. den Vorschau-Container. Im eingebetteten Zustand
       scrollt aber keines von beiden - gemessen: kein einziger Menuepunkt
       bewegte die Seite, auch die vorhandenen nicht.

       scrollIntoView() fragt nicht, wer scrollt, sondern laesst das den Browser
       entscheiden. Der Abstand zum festen Kopf steckt als scroll-margin-top am
       Abschnitt, statt hier als Zahl.

       Ohne behavior: 'smooth' - gemessen im eingebetteten Zustand bewegt sich
       damit nichts, ohne springt es sauber. Ein Sprung, der ankommt, ist mehr
       wert als eine weiche Bewegung, die ausbleibt.
    */
    element.scrollIntoView({ block: 'start' });
}

function scrollToTop() {
    if (props.previewMode && scrollContainer.value) {
        // In preview mode, scroll the container
        scrollContainer.value.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    } else {
        // In production mode, scroll the window
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

function handleCTA() {
    if (props.website.cta_target_type === 'contact') {
        const contactSection = activeSections.value.find(s => s.section_type === 'contact');
        if (contactSection) {
            scrollToSection(contactSection.id);
        }
    }
}

function submitContact() {
    // TODO: Implement contact form submission
    console.log('Contact form submitted:', contactForm.value);
    alert('Vielen Dank für Ihre Nachricht! Wir werden uns bald bei Ihnen melden.');
    contactForm.value = { name: '', email: '', message: '' };
}

// Scroll Spy
function handleScroll(event?: Event) {
    let scrollY = 0;
    let container: HTMLElement | null = null;

    if (props.previewMode && scrollContainer.value) {
        // In preview mode, get scroll position from saved container
        container = scrollContainer.value;
        scrollY = container.scrollTop;
    } else {
        // In production mode, use window scroll
        scrollY = window.scrollY;
    }

    isScrolled.value = scrollY > 100;

    // Update active section based on scroll position
    const sections = activeSections.value;
    const headerHeight = 80;

    for (let i = sections.length - 1; i >= 0; i--) {
        const section = sections[i];
        const element = document.getElementById(`section-${section.id}`);
        if (element) {
            if (props.previewMode && container) {
                // In preview mode: compare element's offsetTop with container's scrollTop
                const elementTop = element.offsetTop;
                const containerScroll = container.scrollTop;

                if (elementTop - headerHeight <= containerScroll + 200) {
                    activeSection.value = section.id;
                    break;
                }
            } else {
                // In production mode: use getBoundingClientRect
                const rect = element.getBoundingClientRect();
                if (rect.top <= 150) {
                    activeSection.value = section.id;
                    break;
                }
            }
        }
    }
}

// Helper function to find the scrollable parent element
function findScrollParent(element: HTMLElement | null): HTMLElement | null {
    if (!element) return null;

    let parent = element.parentElement;
    while (parent) {
        const style = window.getComputedStyle(parent);
        const overflow = style.overflow + style.overflowY + style.overflowX;

        if (/(auto|scroll)/.test(overflow)) {
            console.log('📦 Found scrollable parent:', parent.className);
            return parent;
        }

        parent = parent.parentElement;
    }

    return null;
}

// Lifecycle
onMounted(async () => {
    console.log('=== OnePagerTemplate Mounted ===');
    console.log('Preview Mode:', props.previewMode);
    console.log('Website:', props.website);
    console.log('Sections:', props.sections);
    console.log('Active Sections:', activeSections.value);

    // Log each section's data
    activeSections.value.forEach((section, index) => {
        console.log(`Section ${index} (${section.section_type}):`, {
            id: section.id,
            title: section.title,
            subtitle: section.subtitle,
            content: section.content,
            is_active: section.is_active
        });
    });

    // Wait for DOM to be fully rendered
    await nextTick();
    console.log('🔄 nextTick completed - DOM should be ready');

    // Attach scroll listener to the correct container
    if (props.previewMode) {
        // In preview mode, find the scrollable parent element
        const templateElement = document.querySelector('.onepager-template') as HTMLElement;

        if (templateElement) {
            console.log('✅ Found .onepager-template');
            const foundContainer = findScrollParent(templateElement);

            if (foundContainer) {
                scrollContainer.value = foundContainer; // Store the container
                console.log('✅ Scroll listener attached to scrollable parent:', foundContainer.className);
                foundContainer.addEventListener('scroll', handleScroll);
                handleScroll(); // Initial check
            } else {
                console.error('❌ No scrollable parent found!');
            }
        } else {
            console.error('❌ .onepager-template not found!');
        }
    } else {
        // In production mode, listen to window scroll
        console.log('✅ Scroll listener attached to window');
        window.addEventListener('scroll', handleScroll);
        handleScroll(); // Initial check
    }
});

onBeforeUnmount(() => {
    // Remove scroll listener from the correct container
    if (props.previewMode && scrollContainer.value) {
        scrollContainer.value.removeEventListener('scroll', handleScroll);
    } else {
        window.removeEventListener('scroll', handleScroll);
    }
});
</script>

<style scoped>
.onepager-template {
    width: 100%;
    min-height: 100vh;
    background-color: var(--background-color);
    color: var(--text-color);
}

/* Header */
.onepager-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0);
    transition: all 0.3s ease;
}

/* Dark mode header background (for all dark themes) */
.onepager-template[style*="--background-color: #111827"] .onepager-header,
.onepager-template[style*="--background-color: #0f1419"] .onepager-header,
.onepager-template[style*="--background-color: #1e1b2e"] .onepager-header,
.onepager-template[style*="--background-color: #1c1917"] .onepager-header,
.onepager-template[style*="--background-color: #0f172a"] .onepager-header {
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(10px);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Preview mode: Use absolute positioning within container */
.preview-mode .onepager-header.preview-header {
    position: absolute;
}

.onepager-header.scrolled {
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
    border-bottom-color: rgba(0, 0, 0, 0.15);
}

.onepager-template[style*="--background-color: #111827"] .onepager-header.scrolled,
.onepager-template[style*="--background-color: #0f1419"] .onepager-header.scrolled,
.onepager-template[style*="--background-color: #1e1b2e"] .onepager-header.scrolled,
.onepager-template[style*="--background-color: #1c1917"] .onepager-header.scrolled,
.onepager-template[style*="--background-color: #0f172a"] .onepager-header.scrolled {
    border-bottom-color: rgba(255, 255, 255, 0.15);
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.site-branding {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.site-logo {
    height: 40px;
    width: auto;
}

.site-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--primary-color);
}

.onepager-nav .nav-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 2rem;
}

.onepager-nav .nav-menu li a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    transition: color 0.3s ease;
    position: relative;
}

.onepager-nav .nav-menu li a:hover,
.onepager-nav .nav-menu li.active a {
    color: var(--primary-color);
}

.onepager-nav .nav-menu li.active a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--primary-color);
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-color);
}

.mobile-menu {
    display: none;
    background: var(--background-color);
}

/* Sections */
.sections-container {
    padding-top: 80px; /* Height of fixed header */
}

.onepager-section {
    min-height: 100vh; /* Can be overridden by inline style */
    padding: 6rem 2rem; /* Can be overridden by inline style */
    position: relative;
    --content-width: 1200px; /* Default, can be overridden by inline style */
}

/* Wrapper for section content - this is the flex container */
.section-content-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100%;
    width: 100%;
    /* position: relative removed - interferes with divider positioning */
    z-index: 1;
}

/* Section with background image: add overlay */
.onepager-section[style*="background-image"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, var(--overlay-opacity, 0.3));
    pointer-events: none;
    z-index: 0;
}

/* Ensure section content is above overlay */
.onepager-section > div {
    /* position: relative; */ /* REMOVED: Causing layout issues */
    z-index: 1;
}

.section-hero {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 8rem 2rem;
}

.hero-content {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.4) 100%);
}

.hero-inner {
    position: relative;
    z-index: 1;
    text-align: var(--title-alignment, center);
    max-width: 900px;
    padding: 3rem 2rem;
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-title {
    font-size: 4rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    color: white;
    line-height: 1.2;
    letter-spacing: -0.02em;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
}

.hero-subtitle {
    font-size: 1.75rem;
    margin-bottom: 2rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 400;
    line-height: 1.4;
}

.hero-description {
    font-size: 1.25rem;
    line-height: 1.8;
    margin-bottom: 3rem;
    color: rgba(255, 255, 255, 0.9);
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.cta-button {
    padding: 1.25rem 3rem;
    font-size: 1.125rem;
    font-weight: 600;
    background-color: white;
    color: var(--primary-color);
    border: none;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    background-color: var(--accent-color);
    color: white;
}

/* Container */
.container {
    max-width: var(--content-width, 1200px);
    margin: 0 auto;
    width: 100%;
    padding: 0 1rem;
}

.section-content {
    width: 100%;
}

.section-title {
    font-size: 3rem;
    font-weight: 700;
    /* Support BOTH block (text-align) and flex (justify-content) */
    text-align: var(--title-alignment, center);
    justify-content: var(--title-justify, center);
    /* Force block display to prevent flex issues */
    display: block !important;
    margin-bottom: 1rem;
    color: var(--primary-color);
    line-height: 1.2;
    letter-spacing: -0.02em;
    width: 100%;
}

.section-subtitle {
    font-size: 1.375rem;
    text-align: var(--title-alignment, center);
    justify-content: var(--title-justify, center);
    display: block !important;
    margin-bottom: 4rem;
    color: var(--text-color);
    opacity: 0.7;
    font-weight: 400;
    width: 100%;
}

.section-text {
    font-size: 1.125rem;
    line-height: 1.9;
    color: var(--text-color);
    opacity: 0.85;
    text-align: var(--title-alignment, center);
    justify-content: var(--title-justify, center);
    display: block !important;
    max-width: 800px;
    margin: 0 auto;
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2.5rem;
    width: 100%;
}

.service-card {
    background: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    text-align: var(--title-alignment, center);
}

/* Dark mode service cards */
.onepager-template[style*="--background-color: #111827"] .service-card,
.onepager-template[style*="--background-color: #0f1419"] .service-card,
.onepager-template[style*="--background-color: #1e1b2e"] .service-card,
.onepager-template[style*="--background-color: #1c1917"] .service-card,
.onepager-template[style*="--background-color: #0f172a"] .service-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.onepager-template[style*="--background-color: #111827"] .service-card:hover,
.onepager-template[style*="--background-color: #0f1419"] .service-card:hover,
.onepager-template[style*="--background-color: #1e1b2e"] .service-card:hover,
.onepager-template[style*="--background-color: #1c1917"] .service-card:hover,
.onepager-template[style*="--background-color: #0f172a"] .service-card:hover {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.service-icon {
    font-size: 4rem;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-left: auto;
    margin-right: auto;
}

.service-card h3 {
    font-size: 1.625rem;
    margin-bottom: 1rem;
    color: var(--text-color);
    font-weight: 600;
}

.service-card p {
    color: var(--text-color);
    opacity: 0.8;
    line-height: 1.7;
    font-size: 1.0625rem;
}

/* Team Grid */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.team-member {
    text-align: var(--title-alignment, center);
}

.member-image {
    width: 150px;
    height: 150px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    overflow: hidden;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: 700;
}

.member-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-member h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.member-position {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.member-bio {
    color: var(--text-color);
    opacity: 0.7;
    line-height: 1.5;
}

/* Contact */
.contact-info {
    display: flex;
    gap: 3rem;
    justify-content: center;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.125rem;
    padding: 1rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Dark mode contact items */
.onepager-template[style*="--background-color: #111827"] .contact-item,
.onepager-template[style*="--background-color: #0f1419"] .contact-item,
.onepager-template[style*="--background-color: #1e1b2e"] .contact-item,
.onepager-template[style*="--background-color: #1c1917"] .contact-item,
.onepager-template[style*="--background-color: #0f172a"] .contact-item {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.contact-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.onepager-template[style*="--background-color: #111827"] .contact-item:hover,
.onepager-template[style*="--background-color: #0f1419"] .contact-item:hover,
.onepager-template[style*="--background-color: #1e1b2e"] .contact-item:hover,
.onepager-template[style*="--background-color: #1c1917"] .contact-item:hover,
.onepager-template[style*="--background-color: #0f172a"] .contact-item:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.contact-item i {
    color: var(--primary-color);
    font-size: 1.75rem;
}

.contact-item a {
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
}

.contact-item a:hover {
    color: var(--primary-color);
}

.contact-form {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 3rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Dark mode contact form */
.onepager-template[style*="--background-color: #111827"] .contact-form,
.onepager-template[style*="--background-color: #0f1419"] .contact-form,
.onepager-template[style*="--background-color: #1e1b2e"] .contact-form,
.onepager-template[style*="--background-color: #1c1917"] .contact-form,
.onepager-template[style*="--background-color: #0f172a"] .contact-form {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.form-group {
    margin-bottom: 2rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 1.25rem;
    font-size: 1rem;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
    background: rgba(0, 0, 0, 0.02);
    color: var(--text-color);
}

/* Dark mode form inputs */
.onepager-template[style*="--background-color: #111827"] .form-group input,
.onepager-template[style*="--background-color: #111827"] .form-group textarea,
.onepager-template[style*="--background-color: #0f1419"] .form-group input,
.onepager-template[style*="--background-color: #0f1419"] .form-group textarea,
.onepager-template[style*="--background-color: #1e1b2e"] .form-group input,
.onepager-template[style*="--background-color: #1e1b2e"] .form-group textarea,
.onepager-template[style*="--background-color: #1c1917"] .form-group input,
.onepager-template[style*="--background-color: #1c1917"] .form-group textarea,
.onepager-template[style*="--background-color: #0f172a"] .form-group input,
.onepager-template[style*="--background-color: #0f172a"] .form-group textarea {
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: var(--text-color);
    opacity: 0.5;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--k-accent-weak);
    background: rgba(0, 0, 0, 0.03);
}

.onepager-template[style*="--background-color: #111827"] .form-group input:focus,
.onepager-template[style*="--background-color: #111827"] .form-group textarea:focus,
.onepager-template[style*="--background-color: #0f1419"] .form-group input:focus,
.onepager-template[style*="--background-color: #0f1419"] .form-group textarea:focus,
.onepager-template[style*="--background-color: #1e1b2e"] .form-group input:focus,
.onepager-template[style*="--background-color: #1e1b2e"] .form-group textarea:focus,
.onepager-template[style*="--background-color: #1c1917"] .form-group input:focus,
.onepager-template[style*="--background-color: #1c1917"] .form-group textarea:focus,
.onepager-template[style*="--background-color: #0f172a"] .form-group input:focus,
.onepager-template[style*="--background-color: #0f172a"] .form-group textarea:focus {
    box-shadow: 0 0 0 3px var(--k-accent-weak);
    background: rgba(255, 255, 255, 0.08);
}

.submit-button {
    width: 100%;
    padding: 1.25rem;
    font-size: 1.125rem;
    font-weight: 600;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* Footer */
.onepager-footer {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 3rem 2rem;
    text-align: var(--title-alignment, center);
}

.onepager-footer p {
    margin: 0;
    font-size: 1rem;
    opacity: 0.9;
}

/* Scroll to Top */
.scroll-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
    z-index: 999;
}

.scroll-to-top:hover {
    transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 768px) {
    .onepager-nav {
        display: none;
    }

    .mobile-menu-toggle {
        display: block;
    }

    .mobile-menu {
        display: block;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .mobile-menu.open {
        max-height: 500px;
    }

    .mobile-menu ul {
        list-style: none;
        padding: 1rem 2rem;
        margin: 0;
    }

    .mobile-menu li {
        margin-bottom: 1rem;
    }

    .mobile-menu a {
        text-decoration: none;
        color: var(--text-color);
        font-weight: 500;
        font-size: 1.125rem;
    }

    .hero-title {
        font-size: 2.5rem;
        line-height: 1.3;
    }

    .hero-subtitle {
        font-size: 1.25rem;
    }

    .hero-description {
        font-size: 1.125rem;
    }

    .section-title {
        font-size: 2.25rem;
    }

    .section-subtitle {
        font-size: 1.125rem;
    }

    .onepager-section {
        min-height: auto;
        padding: 4rem 1.5rem;
    }

    .section-hero {
        padding: 5rem 1.5rem;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .contact-info {
        flex-direction: column;
        gap: 1.5rem;
    }

    .contact-form {
        padding: 2rem;
    }
}

/* Gallery */
.gallery-grid {
    display: grid;
    gap: 2rem;
    width: 100%;
}

.gallery-columns-2 { grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); }
.gallery-columns-3 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
.gallery-columns-4 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.gallery-item:hover {
    transform: scale(1.05);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.gallery-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    padding: 1rem;
    font-size: 0.875rem;
}

/* Video */
.video-container {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    background: #000;
    border-radius: 16px;
    overflow: hidden;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* FAQ */
.faq-items {
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.onepager-template[style*="--background-color: #111827"] .faq-item,
.onepager-template[style*="--background-color: #0f1419"] .faq-item,
.onepager-template[style*="--background-color: #1e1b2e"] .faq-item,
.onepager-template[style*="--background-color: #1c1917"] .faq-item,
.onepager-template[style*="--background-color: #0f172a"] .faq-item {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
}

.faq-item:hover {
    border-color: var(--primary-color);
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
}

.faq-question h3 {
    margin: 0;
    font-size: 1.125rem;
    color: var(--text-color);
}

.faq-question i {
    color: var(--primary-color);
    font-size: 1.5rem;
}

.faq-answer {
    padding: 0 1.5rem 1.5rem;
    color: var(--text-color);
    opacity: 0.8;
    line-height: 1.7;
}

/* Statistics */
.statistics-grid {
    display: grid;
    gap: 3rem;
    text-align: var(--title-alignment, center);
}

.stats-columns-2 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
.stats-columns-3 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
.stats-columns-4 { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }

.statistic-item {
    padding: 2rem;
}

.stat-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1.125rem;
    color: var(--text-color);
    opacity: 0.8;
}

/* Features */
.features-grid {
    display: grid;
    gap: 2rem;
}

.features-columns-2 { grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); }
.features-columns-3 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
.features-columns-4 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

.feature-box {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    text-align: var(--title-alignment, center);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.onepager-template[style*="--background-color: #111827"] .feature-box,
.onepager-template[style*="--background-color: #0f1419"] .feature-box,
.onepager-template[style*="--background-color: #1e1b2e"] .feature-box,
.onepager-template[style*="--background-color: #1c1917"] .feature-box,
.onepager-template[style*="--background-color: #0f172a"] .feature-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
}

.feature-box:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.feature-box h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-color);
}

.feature-box p {
    color: var(--text-color);
    opacity: 0.8;
    line-height: 1.6;
}

/* CTA Section */
.cta-content {
    text-align: var(--title-alignment, center);
    padding: 4rem 2rem;
}

.cta-text {
    font-size: 1.25rem;
    line-height: 1.8;
    margin-bottom: 2rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

/* Pricing Section */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
}

.pricing-card {
    background: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: var(--title-alignment, center);
    display: flex;
    flex-direction: column;
}

.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    border-color: var(--primary-color);
}

.plan-name {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 1.5rem;
}

.plan-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.price-currency {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-right: 0.25rem;
}

.price-amount {
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--primary-color);
    line-height: 1;
}

.price-period {
    font-size: 1rem;
    color: var(--text-color);
    opacity: 0.6;
    margin-left: 0.5rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
    flex-grow: 1;
}

.plan-features li {
    padding: 0.875rem 0;
    color: var(--text-color);
    opacity: 0.85;
    font-size: 1.0625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.plan-features li i {
    color: var(--primary-color);
    font-size: 1.25rem;
    flex-shrink: 0;
}

.plan-button {
    padding: 1.125rem 2.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 1.0625rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px var(--k-accent-line);
}

.plan-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--k-accent-line);
}

/* Dark mode pricing cards */
.onepager-template[style*="--background-color: #111827"] .pricing-card,
.onepager-template[style*="--background-color: #0f1419"] .pricing-card,
.onepager-template[style*="--background-color: #1e1b2e"] .pricing-card,
.onepager-template[style*="--background-color: #1c1917"] .pricing-card,
.onepager-template[style*="--background-color: #0f172a"] .pricing-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.onepager-template[style*="--background-color: #111827"] .plan-price,
.onepager-template[style*="--background-color: #0f1419"] .plan-price,
.onepager-template[style*="--background-color: #1e1b2e"] .plan-price,
.onepager-template[style*="--background-color: #1c1917"] .plan-price,
.onepager-template[style*="--background-color: #0f172a"] .plan-price {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Testimonials Section */
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
    max-width: 1200px;
    margin: 0 auto;
}

.testimonial-card {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.testimonial-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    border-color: var(--primary-color);
}

.testimonial-quote {
    flex-grow: 1;
}

.quote-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    opacity: 0.3;
    margin-bottom: 1rem;
}

.testimonial-text {
    font-size: 1.0625rem;
    line-height: 1.8;
    color: var(--text-color);
    opacity: 0.85;
    font-style: italic;
    margin: 0;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 2px solid rgba(0, 0, 0, 0.1);
}

.author-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.author-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-image.placeholder {
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.author-info {
    flex-grow: 1;
}

.author-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-color);
    margin: 0 0 0.25rem 0;
}

.author-position {
    font-size: 0.9375rem;
    color: var(--primary-color);
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.author-company {
    font-size: 0.875rem;
    color: var(--text-color);
    opacity: 0.6;
    margin: 0;
}

.testimonial-rating {
    display: flex;
    gap: 0.25rem;
    color: #fbbf24;
    font-size: 1.125rem;
}

.testimonial-rating i {
    color: #fbbf24;
}

/* Dark mode testimonial cards */
.onepager-template[style*="--background-color: #111827"] .testimonial-card,
.onepager-template[style*="--background-color: #0f1419"] .testimonial-card,
.onepager-template[style*="--background-color: #1e1b2e"] .testimonial-card,
.onepager-template[style*="--background-color: #1c1917"] .testimonial-card,
.onepager-template[style*="--background-color: #0f172a"] .testimonial-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.onepager-template[style*="--background-color: #111827"] .testimonial-author,
.onepager-template[style*="--background-color: #0f1419"] .testimonial-author,
.onepager-template[style*="--background-color: #1e1b2e"] .testimonial-author,
.onepager-template[style*="--background-color: #1c1917"] .testimonial-author,
.onepager-template[style*="--background-color: #0f172a"] .testimonial-author {
    border-top-color: rgba(255, 255, 255, 0.1);
}

/* Portfolio Section */
.portfolio-grid {
    display: grid;
    gap: 2.5rem;
    width: 100%;
}

.portfolio-columns-2 { grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); }
.portfolio-columns-3 { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
.portfolio-columns-4 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

.portfolio-item {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    border: 2px solid rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.portfolio-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    border-color: var(--primary-color);
}

.portfolio-image {
    position: relative;
    width: 100%;
    height: 280px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.portfolio-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.portfolio-item:hover .portfolio-image img {
    transform: scale(1.1);
}

.portfolio-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 4rem;
    opacity: 0.6;
}

.portfolio-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.portfolio-item:hover .portfolio-overlay {
    opacity: 1;
}

.portfolio-overlay i {
    color: white;
    font-size: 3rem;
}

.portfolio-info {
    padding: 2rem;
}

.portfolio-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-color);
    margin: 0 0 0.75rem 0;
}

.portfolio-category {
    font-size: 0.9375rem;
    color: var(--primary-color);
    font-weight: 600;
    margin: 0 0 1rem 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.portfolio-description {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--text-color);
    opacity: 0.8;
    margin: 0;
}

/* Dark mode portfolio items */
.onepager-template[style*="--background-color: #111827"] .portfolio-item,
.onepager-template[style*="--background-color: #0f1419"] .portfolio-item,
.onepager-template[style*="--background-color: #1e1b2e"] .portfolio-item,
.onepager-template[style*="--background-color: #1c1917"] .portfolio-item,
.onepager-template[style*="--background-color: #0f172a"] .portfolio-item {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

/* --- Neuigkeiten ------------------------------------------------------- */

.onepager-section {
    /* Platz fuer den festen Kopf, wenn ein Menuepunkt hierher springt. */
    scroll-margin-top: 80px;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    width: 100%;
    text-align: left;
}

.post-card {
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.post-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

/*
   Ist ein Beitrag ausgeklappt, wird das Raster einspaltig.

   Zuerst hatte nur die offene Karte grid-column: 1 / -1. Damit stand der Text
   zwar breit, aber die uebrigen Karten blieben in schmalen Spalten daneben
   haengen - bei zwei Beitraegen ein Drittel Breite und viel Leere rechts.
   Einspaltig steht der offene Beitrag oben und der Rest als Liste darunter.
*/
.posts-grid:has(.post-card.offen) {
    grid-template-columns: 1fr;
}

.post-card.offen {
    transform: none;
}

.post-card-bild {
    height: 180px;
    background-size: cover;
    background-position: center;
    flex: none;
}

.post-card.offen .post-card-bild {
    height: 280px;
}

.post-card-inhalt {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 1.6rem;
}

.post-card-datum {
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--primary-color);
    font-weight: 600;
}

.post-card-titel {
    margin: 0;
    font-size: 1.25rem;
    line-height: 1.3;
}

.post-card-anriss,
.post-card-text {
    margin: 0;
    line-height: 1.6;
    opacity: 0.85;
}

.post-card-mehr {
    margin-top: 0.4rem;
    padding: 0;
    background: none;
    border: none;
    font: inherit;
    font-weight: 600;
    color: var(--primary-color);
    cursor: pointer;
    border-bottom: 1px solid transparent;
    transition: border-color 0.2s ease;
}

.post-card-mehr:hover {
    border-bottom-color: var(--primary-color);
}

/* Dunkle Farbschemata - dieselben Grundfarben wie bei den Leistungskarten. */
.onepager-template[style*="--background-color: #111827"] .post-card,
.onepager-template[style*="--background-color: #0f1419"] .post-card,
.onepager-template[style*="--background-color: #1e1b2e"] .post-card,
.onepager-template[style*="--background-color: #1c1917"] .post-card,
.onepager-template[style*="--background-color: #0f172a"] .post-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 640px) {
    .posts-grid {
        grid-template-columns: 1fr;
    }
}
</style>
