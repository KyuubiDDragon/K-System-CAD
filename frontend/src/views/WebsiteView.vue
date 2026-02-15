<template>
    <div class="website-view">
        <div v-if="loading && !previewMode" class="loading-container">
            <div class="spinner">
                <i class="mdi mdi-loading mdi-spin"></i>
            </div>
            <p>{{ $t('website.loading') }}</p>
        </div>

        <div v-else-if="error && !previewMode" class="error-container">
            <div class="error-icon">
                <i class="mdi mdi-alert-circle"></i>
            </div>
            <h2>{{ $t('website.loadError') }}</h2>
            <p>{{ error }}</p>
        </div>

        <div
            v-else-if="website.maintenance_mode"
            class="website-container maintenance-mode-container"
            :style="websiteStyles"
        >
            <div class="maintenance-content">
                <div v-if="website.logo" class="site-logo">
                    <img :src="getMediaUrl(website.logo)" :alt="website.site_name + ' Logo'" />
                </div>
                <h1>{{ website.site_name }}</h1>
                <div class="maintenance-icon">
                    <i class="mdi mdi-tools"></i>
                </div>
                <h2>{{ $t('website.maintenance') }}</h2>
                <p>
                    {{
                        website.maintenance_message ||
                        $t('website.maintenanceDefault')
                    }}
                </p>
            </div>
            <footer class="site-footer">
                <div class="footer-bottom">
                    <p>
                        {{
                            website.footer_text ||
                            '© ' + new Date().getFullYear() + ' ' + website.site_name
                        }}
                    </p>
                </div>
            </footer>
        </div>

        <!-- Template Router for non-default templates -->
        <TemplateRouter
            v-else-if="website.layout_template && website.layout_template !== 'default'"
            :website="website"
            :navigation-items="navigationItems"
            :pages="pages"
            :posts="posts"
            :categories="categories"
            :sections="sections"
            :news="news"
            :preview-mode="previewMode"
            :get-media-url="getMediaUrl"
        />

        <!-- Default Template (existing layout) -->
        <div v-else class="website-container website-preview-layout" :style="websiteStyles">
            <header class="site-header" :style="getBannerStyles()">
                <div v-if="website.logo" class="site-logo">
                    <img :src="getMediaUrl(website.logo)" :alt="website.site_name + ' Logo'" />
                </div>
                <div class="site-title">{{ website.site_name }}</div>
                <div class="site-slogan">{{ website.site_slogan }}</div>
                <nav class="site-nav">
                    <ul v-if="navigationItems && navigationItems.length > 0">
                        <li v-if="website.navbar_name">
                            <a
                                href="#"
                                :class="{ active: activeNavigationId === 'home' }"
                                @click.prevent="loadHome()"
                            >
                                {{ website.navbar_name }}
                            </a>
                        </li>

                        <li
                            v-for="item in navigationItems.filter(i => !i.parent_id)"
                            :key="item.id"
                            :class="{ 'has-submenu': getChildItems(item.id).length > 0 }"
                        >
                            <a
                                href="#"
                                :class="{
                                    active:
                                        activeNavigationId === item.id ||
                                        (activeNavigationId === 'single-post' &&
                                            currentPost &&
                                            currentPost.linked_navigation_id === item.id),
                                }"
                                @click.prevent="handleNavigationClick(item)"
                            >
                                {{ item.title }}
                            </a>
                            <ul v-if="getChildItems(item.id).length > 0" class="submenu">
                                <li v-for="child in getChildItems(item.id)" :key="child.id">
                                    <a
                                        href="#"
                                        :class="{ active: activeNavigationId === child.id }"
                                        @click.prevent="handleNavigationClick(child)"
                                    >
                                        {{ child.title }}
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li v-if="website.show_contact_form">
                            <a
                                href="#"
                                :class="{ active: activeNavigationId === 'contact' }"
                                @click.prevent="loadContact()"
                            >
                                {{ $t('website.contact') }}
                            </a>
                        </li>
                    </ul>
                    <div v-else-if="website.navbar_name" class="site-nav">
                        <ul>
                            <li>
                                <a
                                    href="#"
                                    :class="{ active: activeNavigationId === 'home' }"
                                    @click.prevent="loadHome()"
                                >
                                    {{ website.navbar_name }}
                                </a>
                            </li>
                            <li v-if="website.show_contact_form">
                                <a
                                    href="#"
                                    :class="{ active: activeNavigationId === 'contact' }"
                                    @click.prevent="loadContact()"
                                >
                                    {{ $t('website.contact') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="no-navigation-message">
                        {{ $t('website.noNavigation') }}
                    </div>
                </nav>
            </header>

            <main class="site-main">
                <div v-if="isBlockContent" class="block-content">
                    <div v-for="block in parsedBlocks" :key="block.id" class="block-wrapper">
                        <div v-if="block.type === 'text'" class="text-block">
                            <div v-html="block.content.text || ''"></div>
                        </div>

                        <div v-else-if="block.type === 'team'" class="team-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description" class="team-description">
                                {{ block.content.description }}
                            </p>

                            <div class="team-members">
                                <div
                                    v-for="(member, index) in block.content.members"
                                    :key="index"
                                    class="team-member"
                                >
                                    <div class="member-image">
                                        <img
                                            v-if="member.image"
                                            :src="getMediaUrl(member.image)"
                                            :alt="member.name"
                                        />
                                        <div
                                            v-else
                                            class="placeholder-image"
                                            :style="{
                                                backgroundColor: websiteStyles['--accent-color'],
                                            }"
                                        >
                                            {{ getInitials(member.name) }}
                                        </div>
                                    </div>
                                    <h3>{{ member.name }}</h3>
                                    <p class="member-position">{{ member.position }}</p>
                                    <p class="member-bio">{{ member.bio }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="block.type === 'columns'" class="columns-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <div
                                class="columns-container"
                                :class="`columns-${block.content.columnCount}`"
                            >
                                <div
                                    v-for="(column, index) in block.content.columns"
                                    :key="index"
                                    class="column"
                                >
                                    <h3 v-if="column.title">{{ column.title }}</h3>
                                    <div class="column-content" v-html="column.text"></div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="block.type === 'testimonials'" class="testimonials-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <div class="testimonials-container">
                                <div
                                    v-for="(item, index) in block.content.items"
                                    :key="index"
                                    class="testimonial"
                                >
                                    <div class="testimonial-quote">
                                        <blockquote>{{ item.quote }}</blockquote>
                                    </div>
                                    <div class="testimonial-author">
                                        <div class="author-image">
                                            <img
                                                v-if="item.image"
                                                :src="getMediaUrl(item.image)"
                                                :alt="item.name"
                                            />
                                            <div
                                                v-else
                                                class="placeholder-image"
                                                :style="{
                                                    backgroundColor:
                                                        websiteStyles['--accent-color'],
                                                }"
                                            >
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

                        <!-- FAQ Block -->
                        <div v-else-if="block.type === 'faq'" class="faq-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description || block.content.introduction" class="faq-description">
                                {{ block.content.description || block.content.introduction }}
                            </p>
                            
                            <div class="faq-items">
                                <div 
                                    v-for="(item, index) in block.content.items" 
                                    :key="index" 
                                    class="faq-item"
                                >
                                    <div class="faq-question">
                                        <h3>{{ item.question }}</h3>
                                        <span class="faq-toggle-icon">
                                            <i class="mdi mdi-plus"></i>
                                        </span>
                                    </div>
                                    <div class="faq-answer" style="display: none;" v-html="item.answer"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Gallery Block -->
                        <div v-else-if="block.type === 'gallery'" class="gallery-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description" class="gallery-description">
                                {{ block.content.description }}
                            </p>
                            
                            <div 
                                class="gallery-grid" 
                                :class="{ 
                                    'gallery-grid-2': block.content.columns === 2,
                                    'gallery-grid-3': block.content.columns === 3 || !block.content.columns,
                                    'gallery-grid-4': block.content.columns === 4
                                }"
                            >
                                <div 
                                    v-for="(image, index) in block.content.images" 
                                    :key="index" 
                                    class="gallery-item"
                                >
                                    <img 
                                        :src="getMediaUrl(image.url)" 
                                        :alt="image.caption || 'Gallery image'" 
                                    />
                                    <div v-if="image.caption" class="gallery-caption">
                                        {{ image.caption }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Call to Action Block -->
                        <div v-else-if="block.type === 'cta'" class="cta-block">
                            <div class="cta-container" :style="getCtaBlockStyles(block)">
                                <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                                <div v-if="block.content.text" class="cta-text" v-html="block.content.text"></div>
                                
                                <div class="cta-buttons">
                                    <a 
                                        v-if="block.content.primaryButton" 
                                        href="#"
                                        @click.prevent="handleCtaButtonClick(block.content.primaryButton)"
                                        class="cta-button primary-button"
                                        :style="getButtonStyles()" 
                                    >
                                        {{ block.content.primaryButton.text }}
                                    </a>
                                    
                                    <a 
                                        v-if="block.content.secondaryButton" 
                                        href="#"
                                        @click.prevent="handleCtaButtonClick(block.content.secondaryButton)"
                                        class="cta-button secondary-button"
                                    >
                                        {{ block.content.secondaryButton.text }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Video Block -->
                        <div v-else-if="block.type === 'video'" class="video-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <div v-if="block.content.description" class="video-description">
                                {{ block.content.description }}
                            </div>
                            
                            <div class="video-container">
                                <iframe 
                                    v-if="block.content.embedUrl"
                                    :src="block.content.embedUrl" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen
                                ></iframe>
                                <div v-else class="video-placeholder">
                                    <i class="mdi mdi-video-outline"></i>
                                    <p>{{ $t('website.videoUnavailable') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Feature Boxes Block -->
                        <div v-else-if="block.type === 'features'" class="features-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description" class="features-description">
                                {{ block.content.description }}
                            </p>
                            
                            <div class="features-container" :class="`features-columns-${block.content.columns || 3}`">
                                <div 
                                    v-for="(feature, index) in block.content.features" 
                                    :key="index" 
                                    class="feature-box"
                                    :style="getFeatureBoxStyle(feature)"
                                >
                                    <div class="feature-icon" v-if="feature.icon">
                                        <i :class="feature.icon"></i>
                                    </div>
                                    <h3 class="feature-title">{{ feature.title }}</h3>
                                    <div class="feature-text" v-html="feature.text"></div>
                                    
                                    <a 
                                        v-if="feature.linkText && feature.linkUrl" 
                                        @click.prevent="handleFeatureLink(feature)"
                                        href="#" 
                                        class="feature-link"
                                    >
                                        {{ feature.linkText }}
                                        <i class="mdi mdi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Block -->
                        <div v-else-if="block.type === 'statistics' || block.type === 'stats'" class="statistics-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description || block.content.subtitle" class="statistics-description">
                                {{ block.content.description || block.content.subtitle }}
                            </p>
                            
                            <div 
                                class="statistics-container"
                                :class="`statistics-columns-${block.content.columns || block.content.layout === 'grid' ? 4 : 2}`"
                            >
                                <div 
                                    v-for="(stat, index) in (block.content.statistics || block.content.items)" 
                                    :key="index" 
                                    class="statistic-item"
                                >
                                    <div class="statistic-icon" v-if="stat.icon">
                                        <i :class="stat.icon" :style="{ color: stat.iconColor || 'var(--primary-color)' }"></i>
                                    </div>
                                    <div class="statistic-value">{{ stat.value }}</div>
                                    <div class="statistic-label">{{ stat.label }}</div>
                                    <div v-if="stat.description" class="statistic-description">
                                        {{ stat.description }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Table Block -->
                        <div v-else-if="block.type === 'pricing'" class="pricing-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description" class="pricing-description">
                                {{ block.content.description }}
                            </p>
                            
                            <div class="pricing-table">
                                <div 
                                    v-for="(plan, index) in block.content.plans" 
                                    :key="index" 
                                    class="pricing-plan"
                                    :class="{ 'featured-plan': plan.featured }"
                                    :style="getPlanStyle(plan)"
                                >
                                    <div v-if="plan.featured" class="plan-badge">Empfohlen</div>
                                    <h3 class="plan-name">{{ plan.name }}</h3>
                                    <div class="plan-price">
                                        <span class="price-currency">{{ plan.currency || '€' }}</span>
                                        <span class="price-value">{{ plan.price }}</span>
                                        <span class="price-period">{{ plan.period }}</span>
                                    </div>
                                    <div class="plan-description" v-if="plan.description">
                                        {{ plan.description }}
                                    </div>
                                    <ul class="plan-features">
                                        <li 
                                            v-for="(feature, featureIndex) in plan.features" 
                                            :key="featureIndex"
                                            :class="{ 
                                                'feature-included': typeof feature === 'string' || feature.included !== false 
                                            }"
                                        >
                                            <i 
                                                :class="typeof feature === 'string' || feature.included !== false 
                                                    ? 'mdi mdi-check' 
                                                    : 'mdi mdi-close'" 
                                                class="feature-icon"
                                            ></i>
                                            {{ typeof feature === 'string' ? feature : feature.text }}
                                        </li>
                                    </ul>
                                    <a 
                                        v-if="plan.buttonText" 
                                        href="#"
                                        @click.prevent="handlePlanButtonClick(plan)"
                                        class="plan-button"
                                        :style="getPlanButtonStyle(plan)"
                                    >
                                        {{ plan.buttonText }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Countdown Block -->
                        <div v-else-if="block.type === 'countdown'" class="countdown-block">
                            <h2 v-if="block.content.title">{{ block.content.title }}</h2>
                            <p v-if="block.content.description" class="countdown-description">
                                {{ block.content.description }}
                            </p>
                            
                            <div class="countdown-container">
                                <div class="countdown-timer">
                                    <div class="countdown-unit">
                                        <div class="countdown-value">{{ countdownTime.days }}</div>
                                        <div class="countdown-label">Tage</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value">{{ countdownTime.hours }}</div>
                                        <div class="countdown-label">Stunden</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value">{{ countdownTime.minutes }}</div>
                                        <div class="countdown-label">Minuten</div>
                                    </div>
                                    <div class="countdown-unit">
                                        <div class="countdown-value">{{ countdownTime.seconds }}</div>
                                        <div class="countdown-label">Sekunden</div>
                                    </div>
                                </div>
                                
                                <div v-if="block.content.targetDate < new Date()" class="countdown-expired-message">
                                    {{ block.content.expiredMessage || 'Das Event hat bereits stattgefunden!' }}
                                </div>
                                
                                <div v-if="block.content.buttonText" class="countdown-action">
                                    <a 
                                        v-if="block.content.buttonText" 
                                        href="#"
                                        @click.prevent="handleCountdownButtonClick(block.content)"
                                        class="countdown-button"
                                        :style="getButtonStyles()"
                                    >
                                        {{ block.content.buttonText }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="activeNavigationId === 'blog-overview'"
                    class="blog-section"
                >
                    <header class="blog-header">
                        <h1>{{ getNavigationItemTitle('blog-overview') || $t('blog.title') }}</h1>
                        <p class="blog-description">{{ $t('blog.latestPosts') }}</p>
                    </header>

                    <div v-if="blogPosts.length > 0" class="blog-grid">
                        <article v-for="post in blogPosts" :key="post.id" class="blog-card">
                            <div
                                v-if="post.featured_image"
                                class="card-image"
                                :style="{
                                    backgroundImage:
                                        'url(' + getMediaUrl(post.featured_image) + ')',
                                }"
                            ></div>
                            <div v-else class="card-image card-image-placeholder">
                                <i class="mdi mdi-newspaper"></i>
                            </div>

                            <div class="card-content">
                                <div class="card-meta">
                                    <time
                                        :datetime="
                                            formatDate(post.published_at || post.created_at)
                                        "
                                    >
                                        <i class="mdi mdi-calendar"></i>
                                        {{
                                            formatDate(post.published_at || post.created_at)
                                        }}
                                    </time>
                                    <div class="card-categories">
                                        <span
                                            v-for="category in post.categories"
                                            :key="category.id"
                                            class="category-tag"
                                            :style="{
                                                backgroundColor:
                                                    category.color || '#007bff',
                                            }"
                                        >
                                            {{ category.name }}
                                        </span>
                                    </div>
                                </div>

                                <h3 class="card-title">{{ post.title }}</h3>
                                <div class="card-excerpt" v-if="post.excerpt">
                                    {{ post.excerpt }}
                                </div>
                                <div class="card-excerpt" v-else>
                                    {{ stripHtml(post.content).substring(0, 150) + '...' }}
                                </div>
                                <a
                                    @click.prevent="loadSinglePost(post.id)"
                                    href="#"
                                    class="read-more"
                                >
                                    Weiterlesen
                                </a>
                            </div>
                        </article>
                    </div>
                    <div v-else class="empty-blog">
                        <div class="empty-icon">
                            <i class="mdi mdi-newspaper"></i>
                        </div>
                        <h2>{{ getNavigationItemTitle('blog-overview') || $t('blog.title') }}</h2>
                        <p>{{ $t('blog.noPosts') }}</p>
                    </div>
                </div>

                <div
                    v-else-if="activeNavigationId === 'single-post' && currentPost"
                    class="single-post"
                >
                    <div class="post-header">
                        <h1 class="post-title">{{ currentPost.title }}</h1>

                        <div class="post-meta">
                            <time
                                :datetime="
                                    formatDate(
                                        currentPost.published_at || currentPost.created_at
                                    )
                                "
                            >
                                <i class="mdi mdi-calendar"></i>
                                {{
                                    formatDate(
                                        currentPost.published_at || currentPost.created_at
                                    )
                                }}
                            </time>
                            <div class="post-categories">
                                <span
                                    v-for="category in currentPost.categories"
                                    :key="category.id"
                                    class="category-tag"
                                    :style="{
                                        backgroundColor: category.color || '#007bff',
                                    }"
                                >
                                    {{ category.name }}
                                </span>
                            </div>
                        </div>

                        <div v-if="currentPost.featured_image" class="post-featured-image">
                            <img
                                :src="getMediaUrl(currentPost.featured_image)"
                                :alt="currentPost.title"
                            />
                        </div>
                    </div>

                    <div class="post-content" v-html="currentPost.content"></div>

                    <div class="post-footer">
                        <a
                            href="#"
                            class="back-button"
                            @click.prevent="goBackToBlogOverview"
                        >
                            <i class="mdi mdi-arrow-left"></i> Zurück zur Übersicht
                        </a>
                    </div>
                </div>
                <div
                    v-else-if="activeNavigationId === 'single-post' && !currentPost"
                    class="empty-content"
                >
                    <h2>Beitrag nicht gefunden</h2>
                    <p>Der angeforderte Blog-Beitrag konnte nicht geladen werden.</p>
                </div>

                <div
                    v-else-if="
                        activeNavigationId === 'contact' && 
                        website.show_contact_form
                    "
                    class="contact-form-section"
                >
                    <div class="contact-form-container">
                        <h2>{{ $t('website.contact') }}</h2>
                        <p>{{ $t('website.contactIntro') }}</p>

                        <form class="contact-form" @submit.prevent="submitContactForm">
                            <div
                                v-for="field in contactFormFields"
                                :key="field.id"
                                class="form-group"
                            >
                                <template
                                    v-if="field.type === 'text' || field.type === 'email'"
                                >
                                    <label :for="field.id"
                                        >{{ field.label
                                        }}{{ field.required ? ' *' : '' }}</label
                                    >
                                    <input
                                        :type="field.type"
                                        :id="field.id"
                                        :placeholder="field.placeholder"
                                        class="form-control"
                                        :required="field.required"
                                        v-model="contactForm[field.id]"
                                    />
                                </template>
                                <template v-else-if="field.type === 'textarea'">
                                    <label :for="field.id"
                                        >{{ field.label
                                        }}{{ field.required ? ' *' : '' }}</label
                                    >
                                    <textarea
                                        :id="field.id"
                                        :placeholder="field.placeholder"
                                        class="form-control"
                                        :required="field.required"
                                        rows="5"
                                        v-model="contactForm[field.id]"
                                    ></textarea>
                                </template>
                                <template v-else-if="field.type === 'select'">
                                    <label :for="field.id"
                                        >{{ field.label
                                        }}{{ field.required ? ' *' : '' }}</label
                                    >
                                    <select
                                        :id="field.id"
                                        class="form-control"
                                        :required="field.required"
                                        v-model="contactForm[field.id]"
                                    >
                                        <option value="" disabled selected>
                                            {{ field.placeholder || $t('blog.selectPlaceholder') }}
                                        </option>
                                        <option
                                            v-for="(option, index) in field.options"
                                            :key="index"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else-if="field.type === 'multiselect'">
                                    <label :for="field.id">{{ field.label }}{{ field.required ? ' *' : '' }}</label>
                                    <v-select
                                        :id="field.id"
                                        v-model="contactForm[field.id]"
                                        :items="field.options || []"
                                        :required="field.required"
                                        chips
                                        multiple
                                        variant="outlined"
                                        density="comfortable"
                                        :placeholder="field.placeholder || $t('blog.selectPlaceholder')"
                                        class="form-select"
                                    ></v-select>
                                    <small class="form-text">{{ $t('website.multiselectHint') }}</small>
                                </template>
                                <template v-else-if="field.type === 'checkbox'">
                                    <div class="checkbox-container">
                                        <input
                                            type="checkbox"
                                            :id="field.id"
                                            :required="field.required"
                                            v-model="contactForm[field.id]"
                                        />
                                        <label :for="field.id"
                                            >{{ field.label
                                            }}{{ field.required ? ' *' : '' }}</label
                                        >
                                    </div>
                                </template>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :style="getButtonStyles()"
                                    :disabled="submitting"
                                >
                                    <span v-if="submitting"
                                        ><i class="mdi mdi-loading mdi-spin"></i>
                                        {{ $t('website.sending') }}</span
                                    >
                                    <span v-else>{{ $t('website.sendMessage') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div
                    v-else-if="
                        activeNavigationId === 'contact' && 
                        !website.show_contact_form
                    "
                    class="empty-content"
                >
                    <h2>{{ $t('website.contact') }}</h2>
                    <p>{{ $t('website.contactDisabled') }}</p>
                </div>

                <div
                    v-else-if="
                        activeNavigationId === 'home' &&
                        selectedContent === null && 
                        !isBlockContent
                    "
                    class="home-content"
                >
                    <section class="hero-section" :style="getHeroStyles()">
                        <h1>{{ $t('website.welcome') }} {{ website.site_name }}</h1>
                        <div v-html="website.site_description"></div>

                        <div v-if="website.cta_text" class="hero-cta">
                            <a
                                href="#"
                                class="cta-button"
                                :style="getButtonStyles()"
                                @click.prevent="handleCtaClick"
                            >
                                {{ website.cta_text }}
                            </a>
                        </div>
                    </section>
                </div>

                <div
                    v-else-if="
                        selectedContent === '' ||
                        (selectedContent === null &&
                            !isBlockContent)
                    "
                >
                    <section class="empty-content">
                        <h2>
                            {{ getNavigationItemTitle(activeNavigationId) || 'Inhalt' }}
                        </h2>
                        <p>{{ $t('website.noContent') }}</p>
                    </section>
                </div>

                <div v-else v-html="selectedContent" class="dynamic-content"></div>

                <!-- Single Blog Post View -->
                <div v-if="activeNavigationId === 'blog-post' && selectedPost" class="blog-post-view">
                    <div class="blog-post-container">
                        <!-- Back to blog link -->
                        <div class="blog-post-nav">
                            <a @click.prevent="goBackToBlog" href="#" class="back-to-blog">
                                <i class="mdi mdi-arrow-left"></i> {{ $t('blog.backToOverview') }}
                            </a>
                        </div>
                        
                        <!-- Featured image -->
                        <div v-if="selectedPost.featured_image" class="blog-post-featured-image">
                            <img :src="getMediaUrl(selectedPost.featured_image)" :alt="selectedPost.title" />
                        </div>
                        
                        <!-- Post header -->
                        <div class="blog-post-header">
                            <h1 class="blog-post-title">{{ selectedPost.title }}</h1>
                            
                            <div class="blog-post-meta">
                                <div class="post-date">
                                    <i class="mdi mdi-calendar"></i>
                                    {{ formatDate(selectedPost.published_at || selectedPost.created_at) }}
                                </div>
                                
                                <div v-if="selectedPost.categories && selectedPost.categories.length" class="post-categories">
                                    <span v-for="category in selectedPost.categories" :key="category.id" 
                                          class="category-tag" 
                                          :style="{ backgroundColor: category.color || '#007bff' }">
                                        {{ category.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Post content -->
                        <div class="blog-post-content" v-html="selectedPost.content"></div>
                        
                        <!-- Tags if available -->
                        <div v-if="selectedPost.tags && selectedPost.tags.length" class="blog-post-tags">
                            <span class="tags-label">Tags:</span>
                            <span v-for="tag in selectedPost.tags" :key="tag.id" class="post-tag">
                                #{{ tag.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="site-footer">
                <div class="footer-bottom">
                    <p>
                        {{
                            website.footer_text ||
                            '© ' + new Date().getFullYear() + ' ' + website.site_name
                        }}
                    </p>
                </div>
            </footer>
        </div>

        <div v-if="debugMode" class="debug-panel">
            <h3>Debug Info</h3>
            <pre>ActiveNav: {{ activeNavigationId }}</pre>
            <pre>IsBlockContent: {{ isBlockContent }}</pre>
            <pre>HasContent: {{ !!selectedContent }}</pre>
            <pre>BlocksCount: {{ parsedBlocks.length }}</pre>
            <button @click="debugMode = false">Close Debug</button>
        </div>
    </div>
</template>

<script setup lang="ts">
// Props for preview mode
interface Props {
  previewMode?: boolean
  website?: any
  navigationItems?: any[]
  pages?: any[]
  posts?: any[]
  categories?: any[]
  sections?: any[]
  news?: any[]
  previewId?: string
  websiteId?: string
  getMediaUrl?: (filename: string) => string
}

import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientPublic } from '@/api'; // Use the public API client
import { useToast } from 'vue-toastification';
import i18n from '@/plugins/i18n';
import TemplateRouter from '@/components/website/templates/TemplateRouter.vue';

// Initialize the toast service
const toast = useToast();
const { t } = useI18n();

const props = withDefaults(defineProps<Props>(), {
  previewMode: false
});

const emit = defineEmits(['titleChanged', 'logoChanged']);

// Get the route and extract the website ID
const route = useRoute();
const websiteId = computed(() => {
    // In preview mode, use provided previewId or default
    if (props.previewMode) {
        return props.previewId || '1';
    }

    if (props.websiteId) {
        return props.websiteId;
    }
    
    // In normal mode, use route params
    const id = route.params.id;
    console.log('Extracted websiteId from route:', id);
    if (!id) {
        console.error('No websiteId found in route params!');
    }
    return id;
});

// Flag to check if the component is still mounted
let isComponentMounted = true; // Use a simple boolean variable

// State refs - use props values if in preview mode
const website = ref(props.previewMode ? props.website || {} : {});
const loading = ref(!props.previewMode);
const error = ref(null);
const navigationItems = ref(props.previewMode ? props.navigationItems || [] : []);
const activeNavigationId = ref('home');
const selectedContent = ref(null);
const parsedBlocks = ref([]);
const isBlockContent = ref(false);
const contactFormFields = ref([]);
const contactForm = ref({});
const submitting = ref(false);
const pages = ref(props.previewMode ? props.pages || [] : []);
const posts = ref(props.previewMode ? props.posts || [] : []);
const categories = ref(props.previewMode ? props.categories || [] : []);
const sections = ref(props.previewMode ? props.sections || [] : []);
const news = ref(props.previewMode ? props.news || [] : []);
const currentPost = ref(null);
const currentPage = ref(null);
const countdownInterval = ref(null);
const blogPosts = ref([]);
const debugMode = ref(false);

// Compute CSS variables based on website color scheme
const websiteStyles = computed(() => {
    const styles = {};
    
    if (!website.value || Object.keys(website.value).length === 0) {
        return styles; // Return empty object if no website data yet
    }
    
    // Use custom colors or selected scheme colors
    const colorSource = website.value.useCustomColors
        ? website.value.customColors || {}
        : getSelectedSchemeColors();
        
    // Set CSS variables for colors
    styles['--primary-color'] = colorSource.primary || website.value.primary_color || '#3b82f6';
    styles['--secondary-color'] = colorSource.secondary || website.value.secondary_color || '#1e3a8a';
    styles['--accent-color'] = colorSource.accent || website.value.accent_color || '#60a5fa';
    styles['--background-color'] = colorSource.background || website.value.background_color || '#ffffff';
    styles['--text-color'] = colorSource.text || website.value.text_color || '#333333';

    // Add background image if available
    if (website.value.background_image) {
        styles['background-image'] = `url('${getMediaUrl(website.value.background_image)}')`;
        styles['background-size'] = 'cover';
        styles['background-position'] = 'center';
        styles['background-attachment'] = 'fixed';
    }
    
    // Additional styles for specific elements
    styles['--banner-background'] = colorSource.bannerBackground || styles['--primary-color'];
    styles['--banner-text'] = colorSource.bannerText || 
        (isDarkColor(styles['--banner-background']) ? '#ffffff' : '#333333');
    
    styles['--hero-background'] = colorSource.heroBackground || 
        `rgba(${hexToRgb(styles['--secondary-color'])}, 0.8)`;
    styles['--hero-text'] = colorSource.heroText || 
        (isDarkColor(styles['--hero-background']) ? '#ffffff' : '#333333');
    
    styles['--button-background'] = colorSource.buttonBackground || styles['--accent-color'];
    styles['--button-text-color'] = colorSource.buttonText || 
        (isDarkColor(styles['--button-background']) ? '#ffffff' : '#333333');
    
    // Darker variant for hover effects
    styles['--darker-primary'] = darkenColor(styles['--primary-color'], 20);
    
    return styles;
});

// Helper function to darken a color (can be added to your existing helpers)
function darkenColor(hex, percent) {
    if (!hex || typeof hex !== 'string') return '#000000';
    
    // Convert hex to RGB
    let r = parseInt(hex.substring(1, 3), 16);
    let g = parseInt(hex.substring(3, 5), 16);
    let b = parseInt(hex.substring(5, 7), 16);
    
    // Darken by reducing RGB values
    r = Math.max(0, Math.floor(r * (1 - percent / 100)));
    g = Math.max(0, Math.floor(g * (1 - percent / 100)));
    b = Math.max(0, Math.floor(b * (1 - percent / 100)));
    
    // Convert back to hex
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

// Computed property for countdown timer
const countdownTime = computed(() => {
    // Default values
    const defaultTime = { days: 0, hours: 0, minutes: 0, seconds: 0 };
    
    // If no blocks or not in block mode, return default
    if (!isBlockContent.value || !parsedBlocks.value.length) {
        return defaultTime;
    }
    
    // Find countdown block
    const countdownBlock = parsedBlocks.value.find(block => block.type === 'countdown');
    if (!countdownBlock || !countdownBlock.content.targetDate) {
        return defaultTime;
    }
    
    // Calculate time difference
    const now = new Date();
    const targetDate = new Date(countdownBlock.content.targetDate);
    const difference = targetDate - now;
    
    // If the target date is in the past
    if (difference <= 0) {
        return defaultTime;
    }
    
    // Calculate time components
    const days = Math.floor(difference / (1000 * 60 * 60 * 24));
    const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((difference % (1000 * 60)) / 1000);
    
    return { days, hours, minutes, seconds };
});

// Helper Functions (copied/adapted from manager)
function getMediaUrl(path) {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    
    // Use VITE API URL for loading images
    const baseUrl = import.meta.env.VITE_API_URL || '';
    
    // In preview mode, construct URL differently
    if (props.previewMode) {
        // If path already has uploads/website/ just return with baseUrl
        if (path.startsWith('uploads/website/')) {
            return `${baseUrl}/${path}`;
        }
        
        // For newly uploaded files in the preview that might have temp paths
        if (path.startsWith('temp/')) {
            return `${baseUrl}/${path}`;
        }
    }
    
    // Construct the public URL path
    const authorityId = website.value?.authority_id || 'default';
    const siteId = websiteId.value;
    const fileName = path.split('/').pop();
    
    return `${baseUrl}/uploads/website/${authorityId}/${siteId}/${fileName}`;
}

function getInitials(name) {
    if (!name) return '';
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
}

function formatDate(dateString, includeTime = false) {
    if (!dateString) return '';

    const date = new Date(dateString);
    if (isNaN(date)) return ''; // Handle invalid dates

    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    };

    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }

    try {
        return date.toLocaleDateString('de-DE', options);
    } catch (e) {
        console.error('Date formatting error:', e);
        return dateString; // Return original if formatting fails
    }
}

function hexToRgb(hex) {
    if (!hex || typeof hex !== 'string') return '0, 0, 0';
    hex = hex.replace('#', '');

    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }

    if (hex.length !== 6) {
        return '0, 0, 0'; // Invalid hex
    }

    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    return `${r}, ${g}, ${b}`;
}

function isDarkColor(color) {
    if (!color || typeof color !== 'string') return false;

    let r, g, b;

    if (color.startsWith('#')) {
        color = color.substring(1);
        if (color.length === 3) {
            r = parseInt(color[0] + color[0], 16);
            g = parseInt(color[1] + color[1], 16);
            b = parseInt(color[2] + color[2], 16);
        } else if (color.length === 6) {
            r = parseInt(color.substring(0, 2), 16);
            g = parseInt(color.substring(2, 4), 16);
            b = parseInt(color.substring(4, 6), 16);
        } else {
            return false; // Invalid hex format
        }
    } else if (color.startsWith('rgb')) {
        const match = color.match(/(\d+),\s*(\d+),\s*(\d+)/);
        if (match) {
            r = parseInt(match[1]);
            g = parseInt(match[2]);
            b = parseInt(match[3]);
        } else {
            return false; // Invalid rgb format
        }
    } else {
        return false; // Unknown color format
    }

    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance < 0.5;
}

function getSelectedSchemeColors() {
    const schemeName = website.value.selected_scheme;
    // Define schemes including the extended properties
    const presetColorSchemes = [
        {
            name: 'Blau-Weiß',
            primary: '#3b82f6',
            secondary: '#60a5fa',
            accent: '#93c5fd',
            background: '#ffffff',
            text: '#111827',
            bannerBackground: '#3b82f6',
            bannerText: '#ffffff',
            heroBackground: 'rgba(96, 165, 250, 0.8)',
            heroText: '#ffffff',
            buttonBackground: '#60a5fa',
            buttonText: '#ffffff',
        },
        {
            name: 'Grün-Natur',
            primary: '#10b981',
            secondary: '#34d399',
            accent: '#6ee7b7',
            background: '#f9fafb',
            text: '#1f2937',
            bannerBackground: '#10b981',
            bannerText: '#ffffff',
            heroBackground: 'rgba(52, 211, 153, 0.8)',
            heroText: '#ffffff',
            buttonBackground: '#34d399',
            buttonText: '#1f2937',
        },
        {
            name: 'Warm-Orange',
            primary: '#f59e0b',
            secondary: '#fbbf24',
            accent: '#fcd34d',
            background: '#fffbeb',
            text: '#1f2937',
            bannerBackground: '#f59e0b',
            bannerText: '#ffffff',
            heroBackground: 'rgba(251, 191, 36, 0.8)',
            heroText: '#1f2937',
            buttonBackground: '#fbbf24',
            buttonText: '#1f2937',
        },
        {
            name: 'Elegant-Dunkel',
            primary: '#6366f1',
            secondary: '#818cf8',
            accent: '#a5b4fc',
            background: '#111827',
            text: '#f9fafb',
            bannerBackground: '#6366f1',
            bannerText: '#ffffff',
            heroBackground: 'rgba(129, 140, 248, 0.8)',
            heroText: '#ffffff',
            buttonBackground: '#818cf8',
            buttonText: '#ffffff',
        },
        // Add other schemes as needed, mapping old names if necessary
    ];
    return presetColorSchemes.find(s => s.name === schemeName) || {};
}

function getBannerStyles() {
    let styles = {};
    const schemeColors = website.value.useCustomColors
        ? website.value.customColors || {}
        : getSelectedSchemeColors();

    if (website.value.banner) {
        styles = {
            'background-image': `url('${getMediaUrl(website.value.banner)}')`,
            'background-size': 'cover',
            'background-position': 'center',
            'background-blend-mode': 'normal', // Ensure no blend mode by default for images
        };
        // If using a banner image, apply text color from scheme or custom colors
        if (schemeColors.bannerText) {
            styles['color'] = schemeColors.bannerText;
        }
    } else {
        // If no banner image, use background color from scheme or custom colors
        styles['background-color'] =
            schemeColors.bannerBackground ||
            schemeColors.primary ||
            website.value.primary_color ||
            '#3b82f6';
        styles['color'] =
            schemeColors.bannerText ||
            (isDarkColor(styles['background-color']) ? '#ffffff' : '#333333');
    }

    return styles;
}

function getHeroStyles() {
    let styles = {};
    const schemeColors = website.value.useCustomColors
        ? website.value.customColors || {}
        : getSelectedSchemeColors();

    if (website.value.hero_image) {
        styles = {
            'background-image': `url('${getMediaUrl(website.value.hero_image)}')`,
            'background-size': 'cover',
            'background-position': 'center',
            'background-color':
                schemeColors.heroBackground ||
                `rgba(${hexToRgb(schemeColors.secondary || '#1e3a8a')}, 0.8)`, // Default overlay color
            'background-blend-mode': 'multiply', // Overlay effect
            padding: '5rem 2rem',
        };
    } else {
        styles = {
            'background-color': website.value.secondary_color || '#1e3a8a', // Fallback or old style
            'background-blend-mode': 'normal',
            padding: '5rem 2rem',
        };
        // Use heroBackground from scheme if available, otherwise fallback
        if (schemeColors.heroBackground) {
            styles['background-color'] = schemeColors.heroBackground;
        }
    }

    styles['color'] =
        schemeColors.heroText || (isDarkColor(styles['background-color']) ? '#ffffff' : '#333333');

    return styles;
}

function getButtonStyles() {
    const schemeColors = website.value.useCustomColors
        ? website.value.customColors || {}
        : getSelectedSchemeColors();

    const bgColor =
        schemeColors.buttonBackground ||
        website.value.accent_color ||
        website.value.secondary_color ||
        '#60a5fa';
    const textColor = schemeColors.buttonText || (isDarkColor(bgColor) ? '#ffffff' : '#333333');

    return {
        backgroundColor: bgColor,
        color: textColor,
        border: 'none',
    };
}

function getCtaBlockStyles(block) {
    const styles = {};
    // If the block has a background image, use it
    if (block.content.backgroundImage) {
        styles.backgroundImage = `url('${getMediaUrl(block.content.backgroundImage)}')`;
        styles.backgroundSize = 'cover';
        styles.backgroundPosition = 'center';
    } else if (block.content.backgroundColor) {
        styles.backgroundColor = block.content.backgroundColor;
    }
    
    // Text color
    if (block.content.textColor) {
        styles.color = block.content.textColor;
    }
    
    return styles;
}

function getChildItems(parentId) {
    // Ensure navigationItems is an array before filtering
    return Array.isArray(navigationItems.value)
        ? navigationItems.value.filter(item => item.parent_id === parentId)
        : [];
}

function getNavigationItemTitle(id) {
    // Find item by its ID
    const item = navigationItems.value.find(navItem => navItem.id === id);
    if (item) return item.title;

    // Handle special cases
    if (id === 'home' && website.value.navbar_name) return website.value.navbar_name;
    if (id === 'contact') return i18n.global.t('website.contact');
    if (id === 'blog-overview') return i18n.global.t('blog.title');
    if (id === 'single-post' && currentPost.value) return currentPost.value.title;

    return null;
}

// Content Loading Methods
function clearContent() {
    selectedContent.value = null;
    isBlockContent.value = false;
    parsedBlocks.value = [];
    currentPage.value = null;
    currentPost.value = null;
    blogPosts.value = [];
}

function loadHome() {
    clearContent();
    activeNavigationId.value = 'home';
    // Make sure we're not in block mode to show the default home content
    isBlockContent.value = false;
    
    // If home is a navigation item with a page_id, try to load that content
    const homeNav = navigationItems.value.find(item => 
        item.id === 'home' || 
        (website.value.navbar_name && item.title === website.value.navbar_name)
    );
    
    if (homeNav && homeNav.page_id) {
        // If home is linked to a page, load that content
        const homePage = pages.value.find(p => p.id === homeNav.page_id);
        if (homePage) {
            currentPage.value = homePage;
            
            // Check if page uses blocks
            if (homePage.use_blocks && homePage.content) {
                isBlockContent.value = true;
                try {
                    parsedBlocks.value = JSON.parse(homePage.content);
                    setTimeout(() => {
                        initializeFaqHandlers();
                    }, 100);
                } catch (e) {
                    console.error('Error parsing blocks for home page:', e);
                    isBlockContent.value = false;
                }
            } else {
                // Regular content
                selectedContent.value = homePage.content || '';
            }
            return;
        }
    }
    
    // If no specific home page content, explicitly set state for default hero
    selectedContent.value = null;
    console.log('Loading default home hero section');
}

async function loadPage(item) {
    clearContent();
    activeNavigationId.value = item.id;
    console.log('Loading page for navigation item:', item.title, item);

    // If the navigation item links to an external URL, navigate there
    if (item.url && !item.page_id) {
        // Check if component is still mounted before navigating
        if (isComponentMounted) {
            window.open(item.url, item.target || '_self');
        }
        return; // Do not load content in the current view
    }

    if (item.page_id) {
        // Find the page from the already loaded pages
        const page = pages.value.find(p => p.id === item.page_id);

        // Check if component is still mounted before proceeding
        if (!isComponentMounted) return;

        if (page) {
            console.log('Found page content:', page.title, 'Uses blocks:', page.use_blocks);
            currentPage.value = page;

            // Check if page uses blocks
            if (page.use_blocks && page.content) {
                isBlockContent.value = true;
                try {
                    const parsedContent = JSON.parse(page.content);
                    if (Array.isArray(parsedContent) && parsedContent.length > 0) {
                        console.log('Valid block content detected, blocks:', parsedContent.length);
                        isBlockContent.value = true;
                        parsedBlocks.value = parsedContent;
                        
                        // Initialize FAQ handlers after a small delay to ensure DOM is updated
                        setTimeout(() => {
                            initializeFaqHandlers();
                        }, 100);
                    } else {
                        console.warn('Page has use_blocks=true but content is not a valid block array');
                        isBlockContent.value = false;
                        selectedContent.value = page.content || '';
                    }
                } catch (e) {
                    console.error('Error parsing blocks:', e);
                    selectedContent.value = `<div class="error-message">Fehler beim Laden des Block-Inhalts.</div>`;
                    isBlockContent.value = false;
                    parsedBlocks.value = [];
                }
            } else {
                // Regular content
                console.log('Loading regular content (not blocks)');
                selectedContent.value = page.content || '';
                isBlockContent.value = false;
                parsedBlocks.value = [];
            }
        } else {
            // Page linked in navigation not found in loaded pages
            console.warn('Page not found for navigation item:', item);
            selectedContent.value = `<div class="empty-content">${i18n.global.t('website.contentUnavailable', { title: item.title })}</div>`;
            isBlockContent.value = false;
        }
    } else {
        // No page linked but not an external URL either
        console.log('Navigation item has no page_id or URL:', item);
        selectedContent.value = `<div class="empty-content">
          <h2>${item.title}</h2>
          <p>${i18n.global.t('website.noContent')}</p>
        </div>`;
        isBlockContent.value = false;
    }
}

function loadContact() {
    clearContent();
    activeNavigationId.value = 'contact';
    // Explicitly set not block content
    isBlockContent.value = false;
    selectedContent.value = null;
    
    console.log('Loading contact form, contact form enabled:', website.value.show_contact_form);
    
    // Initialize contact form fields from website settings
    if (website.value.show_contact_form) {
        if (website.value.contact_form_fields) {
            try {
                const parsedFields = JSON.parse(website.value.contact_form_fields);
                if (Array.isArray(parsedFields) && parsedFields.length > 0) {
                    contactFormFields.value = parsedFields;
                } else {
                    setDefaultContactFields();
                }
            } catch (e) {
                console.error('Error parsing contact form fields:', e);
                setDefaultContactFields();
            }
        } else {
            setDefaultContactFields();
        }
        
        // Reset the form data
        contactForm.value = {};
        contactFormFields.value.forEach(field => {
            if (field.type === 'multiselect') {
                contactForm.value[field.id] = [];
            } else if (field.type === 'checkbox') {
                contactForm.value[field.id] = false;
            } else {
                contactForm.value[field.id] = '';
            }
        });
    }
}

function setDefaultContactFields() {
    contactFormFields.value = [
        {
            id: 'name',
            label: i18n.global.t('website.name'),
            type: 'text',
            placeholder: i18n.global.t('website.namePlaceholder'),
            required: true,
        },
        {
            id: 'email',
            label: i18n.global.t('website.email'),
            type: 'email',
            placeholder: i18n.global.t('website.emailPlaceholder'),
            required: true,
        },
        {
            id: 'message',
            label: i18n.global.t('website.message'),
            type: 'textarea',
            placeholder: i18n.global.t('website.messagePlaceholder'),
            required: true,
        },
    ];
}

function loadBlogOverview(item) {
    clearContent();
    activeNavigationId.value = 'blog-overview';
    console.log('Loading blog overview for item:', item);

    // Check if posts have been loaded
    if (posts.value.length === 0) {
        console.log('No blog posts available');
    }

    // Filter posts based on navigation item's categories
    let categoryIds = [];
    if (item && item.is_blog && item.blog_categories) {
        console.log('Blog item has categories configuration');
        
        // Parse blog categories from the item
        if (typeof item.blog_categories === 'string') {
            try {
                // Ensure parsing is safe
                const parsed = JSON.parse(item.blog_categories);
                if (Array.isArray(parsed)) {
                    categoryIds = parsed;
                    console.log('Parsed category IDs:', categoryIds);
                } else {
                    console.error('Parsed blog categories is not an array:', parsed);
                }
            } catch (e) {
                console.error('Error parsing blog categories:', e);
            }
        } else if (Array.isArray(item.blog_categories)) {
            categoryIds = item.blog_categories;
            console.log('Using array blog categories:', categoryIds);
        }
    }

    if (categoryIds.length > 0) {
        console.log('Filtering posts by categories:', categoryIds);
        blogPosts.value = posts.value.filter(post => {
            if (!post.categories) return false;
            const postCategoryIds = post.categories.map(c => c.id.toString());
            const navCategoryIds = categoryIds.map(id => id.toString());
            return postCategoryIds.some(id => navCategoryIds.includes(id));
        });
    } else {
        // If no categories specified or it's not a blog item with categories, show all posts
        console.log('Showing all available posts');
        blogPosts.value = [...posts.value];
    }

    // Sort blog posts by date, newest first
    blogPosts.value.sort((a, b) => {
        const dateA = new Date(a.published_at || a.created_at);
        const dateB = new Date(b.published_at || b.created_at);
        return dateB - dateA;
    });

    console.log('Loaded blog posts for overview:', blogPosts.value.length);
}

function loadSinglePost(postId) {
    console.log('Loading single post with ID:', postId);
    clearContent();
    activeNavigationId.value = 'single-post';

    const post = posts.value.find(p => p.id === postId);

    // Check if component is still mounted before updating state
    if (!isComponentMounted) return;

    if (post) {
        currentPost.value = post;
        console.log('Post found and loaded', post.title);
    } else {
        console.error('Single post not found with ID:', postId);
        selectedContent.value = `<div class="empty-content">
          <h2>Beitrag nicht gefunden</h2>
          <p>Der angeforderte Blog-Beitrag konnte nicht geladen werden.</p>
        </div>`;
        currentPost.value = null;
    }
}

function goBackToBlogOverview() {
    // Find the navigation item that is marked as a blog overview
    const blogNav = navigationItems.value.find(item => item.is_blog);
    if (blogNav) {
        loadBlogOverview(blogNav);
        // Set the active navigation ID back to the blog overview item
        activeNavigationId.value = blogNav.id; // Set ID to the blog nav item for active state in menu
    } else {
        // Fallback if no blog navigation item is found
        loadHome();
    }
}

// Function to handle navigation clicks
function handleNavigationClick(item) {
    console.log('Navigation clicked:', item);
    
    // Special handling for 'home'
    if (item.id === 'home') {
        loadHome();
        return;
    }
    
    // Special handling for 'contact'
    if (item.id === 'contact' || item.is_contact) {
        loadContact();
        return;
    }
    
    // Special handling for blog items
    if (item.is_blog) {
        console.log('Blog navigation item clicked');
        loadBlogOverview(item);
        return;
    }
    
    // For pages and other items
    loadPage(item);
}

async function submitContactForm() {
    submitting.value = true;
    error.value = null; // Clear previous errors

    try {
        // Basic Validation
        for (const field of contactFormFields.value) {
            // Check if component is still mounted before checking form fields
            if (!isComponentMounted) return;

            if (field.required) {
                const value = contactForm.value[field.id];
                if (field.type === 'checkbox') {
                    if (!value) {
                        toast.error(`Bitte bestätigen Sie "${field.label}".`);
                        submitting.value = false;
                        return;
                    }
                } else if (field.type === 'multiselect') {
                    if (!value || !Array.isArray(value) || value.length === 0) {
                        toast.error(`Bitte wählen Sie mindestens eine Option für "${field.label}" aus.`);
                        submitting.value = false;
                        return;
                    }
                } else if (!value || value.toString().trim() === '') {
                    toast.error(`Bitte füllen Sie das Feld "${field.label}" aus.`);
                    submitting.value = false;
                    return;
                }
            }
        }

        // Add extra information to the form data
        const formDataToSubmit = {
            ...contactForm.value,
            _websiteId: websiteId.value,
            _siteName: website.value.site_name,
            _formSubmittedAt: new Date().toISOString()
        };

        // Submit the form
        const response = await apiClientPublic.post('/company/website/?action=submitContactForm', {
            websiteId: websiteId.value,
            formData: formDataToSubmit,
        });

        // Check if component is still mounted before updating state
        if (!isComponentMounted) return;

        if (response.data && response.data.success) {
            // Show success message
            const messageContainer = document.createElement('div');
            messageContainer.className = 'form-success-message';
            messageContainer.innerHTML = `
                <div class="success-icon"><i class="mdi mdi-check-circle"></i></div>
                <h3>Vielen Dank für Ihre Nachricht!</h3>
                <p>Wir werden uns in Kürze bei Ihnen melden.</p>
            `;
            
            // Replace form with success message
            const formElement = document.querySelector('.contact-form');
            if (formElement && formElement.parentNode) {
                formElement.parentNode.replaceChild(messageContainer, formElement);
            } else {
                // Fallback if DOM manipulation fails
                toast.success('Vielen Dank für Ihre Nachricht! Wir werden uns in Kürze bei Ihnen melden.');
                contactForm.value = {}; // Reset the form
            }
        } else {
            throw new Error(response.data?.error || 'Es ist ein Fehler aufgetreten.');
        }
    } catch (err) {
        console.error('Error submitting form:', err);
        // Check if component is still mounted before updating state
        if (isComponentMounted) {
            error.value = err.message || 'Fehler beim Senden des Formulars.'; // Set error state
            toast.error('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.');
        }
    } finally {
        // Check if component is still mounted before updating state
        if (isComponentMounted) {
            submitting.value = false;
        }
    }
}

// Initial data loading
async function loadWebsite() {
    console.log('loadWebsite - preview mode:', props.previewMode);
    
    // In preview mode, just use the props directly
    if (props.previewMode) {
        console.log('Using provided preview data');
        
        // Don't need to load data in preview mode as it's passed via props
        loading.value = false;
        
        // If website is in maintenance mode, nothing else to do
        if (website.value.maintenance_mode) {
            return;
        }

        // Process pages block content for special blocks (like FAQ)
        processPageBlockContent();
        
        // Load home page as default view in preview
        loadHome();
        return;
    }
    
    // Normal mode - load from API
    loading.value = true;
    error.value = null;
    clearContent();
    activeNavigationId.value = 'home';

    try {
        // Determine which website ID to use
        // 1. From props.websiteId (WaterDuck browser)
        // 2. From route params (normal viewing)
        const websiteIdToUse = props.websiteId || websiteId.value;
        
        console.log('Starting API requests for website:', websiteIdToUse);
        // Load all necessary public data
        const requests = [
            apiClientPublic.get(`/company/website/?action=getWebsiteDetails&websiteId=${websiteIdToUse}`),
            apiClientPublic.get(`/company/website/?action=getNavigation&websiteId=${websiteIdToUse}`),
            apiClientPublic.get(`/company/website/?action=getPages&websiteId=${websiteIdToUse}`),
            apiClientPublic.get(`/company/website/?action=getPosts&websiteId=${websiteIdToUse}`),
            apiClientPublic.get(`/company/website/?action=getCategories&websiteId=${websiteIdToUse}`)
        ];
        
        console.log('Making parallel API requests');
        const [websiteResponse, navResponse, pagesResponse, postsResponse, categoriesResponse] = 
            await Promise.all(requests);
        
        console.log('All API requests completed');

        // Check if component is still mounted before processing results
        if (!isComponentMounted) {
            console.log('Component unmounted, stopping processing');
            return;
        }

        // Process website details
        console.log('Processing website response:', websiteResponse);
        if (!websiteResponse.data.success) {
            throw new Error(websiteResponse.data.error || 'Fehler beim Laden der Website-Details');
        }

        // Extract all website data
        const websiteData = websiteResponse.data.data || websiteResponse.data;
        website.value = websiteData.website;
        sections.value = websiteData.sections || [];
        news.value = websiteData.news || [];
        console.log('Website data loaded:', website.value.site_name);
        console.log('Sections loaded:', sections.value.length);
        console.log('News loaded:', news.value.length);
        
        // Emit the title changed event for WaterDuck browser
        if (props.isInBrowser && website.value) {
            emit('titleChanged', website.value.site_name);
            // Also emit the logo if available
            if (website.value.logo) {
                emit('logoChanged', website.value.logo);
            }
        }

        // If website is not active and not in maintenance mode, show error
        if (!website.value.is_active && !website.value.maintenance_mode) {
            error.value = 'Diese Website ist derzeit nicht aktiv.';
            loading.value = false;
            return;
        }
        // If in maintenance mode, the template will handle it
        if (website.value.maintenance_mode) {
            loading.value = false;
            return;
        }

        // Process navigation
        if (navResponse.data.success) {
            navigationItems.value = navResponse.data.navigation || [];
            // Sort navigation items - simple sort by sort_order for display
            navigationItems.value.sort((a, b) => a.sort_order - b.sort_order);
        } else {
            navigationItems.value = [];
            console.error('Failed to load navigation:', navResponse.data.error);
        }

        // Process pages
        if (pagesResponse.data.success) {
            pages.value = pagesResponse.data.pages || [];
            
            // Process page block content for special blocks (like FAQ)
            pages.value.forEach(page => {
                if (page.use_blocks && page.content) {
                    try {
                        const blocks = JSON.parse(page.content);
                        
                        // Process blocks
                        blocks.forEach(block => {
                            // Initialize FAQ items with isOpen property
                            if (block.type === 'faq' && block.content && block.content.items) {
                                block.content.items.forEach(item => {
                                    // Ensure we're using Vue's reactivity by maintaining the object structure
                                    if (!('isOpen' in item)) {
                                        item.isOpen = false; // Initialize all FAQ items as closed
                                    }
                                });
                            }
                            
                            // Ensure MDI icons have the double prefix
                            if (block.type === 'features' && block.content && block.content.features) {
                                block.content.features.forEach(feature => {
                                    if (feature.icon && !feature.icon.startsWith('mdi mdi-')) {
                                        // If icon already has one 'mdi-' prefix but not two, add another mdi
                                        if (feature.icon.startsWith('mdi-')) {
                                            feature.icon = 'mdi ' + feature.icon;
                                        } 
                                        // If icon doesn't have any mdi prefix, add the full prefix
                                        else if (!feature.icon.includes('mdi-')) {
                                            feature.icon = 'mdi mdi-' + feature.icon;
                                        }
                                    }
                                });
                            }
                            
                            // Fix stats block icons
                            if ((block.type === 'stats' || block.type === 'statistics') && 
                                 block.content && (block.content.items || block.content.statistics)) {
                                const items = block.content.items || block.content.statistics;
                                items.forEach(item => {
                                    if (item.icon && !item.icon.startsWith('mdi mdi-')) {
                                        // If icon already has one 'mdi-' prefix but not two, add another mdi
                                        if (item.icon.startsWith('mdi-')) {
                                            item.icon = 'mdi ' + item.icon;
                                        } 
                                        // If icon doesn't have any mdi prefix, add the full prefix
                                        else if (!item.icon.includes('mdi-')) {
                                            item.icon = 'mdi mdi-' + item.icon;
                                        }
                                    }
                                });
                            }
                        });
                        
                        // Save the processed blocks back to the page content
                        page.content = JSON.stringify(blocks);
                    } catch (e) {
                        console.error('Error processing page blocks:', e);
                    }
                }
                
                // Add a linked_navigation_id to pages for easy lookup from posts
                const navItem = navigationItems.value.find(item => item.page_id === page.id);
                if (navItem) page.linked_navigation_id = navItem.id;
            });
        } else {
            pages.value = [];
            console.error('Failed to load pages:', pagesResponse.data.error);
        }

        // Process posts
        if (postsResponse.data.success) {
            posts.value = postsResponse.data.posts || [];
        } else {
            posts.value = [];
            console.error('Failed to load posts:', postsResponse.data.error);
        }

        // Process categories
        if (categoriesResponse.data.success) {
            categories.value = categoriesResponse.data.categories || [];
            // Map categories to posts for easy access in template
            posts.value.forEach(post => {
                if (post.categories && Array.isArray(post.categories)) {
                    post.categories = post.categories
                        .map(postCat => {
                            return categories.value.find(cat => cat.id === postCat.id) || postCat; // Find full category object
                        })
                        .filter(cat => cat !== undefined);
                } else {
                    post.categories = [];
                }
            });
        } else {
            categories.value = [];
            console.error('Failed to load categories:', categoriesResponse.data.error);
        }

        // Load contact form fields if enabled
        if (website.value.show_contact_form && website.value.contact_form_fields) {
            try {
                // Ensure parsing is safe
                const parsedFields = JSON.parse(website.value.contact_form_fields);
                if (Array.isArray(parsedFields)) {
                    contactFormFields.value = parsedFields;
                    // Initialize contact form data
                    contactForm.value = {};
                    contactFormFields.value.forEach(field => {
                        if (field.type === 'multiselect') {
                            contactForm.value[field.id] = [];
                        } else if (field.type === 'checkbox') {
                            contactForm.value[field.id] = false;
                        } else {
                            contactForm.value[field.id] = '';
                        }
                    });
                } else {
                    console.error('Parsed contact form fields is not an array:', parsedFields);
                    // Fallback to default fields if parsing fails or it's not an array
                    contactFormFields.value = [
                        {
                            id: 'name',
                            label: 'Name',
                            type: 'text',
                            placeholder: 'Ihr Name',
                            required: true,
                        },
                        {
                            id: 'email',
                            label: 'E-Mail',
                            type: 'email',
                            placeholder: 'Ihre E-Mail-Adresse',
                            required: true,
                        },
                        {
                            id: 'message',
                            label: 'Nachricht',
                            type: 'textarea',
                            placeholder: 'Ihre Nachricht an uns',
                            required: true,
                        },
                    ];
                }
            } catch (e) {
                console.error('Error parsing contact form fields:', e);
                // Fallback to default fields if parsing fails
                contactFormFields.value = [
                    {
                        id: 'name',
                        label: 'Name',
                        type: 'text',
                        placeholder: 'Ihr Name',
                        required: true,
                    },
                    {
                        id: 'email',
                        label: 'E-Mail',
                        type: 'email',
                        placeholder: 'Ihre E-Mail-Adresse',
                        required: true,
                    },
                    {
                        id: 'message',
                        label: 'Nachricht',
                        type: 'textarea',
                        placeholder: 'Ihre Nachricht an uns',
                        required: true,
                    },
                ];
            }
        } else {
            contactFormFields.value = [];
            contactForm.value = {};
        }

        // Determine initial content to display based on URL or default
        const path = route.path;
        // Example: /website/123 -> path /website/123
        // Example: /website/123/about -> path /website/123/about
        // Example: /website/123/blog/post-slug -> path /website/123/blog/post-slug

        const pathSegments = path.split('/').filter(segment => segment !== '');
        // expected: ['website', '123', 'about'] or ['website', '123', 'blog', 'post-slug']

        // Determine the slug based on the path structure
        let targetSlug = null;
        if (pathSegments.length > 2) {
            targetSlug = pathSegments.slice(2).join('/'); // e.g., 'about' or 'blog/post-slug'
        }

        if (targetSlug) {
            console.log('Attempting to load content for slug:', targetSlug);
            // Check if it matches a page slug
            const targetPage = pages.value.find(p => p.slug === targetSlug);
            if (targetPage) {
                const navItem = navigationItems.value.find(item => item.page_id === targetPage.id);
                if (navItem) {
                    handleNavigationClick(navItem); // Load the linked page content
                } else {
                    // If a page exists with the slug but no corresponding navigation item, still try to load it
                    // Create a dummy navigation item to pass to loadPage
                    const dummyNavItem = {
                        id: 'page-' + targetPage.id,
                        page_id: targetPage.id,
                        title: targetPage.title,
                    };
                    await loadPage(dummyNavItem); // Load page content
                }
            } else if (targetSlug.startsWith('blog/')) {
                // Check if it matches a single blog post slug (e.g., 'blog/my-post')
                const postSlug = targetSlug.substring(5); // Remove 'blog/'
                const targetPost = posts.value.find(p => p.slug === postSlug);
                if (targetPost) {
                    loadSinglePost(targetPost.id); // Load the single post content
                } else {
                    // If slug is under /blog but post not found
                    console.warn('Blog post not found for slug:', postSlug);
                    selectedContent.value = `<div class="empty-content">Beitrag "${postSlug}" nicht gefunden.</div>`;
                    activeNavigationId.value = 'not-found';
                }
            } else if (targetSlug === 'blog') {
                // Check if a navigation item is marked as blog overview
                const blogNav = navigationItems.value.find(item => item.is_blog);
                if (blogNav) {
                    console.log('Match found for blog overview slug');
                    handleNavigationClick(blogNav); // Load the blog overview
                } else {
                    // If slug is 'blog' but no blog nav item exists
                    console.warn('Blog slug requested but no blog navigation item found.');
                    selectedContent.value = `<div class="empty-content">Blog-Übersicht nicht konfiguriert.</div>`;
                    activeNavigationId.value = 'not-found';
                }
            } else if (targetSlug === 'contact' && website.value.show_contact_form) {
                // Load contact form if the slug is 'contact' and the form is enabled
                console.log('Match found for contact slug');
                loadContact();
            } else {
                // Slug doesn't match any known page or blog structure, load home
                console.warn(
                    'Slug did not match any page, blog post, or special route:',
                    targetSlug
                );
                loadHome();
            }
        } else {
            // No specific slug in the URL, load home page
            console.log('No slug in path, loading home.');
            loadHome();
        }
    } catch (err) {
        console.error('Error loading website:', err);
        // Check if component is still mounted before updating state
        if (isComponentMounted) {
            error.value = err.message || 'Ein Fehler ist aufgetreten.';
            // Log detailed error information for debugging
            if (err.response) {
                // The request was made and the server responded with a status code outside the 2xx range
                console.error('API Error Response:', err.response.status, err.response.data);
            } else if (err.request) {
                // The request was made but no response was received
                console.error('No API response received:', err.request);
            } else {
                // Something happened in setting up the request that triggered an Error
                console.error('API Request Error:', err.message);
            }
        }
    } finally {
        // Check if component is still mounted before updating state
        if (isComponentMounted) {
            loading.value = false;
            console.log('Website loading complete or failed');
        }
    }

    // Also add a console log at the end of the function
    // Final state check log
    console.log('Website loading completed. Final state:', {
        activeNav: activeNavigationId.value,
        isBlockContent: isBlockContent.value,
        hasSelectedContent: !!selectedContent.value,
        blocksCount: parsedBlocks.value.length,
        loading: loading.value,
        error: error.value
    });
}

// Add a helper function to process page blocks - extracted from loadWebsite
function processPageBlockContent() {
    // Process page block content for special blocks (like FAQ)
    pages.value.forEach(page => {
        if (page.use_blocks && page.content) {
            try {
                const blocks = JSON.parse(page.content);
                
                // Process blocks
                blocks.forEach(block => {
                    // Initialize FAQ items with isOpen property
                    if (block.type === 'faq' && block.content && block.content.items) {
                        block.content.items.forEach(item => {
                            // Ensure we're using Vue's reactivity by maintaining the object structure
                            if (!('isOpen' in item)) {
                                item.isOpen = false; // Initialize all FAQ items as closed
                            }
                        });
                    }
                    
                    // Ensure MDI icons have the double prefix
                    if (block.type === 'features' && block.content && block.content.features) {
                        block.content.features.forEach(feature => {
                            if (feature.icon && !feature.icon.startsWith('mdi mdi-')) {
                                // If icon already has one 'mdi-' prefix but not two, add another mdi
                                if (feature.icon.startsWith('mdi-')) {
                                    feature.icon = 'mdi ' + feature.icon;
                                } 
                                // If icon doesn't have any mdi prefix, add the full prefix
                                else if (!feature.icon.includes('mdi-')) {
                                    feature.icon = 'mdi mdi-' + feature.icon;
                                }
                            }
                        });
                    }
                    
                    // Fix stats block icons
                    if ((block.type === 'stats' || block.type === 'statistics') && 
                         block.content && (block.content.items || block.content.statistics)) {
                        const items = block.content.items || block.content.statistics;
                        items.forEach(item => {
                            if (item.icon && !item.icon.startsWith('mdi mdi-')) {
                                // If icon already has one 'mdi-' prefix but not two, add another mdi
                                if (item.icon.startsWith('mdi-')) {
                                    item.icon = 'mdi ' + item.icon;
                                } 
                                // If icon doesn't have any mdi prefix, add the full prefix
                                else if (!item.icon.includes('mdi-')) {
                                    item.icon = 'mdi mdi-' + item.icon;
                                }
                            }
                        });
                    }
                });
                
                // Save the processed blocks back to the page content
                page.content = JSON.stringify(blocks);
            } catch (e) {
                console.error('Error processing page blocks:', e);
            }
        }
        
        // Add a linked_navigation_id to pages for easy lookup from posts
        const navItem = navigationItems.value.find(item => item.page_id === page.id);
        if (navItem) page.linked_navigation_id = navItem.id;
    });
    
    // Process categories and posts
    if (posts.value.length > 0 && categories.value.length > 0) {
        // Map categories to posts for easy access in template
        posts.value.forEach(post => {
            if (post.categories && Array.isArray(post.categories)) {
                post.categories = post.categories
                    .map(postCat => {
                        return categories.value.find(cat => cat.id === postCat.id) || postCat; // Find full category object
                    })
                    .filter(cat => cat !== undefined);
            } else {
                post.categories = [];
            }
        });
    }
}

// Watch for route changes to load content
watch(route, (newRoute, oldRoute) => {
    // Only react to changes if the website ID is the same
    if (newRoute.params.id === oldRoute.params.id) {
        const oldPathSegments = oldRoute.path
            .split('/')
            .filter(segment => segment !== '')
            .slice(2);
        const newPathSegments = newRoute.path
            .split('/')
            .filter(segment => segment !== '')
            .slice(2);
        const oldSlug = oldPathSegments.join('/');
        const newSlug = newPathSegments.join('/');

        // Only reload content if the slug part of the URL changes AND the website data is loaded
        // Adding the website.value check prevents trying to handle slug changes
        // before initial website data is available.
        if (oldSlug !== newSlug && website.value && Object.keys(website.value).length > 0) {
            console.log('Route slug changed from', oldSlug, 'to', newSlug, '- loading content');
            // Find the corresponding navigation item or determine special content
            let targetItem = null;
            if (newSlug) {
                targetItem = navigationItems.value.find(item => {
                    // Check by URL
                    if (item.url === '/' + newSlug) return true;
                    // Check by linked page slug
                    const linkedPage = pages.value.find(p => p.id === item.page_id);
                    if (linkedPage && linkedPage.slug === newSlug) return true;
                    // Check for blog overview at /blog
                    if (item.is_blog && newSlug === 'blog') return true;
                    // Check for contact page at /contact
                    if (item.is_contact && newSlug === 'contact') return true;

                    return false;
                });

                // Handle single blog posts (slug will be like 'blog/post-slug')
                if (newSlug.startsWith('blog/') && !targetItem) {
                    const postSlug = newSlug.substring(5);
                    const targetPost = posts.value.find(p => p.slug === postSlug);
                    if (targetPost) {
                        console.log('Match found for single post:', targetPost.title);
                        loadSinglePost(targetPost.id);
                        return; // Stop here, content loaded
                    }
                }
                // Handle contact page specifically if it doesn't have a navigation item but the slug is 'contact'
                if (newSlug === 'contact' && website.value.show_contact_form && !targetItem) {
                    console.log('Match found for contact slug without specific nav item');
                    loadContact();
                    return;
                }
            }

            // Load content based on targetItem or fallback
            if (targetItem) {
                console.log('Match found for navigation item:', targetItem.title);
                handleNavigationClick(targetItem);
            } else if (newSlug === null || newSlug === '') {
                // Root path for this website, load home
                console.log('Root path, loading home.');
                loadHome();
            } else {
                // Slug provided but no matching nav item or content found, show error/fallback
                console.warn('Slug did not match any content:', newSlug);
                clearContent();
                activeNavigationId.value = 'not-found';
                selectedContent.value = `<div class="empty-content">Seite "${newSlug}" nicht gefunden.</div>`;
            }
        } else if (newRoute.params.id !== oldRoute.params.id) {
            // If the website ID *does* change, reload the entire website data
            console.log('Website ID changed, reloading website:', newRoute.params.id);
            loadWebsite();
        } else {
            console.log(
                'Route change but no slug or website ID change, or website data not yet loaded.'
            );
        }
    }
});

// Add after the existing watch for route changes
// Watch for changes in key state variables to help debug content visibility
watch(activeNavigationId, (newId) => {
    console.log('Active navigation changed to:', newId);
});

watch(isBlockContent, (newValue) => {
    console.log('isBlockContent changed to:', newValue, 'parsedBlocks length:', parsedBlocks.value.length);
});

watch(selectedContent, (newContent) => {
    console.log('selectedContent changed:', newContent ? 'has content' : 'null or empty');
});

// Lifecycle
onMounted(() => {
    console.log('WebsiteView component mounted, preview mode:', props.previewMode);
    // Add event listener for single post clicks within blog overview
    window.addEventListener('loadSinglePost', handleLoadSinglePostEvent);
    
    // Make sure we have a valid websiteId before loading data if not in preview mode
    if (!props.previewMode) {
        if (websiteId.value) {
            console.log('Valid websiteId found, loading website data');
            loadWebsite();
        } else {
            console.error('No valid websiteId found in route params!');
            error.value = 'Keine gültige Website-ID gefunden.';
            loading.value = false;
        }
    } else {
        // In preview mode, just process the data and initialize the view
        console.log('Preview mode, initializing with provided data');
        processPageBlockContent();
        loadHome();
    }
    
    // Start countdown timer if needed
    startCountdownTimer();
    
    // Add FAQ handlers with a short delay to ensure DOM is ready
    setTimeout(() => {
        initializeFaqHandlers();
    }, 1000);

    // Add keyboard shortcut for debug mode: Ctrl+Alt+D
    window.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.altKey && e.key === 'd') {
            debugMode.value = !debugMode.value;
            console.log('Debug mode toggled:', debugMode.value);
        }
    });
});

// Watch for changes to the props - important for preview mode
watch(() => props.website, (newValue) => {
    if (props.previewMode && newValue) {
        console.log('Preview website data changed');
        website.value = newValue;
        // Reprocess and reload home when preview data changes
        processPageBlockContent();
        loadHome();
    }
}, { deep: true });

watch(() => props.navigationItems, (newValue) => {
    if (props.previewMode && newValue) {
        console.log('Preview navigation items changed');
        navigationItems.value = newValue;
    }
}, { deep: true });

watch(() => props.pages, (newValue) => {
    if (props.previewMode && newValue) {
        console.log('Preview pages changed');
        pages.value = newValue;
        processPageBlockContent();
    }
}, { deep: true });

watch(() => props.posts, (newValue) => {
    if (props.previewMode && newValue) {
        console.log('Preview posts changed');
        posts.value = newValue;
        processPageBlockContent();
    }
}, { deep: true });

watch(() => props.categories, (newValue) => {
    if (props.previewMode && newValue) {
        console.log('Preview categories changed');
        categories.value = newValue;
        processPageBlockContent();
    }
}, { deep: true });

// Function to initialize FAQ click handlers
function initializeFaqHandlers() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    console.log('Found FAQ questions:', faqQuestions.length);
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            console.log('FAQ item clicked');
            // Find the next sibling which should be the answer
            const answer = this.nextElementSibling;
            if (answer && answer.classList.contains('faq-answer')) {
                // Toggle display
                if (answer.style.display === 'block') {
                    answer.style.display = 'none';
                    // Update icon
                    const icon = this.querySelector('.faq-toggle-icon i');
                    if (icon) {
                        icon.className = 'mdi mdi-plus';
                    }
                } else {
                    answer.style.display = 'block';
                    // Update icon
                    const icon = this.querySelector('.faq-toggle-icon i');
                    if (icon) {
                        icon.className = 'mdi mdi-minus';
                    }
                }
            }
        });
    });
}

// Clean up event listener and intervals on unmounted
onUnmounted(() => {
    console.log('WebsiteView component unmounting');
    // This hook is crucial for the function of the component, 
    // even if we don't interact with the DOM.
    window.removeEventListener('loadSinglePost', handleLoadSinglePostEvent);
    isComponentMounted = false;
    
    // Clear countdown timer if active
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
        countdownInterval.value = null;
    }

    // Remove keyboard shortcut for debug mode: Ctrl+Alt+D
    window.removeEventListener('keydown', (e) => {
        if (e.ctrlKey && e.altKey && e.key === 'd') {
            debugMode.value = !debugMode.value;
        }
    });
});

// Start countdown timer for countdown blocks
function startCountdownTimer() {
    // Clear any existing interval first
    if (countdownInterval.value) {
        clearInterval(countdownInterval.value);
    }
    
    // Set up a 1-second interval to update the countdown
    countdownInterval.value = setInterval(() => {
        // This will trigger recalculation of the countdownTime computed property
        // We just need to force a reactivity update
        const now = new Date();
        // No need to update any ref, just triggering the function is enough
    }, 1000);
}

// Event handler for custom event (simulating clicks on blog overview)
function handleLoadSinglePostEvent(event) {
    // Check if component is still mounted before processing event
    if (!isComponentMounted) {
        console.warn('Received event on unmounted component.');
        return;
    }
    if (event.detail && event.detail.postId) {
        loadSinglePost(event.detail.postId);
    }
}

// Function to generate styles for feature boxes
function getFeatureBoxStyle(feature) {
    const styles = {};
    
    // Apply custom background color if provided
    if (feature.backgroundColor) {
        styles.backgroundColor = feature.backgroundColor;
    }
    
    // Apply custom border if provided
    if (feature.borderColor) {
        styles.borderColor = feature.borderColor;
        styles.borderWidth = '1px';
        styles.borderStyle = 'solid';
    }
    
    // Apply custom text color if provided
    if (feature.textColor) {
        styles.color = feature.textColor;
    }
    
    // Special styling for highlighted features
    if (feature.isHighlighted) {
        styles.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.15)';
        styles.transform = 'translateY(-5px)';
        
        if (!feature.backgroundColor) {
            // Use primary color as background for highlighted features without specified bg
            styles.backgroundColor = 'var(--primary-color)';
            styles.color = isDarkColor(website.value.primary_color) ? '#fff' : '#333';
        }
    }
    
    return styles;
}

// Function to generate styles for pricing plans
function getPlanStyle(plan) {
    const styles = {};
    
    // Apply custom background color if provided
    if (plan.backgroundColor) {
        styles.backgroundColor = plan.backgroundColor;
    }
    
    // Apply custom border if provided
    if (plan.borderColor) {
        styles.borderColor = plan.borderColor;
        styles.borderWidth = plan.featured ? '2px' : '1px';
        styles.borderStyle = 'solid';
    } else if (plan.featured) {
        // Default featured plan border
        styles.borderColor = 'var(--primary-color)';
        styles.borderWidth = '2px';
        styles.borderStyle = 'solid';
    }
    
    // Apply custom text color if provided
    if (plan.textColor) {
        styles.color = plan.textColor;
    }
    
    return styles;
}

// Function to generate styles for pricing plan buttons
function getPlanButtonStyle(plan) {
    // Start with default button styles
    const styles = { ...getButtonStyles() };
    
    // Override with custom button background if provided
    if (plan.buttonBackground) {
        styles.backgroundColor = plan.buttonBackground;
        
        // Auto-adjust text color based on background darkness
        if (!plan.buttonTextColor) {
            styles.color = isDarkColor(plan.buttonBackground) ? '#ffffff' : '#333333';
        }
    }
    
    // Override with custom button text color if provided
    if (plan.buttonTextColor) {
        styles.color = plan.buttonTextColor;
    }
    
    return styles;
}

function toggleFaqItem(item) {
    item.isOpen = !item.isOpen;
}

// After the existing functions like formatDate, hexToRgb, etc.

function handleCtaClick() {
    // Find the first navigation item that's not home or contact
    const firstContentItem = navigationItems.value.find(item => 
        !item.parent_id && item.id !== 'home' && !item.is_contact
    );
    
    if (firstContentItem) {
        // Navigate to the first available content
        handleNavigationClick(firstContentItem);
    } else if (website.value.show_contact_form) {
        // If no content items, go to contact if available
        loadContact();
    }
}

function loadPost(slug) {
    clearContent();
    activeNavigationId.value = 'blog-post';
    loading.value = true;
    error.value = null;
    
    console.log(`Loading blog post with slug: ${slug}`);
    
    // First try to find post in already loaded posts
    const post = posts.value.find(p => p.slug === slug);
    
    if (post) {
        console.log('Post found in existing posts');
        selectedPost.value = post;
        loading.value = false;
    } else {
        // Fetch the individual post if not found in the loaded posts
        console.log('Post not found in existing posts, fetching from API');
        apiClientPublic
            .get(`/company/website/?action=getBlogPost&websiteId=${websiteId.value}&slug=${slug}`)
            .then(response => {
                if (response.data && response.data.post) {
                    selectedPost.value = response.data.post;
                    // Add to posts array if not already there
                    if (!posts.value.find(p => p.id === response.data.post.id)) {
                        posts.value.push(response.data.post);
                    }
                } else {
                    error.value = 'Blog-Beitrag nicht gefunden.';
                }
            })
            .catch(err => {
                console.error('Error loading blog post:', err);
                error.value = 'Fehler beim Laden des Blog-Beitrags.';
            })
            .finally(() => {
                loading.value = false;
            });
    }
}

function handleRoute() {
    console.log('Handling route change:', route.path);
    
    // Match blog post route
    const blogPostMatch = route.path.match(/\/website\/([^\/]+)\/blog\/([^\/]+)/);
    if (blogPostMatch) {
        const postSlug = blogPostMatch[2];
        console.log('Blog post route detected, slug:', postSlug);
        websiteId.value = blogPostMatch[1];
        
        // Load website if needed, then load the post
        if (!website.value || website.value.id !== websiteId.value) {
            loadWebsite().then(() => {
                loadPost(postSlug);
            });
        } else {
            loadPost(postSlug);
        }
        return;
    }

    // Match website route
    const websiteMatch = route.path.match(/\/website\/([^\/]+)(?:\/([^\/]+))?/);
    if (websiteMatch) {
        websiteId.value = websiteMatch[1];
        const pageSlug = websiteMatch[2];
        
        console.log('Website route detected, id:', websiteId.value, 'slug:', pageSlug || '(none)');
        
        // Load the website
        loadWebsite().then(() => {
            if (pageSlug) {
                // If a specific slug is in the URL, load that content
                loadContentBySlug(pageSlug);
            }
        });
    }
}

// Also add this helper function in the script section
function stripHtml(html) {
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

// Add this function to the script section
function goBackToBlog() {
    // Find the first blog navigation item
    const blogItem = navigationItems.value.find(item => item.is_blog);
    if (blogItem) {
        loadBlogOverview(blogItem);
    } else {
        // If no specific blog item exists, just load general blog overview
        loadBlogOverview();
    }
}

// Add this function to the script section
function handleFeatureLink(feature) {
    console.log('Feature link clicked:', feature);
    
    if (!feature.linkUrl) return;
    
    // Handle internal links to blog posts
    if (feature.linkUrl.includes('/blog/')) {
        // Handle blog post links with extraction of slug
        const blogPostMatch = feature.linkUrl.match(/\/blog\/([^\/]+)/);
        if (blogPostMatch) {
            const postSlug = blogPostMatch[1];
            console.log('Blog post slug:', postSlug);
            
            // Find post by slug
            const post = posts.value.find(p => p.slug === postSlug);
            if (post) {
                loadSinglePost(post.id);
            } else {
                console.warn('Blog post not found with slug:', postSlug);
            }
        }
        return;
    }
    
    // Handle internal links
    if (feature.linkUrl.startsWith('#')) {
        // Link to another page on the website
        const slug = feature.linkUrl.substring(1);
        
        // Find navigation item with this slug
        const navItem = navigationItems.value.find(item => {
            // Check by ID
            if (item.id === slug) return true;
            
            // Check by page slug
            if (item.page_id) {
                const page = pages.value.find(p => p.id === item.page_id);
                if (page && page.slug === slug) return true;
            }
            
            return false;
        });
        
        if (navItem) {
            handleNavigationClick(navItem);
        } else if (slug === 'contact') {
            loadContact();
        } else if (slug === 'blog') {
            goBackToBlog();
        } else {
            // If no navigation item found, try to find page directly
            const page = pages.value.find(p => p.slug === slug);
            if (page) {
                // Create temporary nav item
                const tempNavItem = {
                    id: `temp-${page.id}`,
                    title: page.title,
                    page_id: page.id
                };
                loadPage(tempNavItem);
            } else {
                console.warn('No navigation item or page found for slug:', slug);
            }
        }
    } else if (plan.buttonUrl.includes('/blog/')) {
        // Handle blog post links
        const blogPostMatch = plan.buttonUrl.match(/\/blog\/([^\/]+)/);
        if (blogPostMatch) {
            const postSlug = blogPostMatch[1];
            loadPost(postSlug);
        }
    } else {
        // External link
        window.open(plan.buttonUrl, plan.buttonTarget || '_blank');
    }
}

// Add this function to handle countdown button clicks
function handleCountdownButtonClick(content) {
    console.log('Countdown button clicked:', content);
    
    if (!content.buttonUrl) return;
    
    // Handle internal links (starting with #)
    if (content.buttonUrl.startsWith('#')) {
        const slug = content.buttonUrl.substring(1);
        
        // Find navigation item with this slug
        const navItem = navigationItems.value.find(item => {
            if (item.id === slug) return true;
            
            if (item.page_id) {
                const page = pages.value.find(p => p.id === item.page_id);
                if (page && page.slug === slug) return true;
            }
            
            return false;
        });
        
        if (navItem) {
            handleNavigationClick(navItem);
        } else if (slug === 'contact') {
            loadContact();
        } else if (slug === 'blog') {
            goBackToBlog();
        } else {
            // Try to find page directly
            const page = pages.value.find(p => p.slug === slug);
            if (page) {
                const tempNavItem = {
                    id: `temp-${page.id}`,
                    title: page.title,
                    page_id: page.id
                };
                loadPage(tempNavItem);
            } else {
                console.warn('No navigation item or page found for slug:', slug);
            }
        }
    } else if (content.buttonUrl.includes('/blog/')) {
        // Handle blog post links
        const blogPostMatch = content.buttonUrl.match(/\/blog\/([^\/]+)/);
        if (blogPostMatch) {
            const postSlug = blogPostMatch[1];
            loadPost(postSlug);
        }
    } else {
        // External link
        window.open(content.buttonUrl, content.buttonTarget || '_blank');
    }
}
</script>
<style>
.main-content {
    padding: 0 !important;
}
#web-body {
    --v-layout-top: 0px !important;
}

/* Ensure background image is applied to the full website */
.website-container {
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}
</style>
<style scoped>

.main-content{
    padding: 0 !important;
}
#web-body{
    --v-layout-top: 0px !important;
}
.website-view {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: var(--background-color, #f0f2f5); /* Default background */
    color: var(--text-color, #333333); /* Default text color */
}

.loading-container,
.error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 300px;
    text-align: center;
}

.spinner {
    font-size: 3rem;
    margin-bottom: 20px;
    color: var(--primary-color, #3b82f6);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.error-icon {
    font-size: 3rem;
    margin-bottom: 20px;
    color: #dc3545; /* Bootstrap danger color */
}

.error-container h2 {
    color: #dc3545;
    margin-bottom: 10px;
}

.website-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    /* Styles from websiteStyles computed property will override background-color and color */
    min-height: 100%; /* Ensure it takes at least full viewport height minus header/footer */
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

/* Maintenance Mode */
.maintenance-mode-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    min-height: calc(100vh - 120px); /* Adjust based on typical header/footer height */
    padding: 40px 20px;
}

.maintenance-content {
    max-width: 600px;
    padding: 30px;
    margin: auto;
    background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    color: #333;
}

.maintenance-icon {
    font-size: 5rem;
    margin: 20px 0;
    color: var(--primary-color, #ffc107); /* Use primary or a warning color */
}

.site-logo {
    margin-bottom: 15px;
}

.site-logo img {
    max-height: 80px;
    max-width: 200px;
    display: block;
    margin: 0 auto;
}

/* Header */
.site-header {
    background-color: var(--primary-color, #3b82f6);
    color: white;
    padding: 2.5rem 1.5rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 10; /* Ensure header is above content */
    background-image: linear-gradient(
        135deg,
        var(--primary-color, #3b82f6),
        var(--secondary-color, #1e3a8a)
    ); /* Default gradient */
}

.site-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.site-slogan {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
    font-weight: 300;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/* Navigation */
.site-nav {
    display: flex;
    justify-content: center;
}

.site-nav ul {
    display: flex;
    justify-content: center;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 1rem;
    flex-wrap: wrap;
}

.site-nav li {
    margin: 0;
    position: relative;
}

.site-nav a {
    color: white;
    text-decoration: none;
    padding: 0.7rem 1.2rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    display: inline-block;
    letter-spacing: 0.3px;
    background-color: rgba(255, 255, 255, 0.1);
}

.site-nav a:hover,
.site-nav a.active {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.site-nav a:active {
    transform: translateY(0);
}

/* Submenu */
.site-nav .submenu {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 220px;
    background-color: white; /* Submenu background */
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    display: none;
    flex-direction: column;
    z-index: 100;
    padding: 0.5rem;
    overflow: hidden;
}

/* Add padding above the dropdown to prevent gap */
.site-nav .has-submenu:hover .submenu {
    display: flex;
    animation: fadeIn 0.3s ease;
}

.site-nav .submenu::before {
    content: '';
    position: absolute;
    left: 0;
    bottom: 100%; /* Position above the submenu */
    width: 100%;
    height: 10px;
    background: transparent; /* Invisible element */
}

.site-nav .submenu li {
    margin: 0;
    padding: 0;
    width: 100%;
}

.site-nav .submenu a {
    padding: 0.8rem 1.2rem;
    width: 100%;
    color: #333; /* Submenu link text color */
    border-radius: 8px;
    background-color: transparent;
    font-weight: 400;
}

.site-nav .submenu a:hover {
    background-color: rgba(0, 0, 0, 0.05); /* Submenu link hover background */
    color: var(--primary-color, #3b82f6); /* Submenu link hover color */
}

.no-navigation-message {
    color: rgba(255, 255, 255, 0.7);
    font-style: italic;
    padding: 10px;
}

/* Main Content */
.site-main {
    flex: 1;
    padding: 2.5rem 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

/* Hero Section (Default Home) */
.hero-section {
    text-align: center;
    padding: 3.5rem 2rem;
    margin-bottom: 3rem;
    border-radius: 16px;
    background-color: var(--secondary-color, #1e3a8a);
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.hero-section::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
    z-index: 0;
}

.hero-section > * {
    position: relative;
    z-index: 1;
}

.hero-section h1 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.hero-section div {
    /* Styling for v-html description */
    font-size: 1.2rem;
    margin-bottom: 30px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/* Styling for the Hero CTA area */
.hero-cta {
    margin-top: 2rem;
}

.hero-cta .cta-button {
    font-size: 1.1rem;
    padding: 12px 30px;
    border-radius: 50px; /* Rounder buttons */
    background-color: var(--primary-color, #3b82f6);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.hero-cta .cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    background-color: var(--darker-primary, #2563eb); /* Slightly darker on hover */
}

/* General CTA Button Style (used elsewhere if not in hero) */
.cta-button {
    background-color: var(--accent-color, #60a5fa);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: opacity 0.3s;
}

.cta-button:hover {
    opacity: 0.9;
}

/* Dynamic Content Styling */
.dynamic-content {
    padding: 2.5rem;
    max-width: 1000px;
    margin: 0 auto;
    background-color: white; /* Default white background for general content */
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    color: #333; /* Default text color for general content */
    line-height: 1.7;
}

.dynamic-content h1 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color, #3b82f6);
    font-weight: 700;
}

.dynamic-content h2 {
    font-size: 2rem;
    margin: 2rem 0 1rem;
    color: var(--text-color, #2d3748);
}

.dynamic-content h3 {
    font-size: 1.5rem;
    margin: 1.5rem 0 1rem;
    color: var(--text-color, #2d3748);
}

.dynamic-content p {
    margin-bottom: 1.5rem;
    color: var(--text-color, #4a5568);
}

.dynamic-content a {
    color: var(--primary-color, #3b82f6);
    text-decoration: none;
    border-bottom: 1px solid rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.dynamic-content a:hover {
    border-bottom-color: var(--primary-color, #3b82f6);
}

.empty-content {
    text-align: center;
    padding: 4rem 2rem;
    background-color: var(--background-color, #f0f2f5);
    border-radius: 16px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    color: var(--text-color, #555);
}

.empty-content h2 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color, #3b82f6);
    font-weight: 700;
}

/* Footer Styling */
.site-footer {
    background-color: var(--secondary-color, #1e3a8a);
    background-image: linear-gradient(
        135deg,
        var(--secondary-color, #1e3a8a),
        #152352
    ); /* Default gradient */
    color: white;
    padding: 1.5rem;
    text-align: center;
    margin-top: auto; /* Pushes footer to the bottom */
    width: 100%;
    box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
}

.footer-bottom {
    padding-top: 10px;
    font-size: 0.95rem;
    opacity: 0.9;
    letter-spacing: 0.3px;
}

/* Block Content Styles (Copied/Adapted from manager preview) */
.block-content {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    position: relative;
    z-index: 1;
}

.block-wrapper {
    margin-bottom: 40px;
    position: relative;
    z-index: 1;
    /* Background and text color should come from the main website-container styles */
}

/* Text Block */
.text-block {
    line-height: 1.6;
}

.text-block p {
    margin-bottom: 1em;
}
.text-block h1 {
    font-size: 2em;
    margin-bottom: 0.5em;
    color: var(--primary-color);
}
.text-block h2 {
    font-size: 1.7em;
    margin: 1em 0 0.5em;
    color: var(--secondary-color);
}
.text-block h3 {
    font-size: 1.4em;
    margin: 0.8em 0 0.4em;
    color: var(--accent-color);
}

/* Team Block */
.team-block {
    text-align: center;
    padding: 20px 0;
}

.team-block h2 {
    color: var(--primary-color);
}

.team-description {
    max-width: 800px;
    margin: 0 auto 30px;
}

.team-members {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
}

.team-member {
    flex: 1;
    min-width: 250px;
    max-width: 300px;
    padding: 20px;
    background: var(--background-color, #f5f5f5);
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    color: var(--text-color);
}

.member-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 15px;
    overflow: hidden;
    background-color: var(--primary-color, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
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
    background: var(--accent-color, #e0e0e0); /* Use accent for placeholder */
    color: var(--text-color);
    font-size: 2.5rem;
    font-weight: bold;
}

.team-member h3 {
    color: var(--secondary-color);
}

.member-position {
    color: var(--text-color, #666);
    font-style: italic;
    margin: 5px 0 10px;
}

.member-bio {
    color: var(--text-color, #4a5568);
}

/* Columns Block */
.columns-block {
    padding: 20px 0;
}

.columns-block h2 {
    color: var(--primary-color);
}

.columns-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 20px;
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

.column {
    flex: 1;
    min-width: 250px;
}

.column h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--secondary-color);
}

.column-content {
    line-height: 1.6;
    color: var(--text-color, #4a5568);
}

/* Testimonials Block */
.testimonials-block {
    padding: 20px 0;
}

.testimonials-block h2 {
    color: var(--primary-color);
}

.testimonials-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    justify-content: center;
}

.testimonial {
    flex: 1;
    min-width: 300px;
    background: var(--background-color, #f9f9f9);
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    color: var(--text-color);
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

.testimonial-quote blockquote::before {
    content: '"'; /* Use proper quote symbol */
    font-size: 50px;
    position: absolute;
    left: -15px;
    top: -20px;
    color: var(--accent-color, #ccc);
}

.testimonial-author {
    display: flex;
    align-items: center;
}

.author-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
    overflow: hidden;
    background-color: var(--primary-color, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.author-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--secondary-color);
}

.author-position {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-color, #666);
    font-style: italic;
}

/* FAQ Block */
.faq-block {
    padding: 20px 0;
    max-width: 900px;
    margin: 0 auto;
}

.faq-block h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 1rem;
}

.faq-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2rem;
    color: var(--text-color);
}

.faq-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.faq-item {
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    background-color: var(--background-color, white);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.faq-question {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background-color: rgba(0, 0, 0, 0.02);
    transition: background-color 0.3s;
}

.faq-question:hover {
    background-color: rgba(0, 0, 0, 0.04);
}

.faq-question h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-color);
    font-weight: 500;
}

.faq-toggle-icon {
    color: var(--primary-color);
    transition: transform 0.3s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.faq-answer {
    padding: 1rem 1.5rem;
    background-color: rgba(0, 0, 0, 0.01);
    color: var(--text-color);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.faq-answer-open {
    height: auto;
    padding: 1rem 1.5rem;
    opacity: 1;
    overflow: visible;
}

/* Gallery Block */
.gallery-block {
    padding: 20px 0;
}

.gallery-block h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 1rem;
}

.gallery-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2rem;
    color: var(--text-color);
}

.gallery-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.gallery-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.gallery-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

.gallery-grid-4 {
    grid-template-columns: repeat(4, 1fr);
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    aspect-ratio: 4/3;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

.gallery-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 0.9rem;
}

/* CTA Block */
.cta-block {
    padding: 20px 0;
}

.cta-container {
    padding: 3rem 2rem;
    border-radius: 12px;
    background-color: var(--primary-color, #3b82f6);
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.cta-container h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: inherit;
}

.cta-text {
    max-width: 700px;
    margin: 0 auto 2rem;
    font-size: 1.1rem;
    line-height: 1.6;
}

.cta-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-button {
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
}

.primary-button {
    background-color: white;
    color: var(--primary-color, #3b82f6);
}

.primary-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
}

.secondary-button {
    background-color: transparent;
    border: 2px solid white;
    color: white;
}

.secondary-button:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
}

/* Video Block */
.video-block {
    padding: 20px 0;
    max-width: 900px;
    margin: 0 auto;
}

.video-block h2 {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 1rem;
}

.video-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2rem;
    color: var(--text-color);
}

.video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.video-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    color: #666;
}

.video-placeholder i {
    font-size: 3rem;
    margin-bottom: 10px;
}

/* Blog Overview Styles */
.blog-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.blog-header {
    text-align: center;
    margin-bottom: 40px;
}

.blog-header h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color, #3b82f6);
}

.blog-description {
    font-size: 1.2rem;
    color: var(--text-color, #666);
    max-width: 700px;
    margin: 0 auto;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.blog-card {
    background-color: var(--background-color, white);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.card-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.card-image-placeholder {
    background-color: var(--primary-color, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-image-placeholder i {
    font-size: 3rem;
    color: white;
}

.card-content {
    padding: 25px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: var(--text-color, #666);
    flex-wrap: wrap;
    gap: 10px;
}

.card-meta time {
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.category-tag {
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.card-title {
    font-size: 1.4rem;
    margin-bottom: 15px;
    color: var(--primary-color, #3b82f6);
    line-height: 1.3;
}

.card-excerpt {
    color: var(--text-color, #333);
    margin-bottom: 20px;
    font-size: 0.95rem;
    line-height: 1.6;
    flex-grow: 1;
}

.read-more {
    background-color: var(--primary-color, #3b82f6);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    transition: all 0.3s ease;
    margin-top: auto;
    align-self: flex-start;
}

.read-more:hover {
    background-color: var(--secondary-color, #1e3a8a);
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: white;
}

@media (max-width: 768px) {
    .blog-grid {
        grid-template-columns: 1fr;
    }
}

/* Single Post Styles */
.single-post {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
    color: var(--text-color, #333);
    background-color: var(--background-color, white);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.post-header {
    margin-bottom: 30px;
}

.post-title {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color, #3b82f6);
    line-height: 1.2;
}

.post-meta {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-size: 0.9rem;
    color: var(--text-color, #666);
    gap: 20px;
    flex-wrap: wrap;
}

.post-meta time {
    display: flex;
    align-items: center;
    gap: 5px;
}

.post-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

/* Category tag styles are already defined above */

.post-featured-image {
    margin-bottom: 30px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.post-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.post-content {
    font-size: 1.1rem;
    line-height: 1.7;
    color: var(--text-color, #333);
    margin-bottom: 40px;
}

.post-content p {
    margin-bottom: 1.5em;
}
.post-content h1 {
    font-size: 2em;
    margin: 2em 0 1em;
    color: var(--primary-color);
}
.post-content h2 {
    font-size: 1.7em;
    margin: 1.5em 0 0.8em;
    color: var(--secondary-color);
}
.post-content h3 {
    font-size: 1.4em;
    margin: 1em 0 0.6em;
    color: var(--accent-color);
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 20px 0;
    display: block;
}

.post-footer {
    border-top: 1px solid #eee; /* Use a neutral border color */
    padding-top: 30px;
    margin-top: 30px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--primary-color, #3b82f6);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-button:hover {
    background-color: var(--darker-primary, #2563eb); /* Slightly darker on hover */
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: white;
}

/* Contact Form Styles */
.contact-form-section {
    margin: 40px 0;
}

.contact-form-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 30px;
    border-radius: 8px;
    background-color: var(--background-color, #f8f9fa);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    color: var(--text-color, #333);
}

.contact-form-container h2 {
    color: var(--primary-color, #3b82f6);
    margin-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-color);
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--text-color, #ddd); /* Use a neutral border color */
    border-radius: 4px;
    font-size: 1rem;
    background-color: var(--background-color);
    color: var(--text-color);
}

textarea.form-control {
    resize: vertical;
}

.checkbox-container {
    display: flex;
    align-items: center;
}

.checkbox-container input {
    margin-right: 10px;
}

.form-actions {
    margin-top: 30px;
    text-align: right;
}

.btn-primary {
    background-color: var(--accent-color, #60a5fa);
    color: var(--button-text-color, white);
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: opacity 0.3s;
}

.btn-primary:hover {
    opacity: 0.9;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .site-nav ul {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .site-nav li {
        margin: 5px 0;
        padding-bottom: 0;
    }

    .site-nav .submenu {
        position: static;
        display: flex; /* Show submenus always in mobile */
        width: 100%;
        box-shadow: none;
        margin-top: 0;
        background-color: rgba(
            0,
            0,
            0,
            0.1
        ); /* Slightly different background for submenus in mobile */
        border-radius: 0;
        padding: 0;
    }

    .site-nav .has-submenu:hover .submenu {
        display: flex; /* Keep flex for hover */
    }

    .site-nav .submenu a {
        padding: 8px 20px;
        border-radius: 0;
        color: white; /* Submenu links in mobile */
    }

    .site-nav .submenu a:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .columns-container {
        flex-direction: column;
    }

    .columns-2 .column,
    .columns-3 .column,
    .columns-4 .column {
        flex: 0 0 100%;
    }

    .team-member,
    .testimonial {
        flex: 0 0 100%;
    }

    .site-main {
        padding: 1.5rem 1rem;
    }

    .hero-section {
        padding: 2.5rem 1rem;
    }

    .hero-section h1 {
        font-size: 2rem;
    }

    .blog-grid {
        grid-template-columns: 1fr;
    }

    .single-post {
        padding: 30px 15px;
    }

    .post-content {
        font-size: 1rem;
    }
    
    /* Responsive styles for new block types */
    .gallery-grid-2,
    .gallery-grid-3,
    .gallery-grid-4 {
        grid-template-columns: 1fr;
    }
    
    .cta-container {
        padding: 2rem 1rem;
    }
    
    .cta-container h2 {
        font-size: 1.6rem;
    }
    
    .cta-text {
        font-size: 1rem;
    }
    
    /* Feature Boxes */
    .features-columns-2,
    .features-columns-3,
    .features-columns-4 {
        grid-template-columns: 1fr;
    }
    
    /* Statistics */
    .statistics-columns-2,
    .statistics-columns-3,
    .statistics-columns-4 {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .statistic-value {
        font-size: 2.5rem;
    }
    
    /* Pricing */
    .pricing-plan {
        min-width: 100%;
    }
    
    .featured-plan {
        transform: translateY(0);
        padding: 40px 30px;
    }
    
    .featured-plan:hover {
        transform: translateY(-5px);
    }
    
    /* Countdown */
    .countdown-timer {
        flex-wrap: wrap;
    }
    
    .countdown-unit {
        min-width: 70px;
    }
    
    .countdown-value {
        font-size: 2.2rem;
    }
}

/* Video Placeholder */
.video-placeholder p {
    margin-top: 10px;
}

/* Feature Boxes Block */
.features-block {
    padding: 40px 0;
}

.features-block h2 {
    text-align: center;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.features-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2.5rem;
    color: var(--text-color);
}

.features-container {
    display: grid;
    gap: 30px;
}

.features-columns-1 {
    grid-template-columns: 1fr;
}

.features-columns-2 {
    grid-template-columns: repeat(2, 1fr);
}

.features-columns-3 {
    grid-template-columns: repeat(3, 1fr);
}

.features-columns-4 {
    grid-template-columns: repeat(4, 1fr);
}

.feature-box {
    padding: 30px;
    border-radius: 10px;
    background-color: var(--background-color, white);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    height: 100%;
    text-align: center;
    align-items: center;
    position: relative;
    z-index: 1;
}

.feature-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    display: flex;
    justify-content: center;
    position: relative;
    z-index: 2;
}

.feature-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--secondary-color);
    font-weight: 600;
    text-align: center;
    position: relative;
    z-index: 2;
    user-select: text;
}

.feature-text {
    margin-bottom: 20px;
    color: var(--text-color);
    line-height: 1.6;
    flex-grow: 1;
    text-align: center;
    position: relative;
    z-index: 2;
    user-select: text;
}

.feature-link {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
    margin-top: auto;
    position: relative;
    z-index: 2;
}

.feature-link i {
    margin-left: 5px;
    transition: transform 0.2s;
}

.feature-link:hover {
    color: var(--darker-primary);
}

.feature-link:hover i {
    transform: translateX(3px);
}

/* Statistics Block */
.statistics-block {
    padding: 50px 0;
    background-color: var(--background-color, white);
    border-radius: 10px;
    margin: 30px 0;
}

.statistics-block h2 {
    text-align: center;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.statistics-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2.5rem;
    color: var(--text-color);
}

.statistics-container {
    display: grid;
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.statistics-columns-2 {
    grid-template-columns: repeat(2, 1fr);
}

.statistics-columns-3 {
    grid-template-columns: repeat(3, 1fr);
}

.statistics-columns-4 {
    grid-template-columns: repeat(4, 1fr);
}

.statistic-item {
    text-align: center;
    padding: 20px;
}

.statistic-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.statistic-value {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--primary-color);
    line-height: 1;
}

.statistic-label {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--secondary-color);
}

.statistic-description {
    color: var(--text-color);
    font-size: 0.95rem;
}

/* Pricing Table Block */
.pricing-block {
    padding: 40px 0;
}

.pricing-block h2 {
    text-align: center;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.pricing-description {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 2.5rem;
    color: var(--text-color);
}

.pricing-table {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    margin: 0 auto;
}

.pricing-plan {
    flex: 1;
    min-width: 280px;
    max-width: 350px;
    background-color: var(--background-color, white);
    border-radius: 12px;
    padding: 40px 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    text-align: center;
    position: relative;
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
}

.pricing-plan:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.featured-plan {
    transform: translateY(-15px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    border: 2px solid var(--primary-color);
    padding: 45px 30px;
}

.featured-plan:hover {
    transform: translateY(-20px);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--primary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.plan-name {
    font-size: 1.7rem;
    margin-bottom: 15px;
    color: var(--secondary-color);
}

.plan-price {
    margin-bottom: 20px;
    line-height: 1.1;
}

.price-currency {
    font-size: 1.5rem;
    vertical-align: top;
    position: relative;
    top: 0.5rem;
    margin-right: 3px;
    color: var(--primary-color);
}

.price-value {
    font-size: 3.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.price-period {
    font-size: 1rem;
    color: var(--text-color);
    opacity: 0.8;
    display: block;
    margin-top: 5px;
}

.plan-description {
    margin: 20px 0;
    color: var(--text-color);
    font-size: 0.95rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 30px;
    text-align: left;
    flex-grow: 1;
}

.plan-features li {
    display: flex;
    color: var(--text-color);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    align-items: center;
}

.plan-features li:last-child {
    border-bottom: none;
}

.feature-included {
    color: var(--text-color);
}

li:not(.feature-included) {
    color: var(--text-color);
}

.feature-icon {
    margin-right: 10px;
    min-width: 20px;
    display: inline-block;
}

.feature-included .feature-icon {
    color: var(--primary-color);
}

.plan-button {
    display: inline-block;
    padding: 12px 30px;
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.3s;
    margin-top: auto;
}

.plan-button:hover {
    background-color: var(--darker-primary);
    transform: translateY(-3px);
}

/* Countdown Block */
.countdown-block {
    padding: 40px 0;
    text-align: center;
}

.countdown-block h2 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.countdown-description {
    max-width: 700px;
    margin: 0 auto 2.5rem;
    color: var(--text-color);
}

.countdown-container {
    max-width: 800px;
    margin: 0 auto;
    background-color: var(--background-color, white);
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

.countdown-timer {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.countdown-unit {
    display: flex;
    flex-direction: column;
    min-width: 80px;
}

.countdown-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1;
    margin-bottom: 5px;
}

.countdown-label {
    font-size: 0.9rem;
    color: var(--text-color);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.countdown-expired-message {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin: 20px 0;
    font-weight: 600;
}

.countdown-action {
    margin-top: 30px;
}

.countdown-button {
    display: inline-block;
    padding: 12px 30px;
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.3s;
}

.countdown-button:hover {
    background-color: var(--darker-primary);
    transform: translateY(-3px);
}

/* Responsive Styles - Extend for new components */

/* Fix for text selection issues */
.dynamic-content, .text-block, .columns-block, .team-block, .testimonials-block, 
.faq-block, .gallery-block, .cta-block, .video-block, .features-block, 
.statistics-block, .pricing-block, .countdown-block, .statistic-item, .pricing-plan,
.column-content, .team-description, .member-bio, .testimonial-quote,
.faq-answer, .gallery-caption, .cta-text, .plan-description, .plan-features {
    user-select: text;
    position: relative;
    z-index: 2;
}

.site-main {
    position: relative;
    z-index: 2;
}

.feature-box::before,
.statistic-item::before,
.pricing-plan::before,
.testimonial::before,
.faq-item::before,
.team-member::before,
.column::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
    pointer-events: none;
}

/* Add to the existing CSS, before the responsive styles section */

/* Contact Form Success Message */
.form-success-message {
    padding: 30px;
    text-align: center;
    background-color: var(--background-color, #f8f9fa);
    border-radius: 8px;
    border: 1px solid rgba(0, 200, 83, 0.3);
    background-color: rgba(0, 200, 83, 0.05);
    animation: fadeIn 0.5s ease;
}

.success-icon {
    font-size: 3rem;
    color: #00c853;
    margin-bottom: 20px;
}

.form-success-message h3 {
    color: var(--primary-color, #3b82f6);
    margin-bottom: 10px;
}

.form-success-message p {
    color: var(--text-color, #333);
    margin-bottom: 0;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Debug Panel */
.debug-panel {
    position: fixed;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 10px;
    border-radius: 4px;
    z-index: 9999;
    font-size: 12px;
    max-width: 300px;
    max-height: 500px;
    overflow: auto;
}

/* Single Blog Post View */
.blog-post-view {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
    color: var(--text-color, #333);
    background-color: var(--background-color, white);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.blog-post-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 30px;
    border-radius: 8px;
    background-color: var(--background-color, #f8f9fa);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    color: var(--text-color, #333);
}

.blog-post-nav {
    margin-bottom: 30px;
}

.blog-post-nav a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--primary-color, #3b82f6);
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.blog-post-nav a:hover {
    background-color: var(--darker-primary, #2563eb); /* Slightly darker on hover */
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: white;
}

.blog-post-featured-image {
    margin-bottom: 30px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.blog-post-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.blog-post-header {
    margin-bottom: 30px;
}

.blog-post-title {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color, #3b82f6);
    line-height: 1.2;
}

.blog-post-meta {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-size: 0.9rem;
    color: var(--text-color, #666);
    gap: 20px;
    flex-wrap: wrap;
}

.post-date {
    display: flex;
    align-items: center;
    gap: 5px;
}

.post-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.post-tag {
    background: var(--primary-color, #3b82f6);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.blog-post-content {
    font-size: 1.1rem;
    line-height: 1.7;
    color: var(--text-color, #333);
    margin-bottom: 40px;
}

.blog-post-content p {
    margin-bottom: 1.5em;
}
.blog-post-content h1 {
    font-size: 2em;
    margin: 2em 0 1em;
    color: var(--primary-color);
}
.blog-post-content h2 {
    font-size: 1.7em;
    margin: 1.5em 0 0.8em;
    color: var(--secondary-color);
}
.blog-post-content h3 {
    font-size: 1.4em;
    margin: 1em 0 0.6em;
    color: var(--accent-color);
}

.blog-post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 20px 0;
    display: block;
}

.blog-post-tags {
    text-align: center;
    margin-top: 20px;
}

.tags-label {
    font-size: 1.2rem;
    color: var(--text-color, #666);
    font-weight: 600;
}
</style>
