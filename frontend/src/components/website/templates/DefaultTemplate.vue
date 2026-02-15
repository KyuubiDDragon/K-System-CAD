<template>
    <div class="default-template" :style="websiteStyles">
        <!-- Fixed Header -->
        <header class="site-header" :class="{ 'scrolled': isScrolled }">
            <div class="header-container">
                <div class="site-branding">
                    <img
                        v-if="website.logo"
                        :src="getMediaUrl(website.logo)"
                        :alt="website.site_name"
                        class="site-logo"
                    />
                    <h1 class="site-title" @click="goToHome">{{ website.site_name }}</h1>
                </div>

                <!-- Pages Navigation -->
                <nav class="site-nav">
                    <ul class="nav-menu">
                        <li v-if="website.navbar_name">
                            <a href="#" :class="{ active: currentView === 'home' }" @click.prevent="goToHome">
                                {{ website.navbar_name }}
                            </a>
                        </li>
                        <li v-for="page in activePages" :key="page.id">
                            <a href="#" :class="{ active: currentPage?.id === page.id }" @click.prevent="goToPage(page)">
                                {{ page.title }}
                            </a>
                        </li>
                        <li v-if="activePosts.length > 0">
                            <a href="#" :class="{ active: currentView === 'blog' }" @click.prevent="goToBlog">
                                Blog
                            </a>
                        </li>
                        <li v-if="website.show_contact_form">
                            <a href="#" :class="{ active: currentView === 'contact' }" @click.prevent="goToContact">
                                Kontakt
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
                    <li v-if="website.navbar_name" @click="mobileMenuOpen = false">
                        <a href="#" @click.prevent="goToHome">{{ website.navbar_name }}</a>
                    </li>
                    <li v-for="page in activePages" :key="page.id" @click="mobileMenuOpen = false">
                        <a href="#" @click.prevent="goToPage(page)">{{ page.title }}</a>
                    </li>
                    <li v-if="activePosts.length > 0" @click="mobileMenuOpen = false">
                        <a href="#" @click.prevent="goToBlog">Blog</a>
                    </li>
                    <li v-if="website.show_contact_form" @click="mobileMenuOpen = false">
                        <a href="#" @click.prevent="goToContact">Kontakt</a>
                    </li>
                </ul>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="site-main">
            <!-- Home View (Hero + Sections) -->
            <div v-if="currentView === 'home'" class="home-view">
                <!-- Hero Section -->
                <section class="hero-section" :style="getHeroStyle()">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <h1 class="hero-title">{{ website.site_name }}</h1>
                        <p v-if="website.site_slogan" class="hero-subtitle">{{ website.site_slogan }}</p>
                        <div v-if="website.cta_text" class="hero-cta">
                            <button class="cta-button" @click="handleCTA">
                                {{ website.cta_text }}
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Home Sections (if any exist with homepage=true) -->
                <div v-for="section in homeSections" :key="section.id" class="page-section">
                    <component :is="renderSection(section)" />
                </div>
            </div>

            <!-- Page View -->
            <div v-else-if="currentView === 'page' && currentPage" class="page-view">
                <div class="page-header">
                    <div class="container">
                        <h1>{{ currentPage.title }}</h1>
                        <div v-if="currentPage.content" v-html="currentPage.content" class="page-intro"></div>
                    </div>
                </div>

                <!-- Page Sections -->
                <div v-for="section in currentPageSections" :key="section.id" class="page-section">
                    <component :is="renderSection(section)" />
                </div>
            </div>

            <!-- Blog View -->
            <div v-else-if="currentView === 'blog'" class="blog-view">
                <div class="container">
                    <h1 class="page-title">Blog</h1>
                    <div class="posts-grid">
                        <article v-for="post in activePosts" :key="post.id" class="post-card" @click="goToPost(post)">
                            <div v-if="post.featured_image" class="post-image">
                                <img :src="getMediaUrl(post.featured_image)" :alt="post.title" />
                            </div>
                            <div class="post-content">
                                <h2>{{ post.title }}</h2>
                                <div class="post-meta">
                                    <span v-if="post.created_at">{{ formatDate(post.created_at) }}</span>
                                    <span v-if="post.category">{{ post.category }}</span>
                                </div>
                                <p v-if="post.excerpt" class="post-excerpt">{{ post.excerpt }}</p>
                                <a href="#" class="read-more" @click.prevent="goToPost(post)">Weiterlesen →</a>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <!-- Single Post View -->
            <div v-else-if="currentView === 'post' && currentPost" class="post-view">
                <div class="container">
                    <article class="single-post">
                        <div v-if="currentPost.featured_image" class="post-featured-image">
                            <img :src="getMediaUrl(currentPost.featured_image)" :alt="currentPost.title" />
                        </div>
                        <header class="post-header">
                            <h1>{{ currentPost.title }}</h1>
                            <div class="post-meta">
                                <span v-if="currentPost.created_at">{{ formatDate(currentPost.created_at) }}</span>
                                <span v-if="currentPost.category">{{ currentPost.category }}</span>
                            </div>
                        </header>
                        <div class="post-body" v-html="currentPost.content"></div>
                        <footer class="post-footer">
                            <button @click="goToBlog" class="back-button">← Zurück zum Blog</button>
                        </footer>
                    </article>
                </div>
            </div>

            <!-- Contact View -->
            <div v-else-if="currentView === 'contact'" class="contact-view">
                <div class="container">
                    <h1 class="page-title">Kontakt</h1>

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
        </main>

        <!-- Footer -->
        <footer class="site-footer">
            <div class="container">
                <p>{{ website.footer_text || `© ${new Date().getFullYear()} ${website.site_name}` }}</p>
            </div>
        </footer>

        <!-- Scroll to Top -->
        <button v-if="isScrolled" class="scroll-to-top" @click="scrollToTop">
            <i class="mdi mdi-arrow-up"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, h } from 'vue';
import SectionDivider from '@/components/website/SectionDivider.vue';

interface Props {
    website: any;
    navigationItems?: any[];
    pages?: any[];
    posts?: any[];
    categories?: any[];
    sections?: any[];
    news?: any[];
    previewMode?: boolean;
    getMediaUrl?: (fileName: string | null) => string;
}

const props = withDefaults(defineProps<Props>(), {
    navigationItems: () => [],
    pages: () => [],
    posts: () => [],
    categories: () => [],
    sections: () => [],
    news: () => [],
    previewMode: false,
    getMediaUrl: (fileName) => fileName || ''
});

// State
const currentView = ref<'home' | 'page' | 'blog' | 'post' | 'contact'>('home');
const currentPage = ref<any>(null);
const currentPost = ref<any>(null);
const isScrolled = ref(false);
const mobileMenuOpen = ref(false);
const openFAQs = ref<number[]>([]);
const contactForm = ref({
    name: '',
    email: '',
    message: ''
});

// Preset Color Schemes
const presetColorSchemes: Record<string, any> = {
    'Blue Ocean': { primary: '#3b82f6', secondary: '#60a5fa', accent: '#93c5fd', background: '#ffffff', text: '#111827' },
    'Forest Green': { primary: '#10b981', secondary: '#34d399', accent: '#6ee7b7', background: '#ffffff', text: '#111827' },
    'Royal Purple': { primary: '#8b5cf6', secondary: '#a78bfa', accent: '#c4b5fd', background: '#ffffff', text: '#111827' },
    'Sunset Orange': { primary: '#f59e0b', secondary: '#fbbf24', accent: '#fcd34d', background: '#ffffff', text: '#111827' },
    'Rose Pink': { primary: '#ec4899', secondary: '#f472b6', accent: '#fbcfe8', background: '#ffffff', text: '#111827' },
    'Dark Blue': { primary: '#3b82f6', secondary: '#60a5fa', accent: '#93c5fd', background: '#111827', text: '#f9fafb' },
    'Dark Forest': { primary: '#10b981', secondary: '#34d399', accent: '#6ee7b7', background: '#0f1419', text: '#f0fdf4' },
    'Dark Purple': { primary: '#a855f7', secondary: '#c084fc', accent: '#e9d5ff', background: '#1e1b2e', text: '#faf5ff' },
    'Dark Amber': { primary: '#f59e0b', secondary: '#fbbf24', accent: '#fde68a', background: '#1c1917', text: '#fffbeb' },
    'Dark Slate': { primary: '#64748b', secondary: '#94a3b8', accent: '#cbd5e1', background: '#0f172a', text: '#f1f5f9' },
};

// Computed
const activePages = computed(() => props.pages.filter(p => p.is_active !== 0));
const activePosts = computed(() => props.posts.filter(p => p.is_published));
const activeSections = computed(() => props.sections.filter(s => s.is_active !== 0));

const homeSections = computed(() => {
    return activeSections.value.filter(s => s.show_on_homepage);
});

const currentPageSections = computed(() => {
    if (!currentPage.value) return [];
    return activeSections.value.filter(s => s.page_id === currentPage.value.id);
});

const websiteStyles = computed(() => {
    let colors: any = {};
    if (props.website.use_custom_colors === false && props.website.selected_scheme) {
        colors = presetColorSchemes[props.website.selected_scheme] || presetColorSchemes['Blue Ocean'];
    } else if (props.website.customColors) {
        colors = props.website.customColors;
    } else {
        colors = {
            primary: props.website.primary_color || '#3b82f6',
            secondary: props.website.secondary_color || '#1e3a8a',
            accent: '#60a5fa',
            background: props.website.background_color || '#ffffff',
            text: '#111827',
        };
    }
    return {
        '--primary-color': colors.primary,
        '--secondary-color': colors.secondary,
        '--accent-color': colors.accent,
        '--background-color': colors.background,
        '--text-color': colors.text,
    };
});

// Methods - Navigation
function goToHome() {
    currentView.value = 'home';
    currentPage.value = null;
    currentPost.value = null;
    scrollToTop();
}

function goToPage(page: any) {
    currentView.value = 'page';
    currentPage.value = page;
    currentPost.value = null;
    scrollToTop();
}

function goToBlog() {
    currentView.value = 'blog';
    currentPage.value = null;
    currentPost.value = null;
    scrollToTop();
}

function goToPost(post: any) {
    currentView.value = 'post';
    currentPost.value = post;
    currentPage.value = null;
    scrollToTop();
}

function goToContact() {
    currentView.value = 'contact';
    currentPage.value = null;
    currentPost.value = null;
    scrollToTop();
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleCTA() {
    if (props.website.cta_target_type === 'contact') {
        goToContact();
    }
}

function submitContact() {
    console.log('Contact form submitted:', contactForm.value);
    alert('Vielen Dank für Ihre Nachricht! Wir werden uns bald bei Ihnen melden.');
    contactForm.value = { name: '', email: '', message: '' };
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', { year: 'numeric', month: 'long', day: 'numeric' });
}

function getHeroStyle() {
    const style: any = {
        background: `linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%)`
    };
    if (props.website.banner_image) {
        style.backgroundImage = `url(${props.getMediaUrl(props.website.banner_image)})`;
        style.backgroundSize = 'cover';
        style.backgroundPosition = 'center';
    }
    return style;
}

// Parser Functions (from OnePager)
function parseServices(section: any): any[] {
    try {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            settings = JSON.parse(settings);
        }
        return settings.items || settings.services || [];
    } catch (e) {
        console.error('Error parsing services:', e);
        return [];
    }
}

function parseTeam(section: any): any[] {
    try {
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

function toggleFAQ(index: number) {
    const idx = openFAQs.value.indexOf(index);
    if (idx > -1) {
        openFAQs.value.splice(idx, 1);
    } else {
        openFAQs.value.push(index);
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

function getSectionStyle(section: any) {
    let settings = section.settings || {};
    if (typeof settings === 'string') {
        try {
            settings = JSON.parse(settings);
        } catch (e) {
            console.error('Error parsing section settings:', e);
            settings = {};
        }
    }

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

    return style;
}

function getDividerSettings(section: any): { shape: string; position: string; flip: boolean; height: number; colorMode: string; color: string } {
    try {
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
            color: settings.dividerColor || '#3b82f6'
        };
    } catch (e) {
        console.error('Error parsing divider settings:', e);
        return { shape: 'none', position: 'bottom', flip: false, height: 80, colorMode: 'primary', color: '#3b82f6' };
    }
}

function getDividerColor(section: any, position: 'top' | 'bottom', sectionsList: any[]): string {
    const settings = getDividerSettings(section);

    if (settings.colorMode === 'custom') {
        return settings.color;
    }

    if (settings.colorMode === 'auto') {
        const currentIndex = sectionsList.findIndex(s => s.id === section.id);

        if (position === 'bottom' && currentIndex < sectionsList.length - 1) {
            const nextSection = sectionsList[currentIndex + 1];
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
            const prevSection = sectionsList[currentIndex - 1];
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

    return 'var(--primary-color)';
}

// Section Rendering using h() function
function renderSection(section: any) {
    const sectionStyle = getSectionStyle(section);
    const dividerSettings = getDividerSettings(section);
    const sectionsList = currentView.value === 'home' ? homeSections.value : currentPageSections.value;

    const children: any[] = [];

    // Top Divider
    if (dividerSettings.shape !== 'none' && (dividerSettings.position === 'top' || dividerSettings.position === 'both')) {
        children.push(
            h(SectionDivider, {
                shape: dividerSettings.shape,
                position: 'top',
                color: getDividerColor(section, 'top', sectionsList),
                flip: dividerSettings.flip,
                height: dividerSettings.height
            })
        );
    }

    // Section Content
    let sectionContent: any = null;

    if (section.section_type === 'hero') {
        let settings = section.settings || {};
        if (typeof settings === 'string') {
            try {
                settings = JSON.parse(settings);
            } catch (e) {
                console.error('Error parsing hero settings:', e);
                settings = {};
            }
        }

        sectionContent = h('div', { class: 'hero-content' }, [
            h('h1', null, section.title),
            section.subtitle ? h('p', { class: 'hero-subtitle' }, section.subtitle) : null,
            section.content ? h('div', { innerHTML: section.content }) : null,
            settings.buttonText ? h('button', {
                class: 'hero-button',
                onClick: () => {
                    if (settings.buttonLink) {
                        window.location.href = settings.buttonLink;
                    }
                }
            }, settings.buttonText) : null
        ]);
    }
    else if (section.section_type === 'about') {
        sectionContent = h('div', { class: 'section-content about-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                section.content ? h('div', { class: 'section-text', innerHTML: section.content }) : null
            ])
        ]);
    }
    else if (section.section_type === 'services') {
        const services = parseServices(section);
        sectionContent = h('div', { class: 'section-content services-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'services-grid' }, services.map(service =>
                    h('div', { class: 'service-card' }, [
                        h('div', { class: 'service-icon' }, [
                            h('i', { class: service.icon || 'mdi mdi-check-circle' })
                        ]),
                        h('h3', null, service.title),
                        h('p', null, service.description)
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'team') {
        const members = parseTeam(section);
        sectionContent = h('div', { class: 'section-content team-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'team-grid' }, members.map(member =>
                    h('div', { class: 'team-member' }, [
                        h('div', { class: 'member-image' }, [
                            member.image
                                ? h('img', { src: props.getMediaUrl(member.image), alt: member.name })
                                : h('div', { class: 'placeholder-image' }, getInitials(member.name))
                        ]),
                        h('h3', null, member.name),
                        h('p', { class: 'member-position' }, member.position),
                        h('p', { class: 'member-bio' }, member.bio)
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'gallery') {
        const gallery = parseGallery(section);
        sectionContent = h('div', { class: 'section-content gallery-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: ['gallery-grid', `gallery-columns-${gallery.columns || 3}`] }, gallery.images.map(image =>
                    h('div', { class: 'gallery-item' }, [
                        h('img', { src: props.getMediaUrl(image.url), alt: image.caption || '' }),
                        image.caption ? h('div', { class: 'gallery-caption' }, image.caption) : null
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'video') {
        const embedUrl = parseVideo(section);
        sectionContent = h('div', { class: 'section-content video-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'video-container' }, [
                    embedUrl ? h('iframe', { src: embedUrl, frameborder: '0', allowfullscreen: true }) : null
                ])
            ])
        ]);
    }
    else if (section.section_type === 'faq') {
        const faqs = parseFAQ(section);
        sectionContent = h('div', { class: 'section-content faq-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'faq-items' }, faqs.map((item, index) =>
                    h('div', { class: 'faq-item', onClick: () => toggleFAQ(index) }, [
                        h('div', { class: 'faq-question' }, [
                            h('h3', null, item.question),
                            h('i', { class: ['mdi', openFAQs.value.includes(index) ? 'mdi-minus' : 'mdi-plus'] })
                        ]),
                        openFAQs.value.includes(index) ? h('div', { class: 'faq-answer', innerHTML: item.answer }) : null
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'statistics') {
        const stats = parseStatistics(section);
        sectionContent = h('div', { class: 'section-content statistics-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: ['statistics-grid', `stats-columns-${stats.columns || 4}`] }, stats.items.map(stat =>
                    h('div', { class: 'statistic-item' }, [
                        stat.icon ? h('i', { class: [stat.icon, 'stat-icon'] }) : null,
                        h('div', { class: 'stat-value' }, stat.value),
                        h('div', { class: 'stat-label' }, stat.label)
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'features') {
        const features = parseFeatures(section);
        sectionContent = h('div', { class: 'section-content features-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: ['features-grid', `features-columns-${features.columns || 3}`] }, features.items.map(feature =>
                    h('div', { class: 'feature-box' }, [
                        feature.icon ? h('i', { class: [feature.icon, 'feature-icon'] }) : null,
                        h('h3', null, feature.title),
                        h('p', null, feature.text)
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'cta') {
        const cta = parseCTA(section);
        sectionContent = h('div', { class: 'section-content cta-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.content ? h('div', { class: 'cta-text', innerHTML: section.content }) : null,
                cta.buttonText ? h('button', { class: 'cta-button', onClick: handleCTA }, cta.buttonText) : null
            ])
        ]);
    }
    else if (section.section_type === 'pricing') {
        const plans = parsePricing(section);
        sectionContent = h('div', { class: 'section-content pricing-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'pricing-grid' }, plans.map(plan =>
                    h('div', { class: 'pricing-card' }, [
                        h('h3', { class: 'plan-name' }, plan.name),
                        h('div', { class: 'plan-price' }, [
                            h('span', { class: 'price-currency' }, plan.currency || '€'),
                            h('span', { class: 'price-amount' }, plan.price),
                            plan.period ? h('span', { class: 'price-period' }, plan.period) : null
                        ]),
                        plan.features && Array.isArray(plan.features) ? h('ul', { class: 'plan-features' },
                            plan.features.map((feature: string) => h('li', null, [
                                h('i', { class: 'mdi mdi-check-circle' }),
                                h('span', null, feature)
                            ]))
                        ) : null,
                        plan.buttonText ? h('button', { class: 'plan-button' }, plan.buttonText) : null
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'testimonials') {
        const testimonials = parseTestimonials(section);
        sectionContent = h('div', { class: 'section-content testimonials-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'testimonials-grid' }, testimonials.map(testimonial =>
                    h('div', { class: 'testimonial-card' }, [
                        h('div', { class: 'testimonial-quote' }, [
                            h('i', { class: 'mdi mdi-format-quote-open quote-icon' }),
                            h('p', { class: 'testimonial-text' }, testimonial.text)
                        ]),
                        h('div', { class: 'testimonial-author' }, [
                            testimonial.image
                                ? h('div', { class: 'author-image' }, [
                                    h('img', { src: props.getMediaUrl(testimonial.image), alt: testimonial.name })
                                ])
                                : h('div', { class: 'author-image placeholder' }, getInitials(testimonial.name)),
                            h('div', { class: 'author-info' }, [
                                h('h4', { class: 'author-name' }, testimonial.name),
                                h('p', { class: 'author-position' }, testimonial.position),
                                testimonial.company ? h('p', { class: 'author-company' }, testimonial.company) : null
                            ])
                        ]),
                        testimonial.rating ? h('div', { class: 'testimonial-rating' },
                            [
                                ...Array(testimonial.rating).fill(null).map((_, i) =>
                                    h('i', { key: i, class: 'mdi mdi-star' })
                                ),
                                ...Array(5 - testimonial.rating).fill(null).map((_, i) =>
                                    h('i', { key: `empty-${i}`, class: 'mdi mdi-star-outline' })
                                )
                            ]
                        ) : null
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'portfolio') {
        const portfolio = parsePortfolio(section);
        sectionContent = h('div', { class: 'section-content portfolio-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: ['portfolio-grid', `portfolio-columns-${portfolio.columns || 3}`] }, portfolio.items.map(project =>
                    h('div', { class: 'portfolio-item' }, [
                        h('div', { class: 'portfolio-image' }, [
                            project.image
                                ? h('img', { src: props.getMediaUrl(project.image), alt: project.title })
                                : h('div', { class: 'portfolio-placeholder' }, [
                                    h('i', { class: 'mdi mdi-image' })
                                ]),
                            h('div', { class: 'portfolio-overlay' }, [
                                h('i', { class: 'mdi mdi-eye' })
                            ])
                        ]),
                        h('div', { class: 'portfolio-info' }, [
                            h('h3', { class: 'portfolio-title' }, project.title),
                            project.category ? h('p', { class: 'portfolio-category' }, project.category) : null,
                            project.description ? h('p', { class: 'portfolio-description' }, project.description) : null
                        ])
                    ])
                ))
            ])
        ]);
    }
    else if (section.section_type === 'contact') {
        sectionContent = h('div', { class: 'section-content contact-content' }, [
            h('div', { class: 'container' }, [
                h('h2', { class: 'section-title' }, section.title),
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                h('div', { class: 'contact-info' }, [
                    props.website.contact_email ? h('div', { class: 'contact-item' }, [
                        h('i', { class: 'mdi mdi-email' }),
                        h('a', { href: `mailto:${props.website.contact_email}` }, props.website.contact_email)
                    ]) : null,
                    props.website.contact_phone ? h('div', { class: 'contact-item' }, [
                        h('i', { class: 'mdi mdi-phone' }),
                        h('a', { href: `tel:${props.website.contact_phone}` }, props.website.contact_phone)
                    ]) : null
                ].filter(Boolean)),
                props.website.show_contact_form ? h('form', {
                    class: 'contact-form',
                    onSubmit: (e: Event) => {
                        e.preventDefault();
                        submitContact();
                    }
                }, [
                    h('div', { class: 'form-group' }, [
                        h('input', { type: 'text', placeholder: 'Name', required: true, value: contactForm.value.name, onInput: (e: any) => contactForm.value.name = e.target.value })
                    ]),
                    h('div', { class: 'form-group' }, [
                        h('input', { type: 'email', placeholder: 'E-Mail', required: true, value: contactForm.value.email, onInput: (e: any) => contactForm.value.email = e.target.value })
                    ]),
                    h('div', { class: 'form-group' }, [
                        h('textarea', { placeholder: 'Nachricht', rows: 5, required: true, value: contactForm.value.message, onInput: (e: any) => contactForm.value.message = e.target.value })
                    ]),
                    h('button', { type: 'submit', class: 'submit-button' }, 'Senden')
                ]) : null
            ])
        ]);
    }
    else {
        // Custom or unknown section type
        sectionContent = h('div', { class: 'section-content custom-content' }, [
            h('div', { class: 'container' }, [
                section.title ? h('h2', { class: 'section-title' }, section.title) : null,
                section.subtitle ? h('p', { class: 'section-subtitle' }, section.subtitle) : null,
                section.content ? h('div', { innerHTML: section.content }) : null
            ])
        ]);
    }

    if (sectionContent) {
        children.push(sectionContent);
    }

    // Bottom Divider
    if (dividerSettings.shape !== 'none' && (dividerSettings.position === 'bottom' || dividerSettings.position === 'both')) {
        children.push(
            h(SectionDivider, {
                shape: dividerSettings.shape,
                position: 'bottom',
                color: getDividerColor(section, 'bottom', sectionsList),
                flip: dividerSettings.flip,
                height: dividerSettings.height
            })
        );
    }

    return h('section', {
        class: ['page-section-wrapper', `section-${section.section_type}`],
        style: sectionStyle
    }, children);
}

// Scroll Handler
function handleScroll() {
    isScrolled.value = window.scrollY > 100;
}

// Lifecycle
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    handleScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.default-template {
    width: 100%;
    min-height: 100vh;
    background-color: var(--background-color);
    color: var(--text-color);
}

/* Header - Similar to OnePager */
.site-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.site-header.scrolled {
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
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
    cursor: pointer;
}

.site-nav .nav-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 2rem;
}

.site-nav .nav-menu li a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    transition: color 0.3s ease;
}

.site-nav .nav-menu li a:hover,
.site-nav .nav-menu li a.active {
    color: var(--primary-color);
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
}

/* Main Content */
.site-main {
    padding-top: 80px;
    min-height: calc(100vh - 200px);
}

/* Hero Section */
.hero-section {
    min-height: 600px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    color: white;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5));
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    padding: 3rem 2rem;
    max-width: 900px;
}

.hero-title {
    font-size: 4rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
    font-size: 1.75rem;
    margin-bottom: 2rem;
    opacity: 0.95;
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
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

/* Page View */
.page-view,
.blog-view,
.post-view,
.contact-view {
    padding: 4rem 0;
}

.page-header {
    padding: 4rem 0;
    text-align: center;
}

.page-header h1 {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.page-intro {
    font-size: 1.25rem;
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.8;
}

.page-title {
    font-size: 3rem;
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 3rem;
}

/* Blog */
.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
    cursor: pointer;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.post-image img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.post-content {
    padding: 2rem;
}

.post-content h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-color);
}

.post-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-color);
    opacity: 0.6;
    margin-bottom: 1rem;
}

.post-excerpt {
    line-height: 1.6;
    margin-bottom: 1rem;
}

.read-more {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}

/* Single Post */
.single-post {
    max-width: 800px;
    margin: 0 auto;
}

.post-featured-image {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
}

.post-featured-image img {
    width: 100%;
    height: auto;
}

.post-header {
    margin-bottom: 3rem;
    text-align: center;
}

.post-header h1 {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.post-body {
    font-size: 1.125rem;
    line-height: 1.8;
    margin-bottom: 3rem;
}

.back-button {
    padding: 0.75rem 2rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

/* Contact */
.contact-info {
    display: flex;
    gap: 3rem;
    justify-content: center;
    margin: 3rem 0;
    flex-wrap: wrap;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.contact-item i {
    font-size: 1.75rem;
    color: var(--primary-color);
}

.contact-item a {
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
}

.contact-form {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 3rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
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
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.submit-button {
    width: 100%;
    padding: 1.25rem;
    font-size: 1.125rem;
    font-weight: 600;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
}

/* Footer */
.site-footer {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 3rem 2rem;
    text-align: center;
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
    z-index: 999;
}

/* Dark mode header */
.default-template[style*="--background-color: #111827"] .site-header,
.default-template[style*="--background-color: #0f1419"] .site-header,
.default-template[style*="--background-color: #1e1b2e"] .site-header,
.default-template[style*="--background-color: #1c1917"] .site-header,
.default-template[style*="--background-color: #0f172a"] .site-header {
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(10px);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Section Styles (from OnePager) */
.page-section-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6rem 2rem;
    --content-width: 1200px;
}

/* Section with background image: add overlay */
.page-section-wrapper[style*="background-image"]::before {
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

.page-section-wrapper > * {
    position: relative;
    z-index: 1;
}

.section-content {
    width: 100%;
}

.section-title {
    font-size: 3rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 1rem;
    color: var(--primary-color);
    line-height: 1.2;
}

.section-subtitle {
    font-size: 1.375rem;
    text-align: center;
    margin-bottom: 4rem;
    color: var(--text-color);
    opacity: 0.7;
}

.section-text {
    font-size: 1.125rem;
    line-height: 1.9;
    color: var(--text-color);
    opacity: 0.85;
    text-align: center;
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
    text-align: center;
}

.default-template[style*="--background-color: #111827"] .service-card,
.default-template[style*="--background-color: #0f1419"] .service-card,
.default-template[style*="--background-color: #1e1b2e"] .service-card,
.default-template[style*="--background-color: #1c1917"] .service-card,
.default-template[style*="--background-color: #0f172a"] .service-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
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
    text-align: center;
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
    padding-bottom: 56.25%;
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

.default-template[style*="--background-color: #111827"] .faq-item,
.default-template[style*="--background-color: #0f1419"] .faq-item,
.default-template[style*="--background-color: #1e1b2e"] .faq-item,
.default-template[style*="--background-color: #1c1917"] .faq-item,
.default-template[style*="--background-color: #0f172a"] .faq-item {
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
    text-align: center;
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
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.default-template[style*="--background-color: #111827"] .feature-box,
.default-template[style*="--background-color: #0f1419"] .feature-box,
.default-template[style*="--background-color: #1e1b2e"] .feature-box,
.default-template[style*="--background-color: #1c1917"] .feature-box,
.default-template[style*="--background-color: #0f172a"] .feature-box {
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
    text-align: center;
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
    text-align: center;
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
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.plan-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

/* Dark mode pricing cards */
.default-template[style*="--background-color: #111827"] .pricing-card,
.default-template[style*="--background-color: #0f1419"] .pricing-card,
.default-template[style*="--background-color: #1e1b2e"] .pricing-card,
.default-template[style*="--background-color: #1c1917"] .pricing-card,
.default-template[style*="--background-color: #0f172a"] .pricing-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.default-template[style*="--background-color: #111827"] .plan-price,
.default-template[style*="--background-color: #0f1419"] .plan-price,
.default-template[style*="--background-color: #1e1b2e"] .plan-price,
.default-template[style*="--background-color: #1c1917"] .plan-price,
.default-template[style*="--background-color: #0f172a"] .plan-price {
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
.default-template[style*="--background-color: #111827"] .testimonial-card,
.default-template[style*="--background-color: #0f1419"] .testimonial-card,
.default-template[style*="--background-color: #1e1b2e"] .testimonial-card,
.default-template[style*="--background-color: #1c1917"] .testimonial-card,
.default-template[style*="--background-color: #0f172a"] .testimonial-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.default-template[style*="--background-color: #111827"] .testimonial-author,
.default-template[style*="--background-color: #0f1419"] .testimonial-author,
.default-template[style*="--background-color: #1e1b2e"] .testimonial-author,
.default-template[style*="--background-color: #1c1917"] .testimonial-author,
.default-template[style*="--background-color: #0f172a"] .testimonial-author {
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
.default-template[style*="--background-color: #111827"] .portfolio-item,
.default-template[style*="--background-color: #0f1419"] .portfolio-item,
.default-template[style*="--background-color: #1e1b2e"] .portfolio-item,
.default-template[style*="--background-color: #1c1917"] .portfolio-item,
.default-template[style*="--background-color: #0f172a"] .portfolio-item {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

/* Contact Section (when used as section, not standalone page) */
.contact-content .contact-info {
    display: flex;
    gap: 3rem;
    justify-content: center;
    margin: 3rem 0;
    flex-wrap: wrap;
}

.default-template[style*="--background-color: #111827"] .contact-item,
.default-template[style*="--background-color: #0f1419"] .contact-item,
.default-template[style*="--background-color: #1e1b2e"] .contact-item,
.default-template[style*="--background-color: #1c1917"] .contact-item,
.default-template[style*="--background-color: #0f172a"] .contact-item {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.default-template[style*="--background-color: #111827"] .contact-form,
.default-template[style*="--background-color: #0f1419"] .contact-form,
.default-template[style*="--background-color: #1e1b2e"] .contact-form,
.default-template[style*="--background-color: #1c1917"] .contact-form,
.default-template[style*="--background-color: #0f172a"] .contact-form {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.default-template[style*="--background-color: #111827"] .form-group input,
.default-template[style*="--background-color: #111827"] .form-group textarea,
.default-template[style*="--background-color: #0f1419"] .form-group input,
.default-template[style*="--background-color: #0f1419"] .form-group textarea,
.default-template[style*="--background-color: #1e1b2e"] .form-group input,
.default-template[style*="--background-color: #1e1b2e"] .form-group textarea,
.default-template[style*="--background-color: #1c1917"] .form-group input,
.default-template[style*="--background-color: #1c1917"] .form-group textarea,
.default-template[style*="--background-color: #0f172a"] .form-group input,
.default-template[style*="--background-color: #0f172a"] .form-group textarea {
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.05);
}

.default-template[style*="--background-color: #111827"] .post-card,
.default-template[style*="--background-color: #0f1419"] .post-card,
.default-template[style*="--background-color: #1e1b2e"] .post-card,
.default-template[style*="--background-color: #1c1917"] .post-card,
.default-template[style*="--background-color: #0f172a"] .post-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .site-nav {
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
        background: var(--background-color);
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
    }

    .posts-grid {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 2.25rem;
    }

    .section-subtitle {
        font-size: 1.125rem;
    }

    .page-section-wrapper {
        padding: 4rem 1.5rem;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }
}
</style>
