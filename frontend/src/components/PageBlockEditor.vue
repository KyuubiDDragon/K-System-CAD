<template>
    <div class="page-block-editor">
        <div class="blocks-container">
            <draggable
                v-model="blocks"
                group="blocks"
                handle=".block-handle"
                @end="onChange"
                item-key="id"
                class="blocks-list"
            >
                <template #item="{ element, index }">
                    <div class="block-item" :class="{ active: activeBlockIndex === index }">
                        <div class="block-header">
                            <div class="block-handle">
                                <i class="mdi mdi-drag"></i>
                            </div>
                            <div class="block-title" @click="toggleBlock(index)">
                                {{ getBlockTitle(element) }}
                                <i
                                    class="mdi"
                                    :class="
                                        activeBlockIndex === index
                                            ? 'mdi-chevron-up'
                                            : 'mdi-chevron-down'
                                    "
                                ></i>
                            </div>
                            <div class="block-actions">
                                <button
                                    @click="duplicateBlock(index)"
                                    class="btn-icon"
                                    title="Duplizieren"
                                >
                                    <i class="mdi mdi-content-duplicate"></i>
                                </button>
                                <button
                                    @click="removeBlock(index)"
                                    class="btn-icon"
                                    title="Löschen"
                                >
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </div>
                        </div>

                        <div class="block-content" v-if="activeBlockIndex === index">
                            <!-- Hero Block -->
                            <div v-if="element.type === 'hero'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Untertitel</label>
                                    <input
                                        v-model="element.content.subtitle"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Hintergrundbild</label>
                                    <div class="media-selector">
                                        <div v-if="element.content.bgImage" class="selected-media">
                                            <img
                                                :src="getMediaUrl(element.content.bgImage)"
                                                alt="Hintergrundbild"
                                            />
                                        </div>
                                        <input
                                            type="file"
                                            :id="'bg-image-upload-' + index"
                                            class="hidden-upload"
                                            @change="uploadBlockImage($event, index, 'bgImage')"
                                            accept="image/*"
                                        />
                                        <div class="media-actions">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                @click="
                                                    document
                                                        .getElementById('bg-image-upload-' + index)
                                                        .click()
                                                "
                                            >
                                                <i class="fas fa-upload"></i> Hochladen
                                            </button>
                                            <button
                                                v-if="element.content.bgImage"
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                @click="removeBlockImage(index, 'bgImage')"
                                            >
                                                <i class="fas fa-trash"></i> Entfernen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                    <small class="form-text"
                                        >Wird verwendet, wenn kein Hintergrundbild festgelegt ist
                                        oder als Overlay.</small
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Parallax-Effekt</label>
                                    <div class="toggle-switch">
                                        <input
                                            type="checkbox"
                                            :id="'parallax-toggle-' + index"
                                            v-model="element.content.parallax"
                                        />
                                        <label :for="'parallax-toggle-' + index"></label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Button-Text</label>
                                    <input
                                        v-model="element.content.buttonText"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Button-Ziel</label>
                                    <select
                                        v-model="element.content.buttonTarget"
                                        class="form-control"
                                    >
                                        <option value="">Kein Ziel</option>
                                        <option
                                            v-for="page in pages"
                                            :key="page.id"
                                            :value="page.id"
                                        >
                                            {{ page.title }}
                                        </option>
                                        <option value="contact">Kontaktformular</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Text Block -->
                            <div v-else-if="element.type === 'text'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Inhalt</label>
                                    <textarea
                                        v-model="element.content.text"
                                        class="form-control"
                                        rows="6"
                                        @input="onChange"
                                    ></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>
                            </div>

                            <!-- Columns Block -->
                            <div v-else-if="element.type === 'columns'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Spaltenanzahl</label>
                                    <select
                                        v-model="element.content.columnCount"
                                        class="form-control"
                                        @change="updateColumns(index)"
                                    >
                                        <option value="2">2 Spalten</option>
                                        <option value="3">3 Spalten</option>
                                        <option value="4">4 Spalten</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="columns-container">
                                    <div
                                        v-for="(column, colIndex) in element.content.columns"
                                        :key="colIndex"
                                        class="column-item"
                                    >
                                        <div class="column-header">
                                            <h4>Spalte {{ colIndex + 1 }}</h4>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ t('pageEditor.heading') }}</label>
                                            <input
                                                v-model="column.title"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="form-group">
                                            <label>Inhalt</label>
                                            <textarea
                                                v-model="column.text"
                                                class="form-control"
                                                rows="6"
                                                @input="onChange"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Team Block -->
                            <div v-else-if="element.type === 'team'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Beschreibung</label>
                                    <textarea
                                        v-model="element.content.description"
                                        class="form-control"
                                        rows="2"
                                    ></textarea>
                                </div>

                                <div class="team-members">
                                    <h4>Teammitglieder</h4>
                                    <button
                                        @click="addTeamMember(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> Teammitglied hinzufügen
                                    </button>

                                    <div
                                        v-for="(member, mIndex) in element.content.members"
                                        :key="mIndex"
                                        class="team-member-item"
                                    >
                                        <div class="member-header">
                                            <h5>Mitglied {{ mIndex + 1 }}</h5>
                                            <button
                                                @click="removeTeamMember(index, mIndex)"
                                                class="btn-icon"
                                            >
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>

                                        <div class="form-group">
                                            <label>Name</label>
                                            <input
                                                v-model="member.name"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="form-group">
                                            <label>Position</label>
                                            <input
                                                v-model="member.position"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="form-group">
                                            <label>Biografie</label>
                                            <textarea
                                                v-model="member.bio"
                                                class="form-control"
                                                rows="2"
                                            ></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Bild</label>
                                            <div class="media-selector">
                                                <div v-if="member.image" class="selected-media">
                                                    <img
                                                        :src="getMediaUrl(member.image)"
                                                        alt="Teammitglied"
                                                    />
                                                </div>
                                                <input
                                                    type="file"
                                                    :id="
                                                        'member-image-upload-' +
                                                        index +
                                                        '-' +
                                                        mIndex
                                                    "
                                                    class="hidden-upload"
                                                    @change="
                                                        uploadTeamMemberImage($event, index, mIndex)
                                                    "
                                                    accept="image/*"
                                                />
                                                <div class="media-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        @click="
                                                            document
                                                                .getElementById(
                                                                    'member-image-upload-' +
                                                                        index +
                                                                        '-' +
                                                                        mIndex
                                                                )
                                                                .click()
                                                        "
                                                    >
                                                        <i class="fas fa-upload"></i> Hochladen
                                                    </button>
                                                    <button
                                                        v-if="member.image"
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        @click="
                                                            removeTeamMemberImage(index, mIndex)
                                                        "
                                                    >
                                                        <i class="fas fa-trash"></i> Entfernen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonial Block -->
                            <div v-else-if="element.type === 'testimonials'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="testimonials">
                                    <h4>Testimonials</h4>
                                    <button
                                        @click="addTestimonial(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> Testimonial hinzufügen
                                    </button>

                                    <div
                                        v-for="(testimonial, tIndex) in element.content.items"
                                        :key="tIndex"
                                        class="testimonial-item"
                                    >
                                        <div class="testimonial-header">
                                            <h5>Testimonial {{ tIndex + 1 }}</h5>
                                            <button
                                                @click="removeTestimonial(index, tIndex)"
                                                class="btn-icon"
                                            >
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>

                                        <div class="form-group">
                                            <label>Name</label>
                                            <input
                                                v-model="testimonial.name"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="form-group">
                                            <label>Position/Firma</label>
                                            <input
                                                v-model="testimonial.position"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="form-group">
                                            <label>Zitat</label>
                                            <textarea
                                                v-model="testimonial.quote"
                                                class="form-control"
                                                rows="3"
                                            ></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Bild</label>
                                            <div class="media-selector">
                                                <div
                                                    v-if="testimonial.image"
                                                    class="selected-media"
                                                >
                                                    <img
                                                        :src="getMediaUrl(testimonial.image)"
                                                        alt="Testimonial"
                                                    />
                                                </div>
                                                <input
                                                    type="file"
                                                    :id="
                                                        'testimonial-image-upload-' +
                                                        index +
                                                        '-' +
                                                        tIndex
                                                    "
                                                    class="hidden-upload"
                                                    @change="
                                                        uploadTestimonialImage(
                                                            $event,
                                                            index,
                                                            tIndex
                                                        )
                                                    "
                                                    accept="image/*"
                                                />
                                                <div class="media-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        @click="
                                                            document
                                                                .getElementById(
                                                                    'testimonial-image-upload-' +
                                                                        index +
                                                                        '-' +
                                                                        tIndex
                                                                )
                                                                .click()
                                                        "
                                                    >
                                                        <i class="fas fa-upload"></i> Hochladen
                                                    </button>
                                                    <button
                                                        v-if="testimonial.image"
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        @click="
                                                            removeTestimonialImage(index, tIndex)
                                                        "
                                                    >
                                                        <i class="fas fa-trash"></i> Entfernen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galerie Block -->
                            <div v-else-if="element.type === 'gallery'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Beschreibung</label>
                                    <textarea
                                        v-model="element.content.description"
                                        class="form-control"
                                        rows="2"
                                    ></textarea>
                                </div>

                                <div class="gallery-layout">
                                    <label>Layout</label>
                                    <select v-model="element.content.layout" class="form-control">
                                        <option value="grid">Raster</option>
                                        <option value="masonry">Masonry (versetzt)</option>
                                        <option value="slider">Slider</option>
                                    </select>
                                </div>

                                <div class="gallery-images">
                                    <h4>Bilder</h4>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        @click="addGalleryImage(index)"
                                    >
                                        <i class="mdi mdi-plus"></i> Bild hinzufügen
                                    </button>

                                    <div class="gallery-items-container">
                                        <div
                                            v-for="(image, imageIndex) in element.content.images"
                                            :key="imageIndex"
                                            class="gallery-item"
                                        >
                                            <div class="gallery-item-header">
                                                <span>Bild {{ imageIndex + 1 }}</span>
                                                <button
                                                    @click="removeGalleryImage(index, imageIndex)"
                                                    class="btn-icon"
                                                >
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>

                                            <div class="media-selector">
                                                <div v-if="image.src" class="selected-media">
                                                    <img
                                                        :src="getMediaUrl(image.src)"
                                                        alt="Galleriebild"
                                                    />
                                                </div>
                                                <input
                                                    type="file"
                                                    :id="
                                                        'gallery-image-upload-' +
                                                        index +
                                                        '-' +
                                                        imageIndex
                                                    "
                                                    class="hidden-upload"
                                                    @change="
                                                        uploadGalleryImage(
                                                            $event,
                                                            index,
                                                            imageIndex
                                                        )
                                                    "
                                                    accept="image/*"
                                                />
                                                <div class="media-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        @click="
                                                            document
                                                                .getElementById(
                                                                    'gallery-image-upload-' +
                                                                        index +
                                                                        '-' +
                                                                        imageIndex
                                                                )
                                                                .click()
                                                        "
                                                    >
                                                        <i class="fas fa-upload"></i> Hochladen
                                                    </button>
                                                    <button
                                                        v-if="image.src"
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        @click="
                                                            removeGalleryImageSrc(index, imageIndex)
                                                        "
                                                    >
                                                        <i class="fas fa-trash"></i> Entfernen
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Bildtitel</label>
                                                <input
                                                    v-model="image.title"
                                                    type="text"
                                                    class="form-control"
                                                />
                                            </div>

                                            <div class="form-group">
                                                <label>Beschreibung</label>
                                                <textarea
                                                    v-model="image.caption"
                                                    class="form-control"
                                                    rows="2"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Call-to-Action Block -->
                            <div v-else-if="element.type === 'cta'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Text</label>
                                    <textarea
                                        v-model="element.content.text"
                                        class="form-control"
                                        rows="3"
                                    ></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Button-Text</label>
                                    <input
                                        v-model="element.content.buttonText"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Button-Ziel</label>
                                    <select
                                        v-model="element.content.buttonTarget"
                                        class="form-control"
                                    >
                                        <option value="">Kein Ziel</option>
                                        <option
                                            v-for="page in pages"
                                            :key="page.id"
                                            :value="page.id"
                                        >
                                            {{ page.title }}
                                        </option>
                                        <option value="contact">Kontaktformular</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundbild</label>
                                    <div class="media-selector">
                                        <div v-if="element.content.bgImage" class="selected-media">
                                            <img
                                                :src="getMediaUrl(element.content.bgImage)"
                                                alt="Hintergrundbild"
                                            />
                                        </div>
                                        <input
                                            type="file"
                                            :id="'cta-bg-image-upload-' + index"
                                            class="hidden-upload"
                                            @change="uploadBlockImage($event, index, 'bgImage')"
                                            accept="image/*"
                                        />
                                        <div class="media-actions">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                @click="
                                                    document
                                                        .getElementById(
                                                            'cta-bg-image-upload-' + index
                                                        )
                                                        .click()
                                                "
                                            >
                                                <i class="fas fa-upload"></i> Hochladen
                                            </button>
                                            <button
                                                v-if="element.content.bgImage"
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                @click="removeBlockImage(index, 'bgImage')"
                                            >
                                                <i class="fas fa-trash"></i> Entfernen
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                    <small class="form-text"
                                        >Wird verwendet, wenn kein Hintergrundbild festgelegt ist
                                        oder als Overlay.</small
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Stil</label>
                                    <select v-model="element.content.style" class="form-control">
                                        <option value="standard">Standard</option>
                                        <option value="prominent">Hervorgehoben</option>
                                        <option value="fullwidth">Volle Breite</option>
                                    </select>
                                </div>
                            </div>

                            <!-- FAQ Block -->
                            <div v-else-if="element.type === 'faq'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Einleitung</label>
                                    <textarea
                                        v-model="element.content.introduction"
                                        class="form-control"
                                        rows="2"
                                    ></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe (Allgemein)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Überschriftfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.titleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Einleitungsfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.introductionColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Rahmenfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.borderColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Fragefarbe (Hintergrund)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.questionBgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Fragefarbe (Text)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.questionColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Antwortfarbe (Hintergrund)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.answerBgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Antwortfarbe (Text)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.answerColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="faq-items">
                                    <h4>FAQ-Einträge</h4>
                                    <button
                                        @click="addFaqItem(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> FAQ-Eintrag hinzufügen
                                    </button>

                                    <div
                                        v-for="(item, itemIndex) in element.content.items"
                                        :key="itemIndex"
                                        class="faq-item"
                                    >
                                        <div class="faq-item-header">
                                            <h5>Frage {{ itemIndex + 1 }}</h5>
                                            <div class="faq-item-actions">
                                                <button
                                                    @click="moveFaqItem(index, itemIndex, -1)"
                                                    class="btn-icon"
                                                    :disabled="itemIndex === 0"
                                                >
                                                    <i class="mdi mdi-arrow-up"></i>
                                                </button>
                                                <button
                                                    @click="moveFaqItem(index, itemIndex, 1)"
                                                    class="btn-icon"
                                                    :disabled="
                                                        itemIndex ===
                                                        element.content.items.length - 1
                                                    "
                                                >
                                                    <i class="mdi mdi-arrow-down"></i>
                                                </button>
                                                <button
                                                    @click="removeFaqItem(index, itemIndex)"
                                                    class="btn-icon"
                                                >
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Frage</label>
                                            <input
                                                v-model="item.question"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Antwort</label>
                                            <textarea
                                                v-model="item.answer"
                                                class="form-control"
                                                rows="6"
                                                @input="onChange"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Tables Block -->
                            <div v-else-if="element.type === 'pricing'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitel</label>
                                    <input
                                        v-model="element.content.subtitle"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe (Allgemein)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Titelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.titleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.subtitleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Stil</label>
                                    <select v-model="element.content.style" class="form-control">
                                        <option value="cards">Karten</option>
                                        <option value="table">Tabelle</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Währung</label>
                                    <input
                                        v-model="element.content.currency"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="pricing-plans">
                                    <h4>Preispläne</h4>
                                    <button
                                        @click="addPricingPlan(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> Preisplan hinzufügen
                                    </button>

                                    <div
                                        v-for="(plan, planIndex) in element.content.plans"
                                        :key="planIndex"
                                        class="pricing-plan-item"
                                    >
                                        <div class="pricing-plan-header">
                                            <h5>Plan {{ planIndex + 1 }}</h5>
                                            <div class="pricing-plan-actions">
                                                <button
                                                    @click="movePricingPlan(index, planIndex, -1)"
                                                    class="btn-icon"
                                                    :disabled="planIndex === 0"
                                                >
                                                    <i class="mdi mdi-arrow-left"></i>
                                                </button>
                                                <button
                                                    @click="movePricingPlan(index, planIndex, 1)"
                                                    class="btn-icon"
                                                    :disabled="
                                                        planIndex ===
                                                        element.content.plans.length - 1
                                                    "
                                                >
                                                    <i class="mdi mdi-arrow-right"></i>
                                                </button>
                                                <button
                                                    @click="removePricingPlan(index, planIndex)"
                                                    class="btn-icon"
                                                >
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Name</label>
                                            <input
                                                v-model="plan.name"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Preis</label>
                                            <input
                                                v-model="plan.price"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Beschreibung</label>
                                            <input
                                                v-model="plan.description"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Hervorheben</label>
                                            <div class="toggle-switch">
                                                <input
                                                    type="checkbox"
                                                    :id="
                                                        'plan-highlight-toggle-' +
                                                        index +
                                                        '-' +
                                                        planIndex
                                                    "
                                                    v-model="plan.highlighted"
                                                />
                                                <label
                                                    :for="
                                                        'plan-highlight-toggle-' +
                                                        index +
                                                        '-' +
                                                        planIndex
                                                    "
                                                ></label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Hintergrundfarbe</label>
                                            <input
                                                type="color"
                                                v-model="plan.bgColor"
                                                class="form-control color-picker"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Textfarbe</label>
                                            <input
                                                type="color"
                                                v-model="plan.textColor"
                                                class="form-control color-picker"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Button-Text</label>
                                            <input
                                                v-model="plan.buttonText"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Button-Ziel</label>
                                            <select v-model="plan.buttonUrl" class="form-control">
                                                <option value="">Kein Ziel</option>
                                                <option
                                                    v-for="page in pages"
                                                    :key="page.id"
                                                    :value="page.id"
                                                >
                                                    {{ page.title }}
                                                </option>
                                                <option value="contact">Kontaktformular</option>
                                            </select>
                                        </div>

                                        <div class="plan-features">
                                            <h5>Features</h5>
                                            <div
                                                v-for="(feature, featureIndex) in plan.features"
                                                :key="featureIndex"
                                                class="plan-feature-item"
                                            >
                                                <div class="feature-input-group">
                                                    <input
                                                        v-model="plan.features[featureIndex]"
                                                        type="text"
                                                        class="form-control"
                                                    />
                                                    <button
                                                        @click="
                                                            removePlanFeature(
                                                                index,
                                                                planIndex,
                                                                featureIndex
                                                            )
                                                        "
                                                        class="btn-icon"
                                                    >
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <button
                                                @click="addPlanFeature(index, planIndex)"
                                                class="btn btn-sm btn-secondary mt-2"
                                            >
                                                <i class="mdi mdi-plus"></i> Feature hinzufügen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Block -->
                            <div v-else-if="element.type === 'stats'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitel</label>
                                    <input
                                        v-model="element.content.subtitle"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe (Allgemein)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Titelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.titleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.subtitleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Layout</label>
                                    <select v-model="element.content.layout" class="form-control">
                                        <option value="grid">Raster</option>
                                        <option value="row">Zeile</option>
                                    </select>
                                </div>

                                <div class="stats-items">
                                    <h4>Statistiken</h4>
                                    <button
                                        @click="addStatItem(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> Statistik hinzufügen
                                    </button>

                                    <div
                                        v-for="(stat, statIndex) in element.content.items"
                                        :key="statIndex"
                                        class="stat-item"
                                    >
                                        <div class="stat-item-header">
                                            <h5>Statistik {{ statIndex + 1 }}</h5>
                                            <button
                                                @click="removeStatItem(index, statIndex)"
                                                class="btn-icon"
                                            >
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>

                                        <div class="form-group">
                                            <label>Wert</label>
                                            <input
                                                v-model="stat.value"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Beschriftung</label>
                                            <input
                                                v-model="stat.label"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Icon</label>
                                            <input
                                                v-model="stat.icon"
                                                type="text"
                                                class="form-control"
                                                placeholder="mdi-icon-name"
                                            />
                                            <small class="form-text"
                                                >Material Design Icons Namen verwenden (z.B.
                                                mdi-account)</small
                                            >
                                        </div>

                                        <div class="form-group">
                                            <label>Icon-Farbe</label>
                                            <input
                                                type="color"
                                                v-model="stat.iconColor"
                                                class="form-control color-picker"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Features Block -->
                            <div v-else-if="element.type === 'features'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitel</label>
                                    <input
                                        v-model="element.content.subtitle"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe (Allgemein)</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Titelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.titleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitelfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.subtitleColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Layout</label>
                                    <select v-model="element.content.layout" class="form-control">
                                        <option value="grid">Raster</option>
                                        <option value="list">Liste</option>
                                        <option value="cards">Karten</option>
                                    </select>
                                </div>

                                <div class="form-group" v-if="element.content.layout === 'grid'">
                                    <label>Spalten pro Zeile</label>
                                    <select
                                        v-model="element.content.columnsPerRow"
                                        class="form-control"
                                    >
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                    </select>
                                </div>

                                <div class="feature-items">
                                    <h4>Features</h4>
                                    <button
                                        @click="addFeatureItem(index)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="mdi mdi-plus"></i> Feature hinzufügen
                                    </button>

                                    <div
                                        v-for="(feature, featureIndex) in element.content.features"
                                        :key="featureIndex"
                                        class="feature-item"
                                    >
                                        <div class="feature-item-header">
                                            <h5>Feature {{ featureIndex + 1 }}</h5>
                                            <button
                                                @click="removeFeatureItem(index, featureIndex)"
                                                class="btn-icon"
                                            >
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>

                                        <div class="form-group">
                                            <label>Titel</label>
                                            <input
                                                v-model="feature.title"
                                                type="text"
                                                class="form-control"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Beschreibung</label>
                                            <textarea
                                                v-model="feature.description"
                                                class="form-control"
                                                rows="3"
                                            ></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label>Icon</label>
                                            <input
                                                v-model="feature.icon"
                                                type="text"
                                                class="form-control"
                                                placeholder="mdi-icon-name"
                                            />
                                            <small class="form-text"
                                                >Material Design Icons Namen verwenden (z.B.
                                                mdi-rocket-launch)</small
                                            >
                                        </div>

                                        <div class="form-group">
                                            <label>Icon-Farbe</label>
                                            <input
                                                type="color"
                                                v-model="feature.iconColor"
                                                class="form-control color-picker"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label>Bild (optional)</label>
                                            <div class="media-selector">
                                                <div v-if="feature.image" class="selected-media">
                                                    <img
                                                        :src="getMediaUrl(feature.image)"
                                                        alt="Feature-Bild"
                                                    />
                                                </div>
                                                <input
                                                    type="file"
                                                    :id="
                                                        'feature-image-upload-' +
                                                        index +
                                                        '-' +
                                                        featureIndex
                                                    "
                                                    class="hidden-upload"
                                                    @change="
                                                        uploadFeatureImage(
                                                            $event,
                                                            index,
                                                            featureIndex
                                                        )
                                                    "
                                                    accept="image/*"
                                                />
                                                <div class="media-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        @click="
                                                            document
                                                                .getElementById(
                                                                    'feature-image-upload-' +
                                                                        index +
                                                                        '-' +
                                                                        featureIndex
                                                                )
                                                                .click()
                                                        "
                                                    >
                                                        <i class="fas fa-upload"></i> Hochladen
                                                    </button>
                                                    <button
                                                        v-if="feature.image"
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        @click="
                                                            removeFeatureImage(index, featureIndex)
                                                        "
                                                    >
                                                        <i class="fas fa-trash"></i> Entfernen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Countdown Timer Block -->
                            <div v-else-if="element.type === 'countdown'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Untertitel</label>
                                    <input
                                        v-model="element.content.subtitle"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Enddatum</label>
                                    <input
                                        type="date"
                                        v-model="element.content.endDate"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Endzeit</label>
                                    <input
                                        type="time"
                                        v-model="element.content.endTime"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Hintergrundfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.bgColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Textfarbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.textColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Timer-Farbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.timerColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Beschriftungs-Farbe</label>
                                    <input
                                        type="color"
                                        v-model="element.content.labelColor"
                                        class="form-control color-picker"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Stil</label>
                                    <select v-model="element.content.style" class="form-control">
                                        <option value="standard">Standard</option>
                                        <option value="minimal">Minimal</option>
                                        <option value="detailed">Detailliert</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Anzeigeoptionen</label>
                                    <div class="checkbox-group">
                                        <div class="checkbox-item">
                                            <input
                                                type="checkbox"
                                                :id="'show-days-' + index"
                                                v-model="element.content.showDays"
                                            />
                                            <label :for="'show-days-' + index">Tage anzeigen</label>
                                        </div>
                                        <div class="checkbox-item">
                                            <input
                                                type="checkbox"
                                                :id="'show-hours-' + index"
                                                v-model="element.content.showHours"
                                            />
                                            <label :for="'show-hours-' + index"
                                                >Stunden anzeigen</label
                                            >
                                        </div>
                                        <div class="checkbox-item">
                                            <input
                                                type="checkbox"
                                                :id="'show-minutes-' + index"
                                                v-model="element.content.showMinutes"
                                            />
                                            <label :for="'show-minutes-' + index"
                                                >Minuten anzeigen</label
                                            >
                                        </div>
                                        <div class="checkbox-item">
                                            <input
                                                type="checkbox"
                                                :id="'show-seconds-' + index"
                                                v-model="element.content.showSeconds"
                                            />
                                            <label :for="'show-seconds-' + index"
                                                >Sekunden anzeigen</label
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Button-Text</label>
                                    <input
                                        v-model="element.content.buttonText"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Button-Ziel</label>
                                    <select
                                        v-model="element.content.buttonUrl"
                                        class="form-control"
                                    >
                                        <option value="">Kein Ziel</option>
                                        <option
                                            v-for="page in pages"
                                            :key="page.id"
                                            :value="page.id"
                                        >
                                            {{ page.title }}
                                        </option>
                                        <option value="contact">Kontaktformular</option>
                                    </select>
                                </div>

                                <div class="countdown-preview">
                                    <h4>Vorschau</h4>
                                    <div class="countdown-container" :class="element.content.style">
                                        <div v-if="element.content.showDays" class="countdown-item">
                                            <div
                                                class="countdown-value"
                                                :style="{ color: element.content.timerColor }"
                                            >
                                                30
                                            </div>
                                            <div
                                                class="countdown-label"
                                                :style="{ color: element.content.labelColor }"
                                            >
                                                Tage
                                            </div>
                                        </div>
                                        <div
                                            v-if="element.content.showHours"
                                            class="countdown-item"
                                        >
                                            <div
                                                class="countdown-value"
                                                :style="{ color: element.content.timerColor }"
                                            >
                                                12
                                            </div>
                                            <div
                                                class="countdown-label"
                                                :style="{ color: element.content.labelColor }"
                                            >
                                                Stunden
                                            </div>
                                        </div>
                                        <div
                                            v-if="element.content.showMinutes"
                                            class="countdown-item"
                                        >
                                            <div
                                                class="countdown-value"
                                                :style="{ color: element.content.timerColor }"
                                            >
                                                30
                                            </div>
                                            <div
                                                class="countdown-label"
                                                :style="{ color: element.content.labelColor }"
                                            >
                                                Minuten
                                            </div>
                                        </div>
                                        <div
                                            v-if="element.content.showSeconds"
                                            class="countdown-item"
                                        >
                                            <div
                                                class="countdown-value"
                                                :style="{ color: element.content.timerColor }"
                                            >
                                                00
                                            </div>
                                            <div
                                                class="countdown-label"
                                                :style="{ color: element.content.labelColor }"
                                            >
                                                Sekunden
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optimizer Block -->
                            <div v-else-if="element.type === 'optimizer'" class="block-form">
                                <div class="form-group">
                                    <label>{{ t('pageEditor.heading') }}</label>
                                    <input
                                        v-model="element.content.title"
                                        type="text"
                                        class="form-control"
                                    />
                                </div>

                                <div class="form-group">
                                    <label>Beschreibung</label>
                                    <textarea
                                        v-model="element.content.description"
                                        class="form-control"
                                        rows="2"
                                    ></textarea>
                                </div>

                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-label">
                                            Lazy-Loading für Bilder aktivieren
                                        </div>
                                        <div class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :id="'lazy-load-toggle-' + index"
                                                v-model="element.content.lazyLoadImages"
                                            />
                                            <label :for="'lazy-load-toggle-' + index"></label>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        Bilder werden erst geladen, wenn sie im sichtbaren Bereich
                                        erscheinen. Dies verbessert die initiale Ladezeit der Seite.
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-label">Bilder optimieren</div>
                                        <div class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :id="'optimize-images-toggle-' + index"
                                                v-model="element.content.optimizeImages"
                                            />
                                            <label :for="'optimize-images-toggle-' + index"></label>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="element.content.optimizeImages"
                                    class="image-optimization-settings"
                                >
                                    <div class="form-group">
                                        <label
                                            >Bildqualität ({{
                                                element.content.imageQuality
                                            }}%)</label
                                        >
                                        <input
                                            type="range"
                                            v-model.number="element.content.imageQuality"
                                            class="form-control range-slider"
                                            min="10"
                                            max="100"
                                            step="5"
                                        />
                                    </div>

                                    <div class="form-group">
                                        <label>Maximale Bildbreite (in Pixel)</label>
                                        <input
                                            type="number"
                                            v-model.number="element.content.maxImageWidth"
                                            class="form-control"
                                            min="320"
                                            max="3840"
                                        />
                                    </div>

                                    <div class="form-group">
                                        <div class="toggle-container">
                                            <div class="toggle-label">
                                                WebP-Komprimierung verwenden
                                            </div>
                                            <div class="toggle-switch">
                                                <input
                                                    type="checkbox"
                                                    :id="'webp-toggle-' + index"
                                                    v-model="element.content.compressWebp"
                                                />
                                                <label :for="'webp-toggle-' + index"></label>
                                            </div>
                                        </div>
                                        <div class="form-text">
                                            WebP bietet bessere Kompression als JPEG und PNG bei
                                            gleicher Bildqualität.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-label">Browser-Caching aktivieren</div>
                                        <div class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :id="'caching-toggle-' + index"
                                                v-model="element.content.enableCaching"
                                            />
                                            <label :for="'caching-toggle-' + index"></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-label">CSS/JS-Dateien minifizieren</div>
                                        <div class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :id="'minify-toggle-' + index"
                                                v-model="element.content.minifyAssets"
                                            />
                                            <label :for="'minify-toggle-' + index"></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="toggle-container">
                                        <div class="toggle-label">
                                            Kritische Ressourcen vorladen
                                        </div>
                                        <div class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :id="'preload-toggle-' + index"
                                                v-model="element.content.preloadCriticalAssets"
                                            />
                                            <label :for="'preload-toggle-' + index"></label>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        Kritische CSS und Fonts werden priorisiert geladen, um
                                        Content-Blocking zu vermeiden.
                                    </div>
                                </div>

                                <div class="optimizer-status">
                                    <h4>Status</h4>
                                    <div class="status-item">
                                        <div class="status-label">Gescannte Bilder:</div>
                                        <div class="status-value">
                                            {{ element.content.status.imagesScanned }}
                                        </div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Optimierungspotential:</div>
                                        <div class="status-value">
                                            {{ element.content.status.optimizationPotential }}
                                        </div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">
                                            Geschätzte Geschwindigkeitsverbesserung:
                                        </div>
                                        <div class="status-value">
                                            {{ element.content.status.estimatedSpeedImprovement }}
                                        </div>
                                    </div>

                                    <button
                                        @click="scanWebsite(index)"
                                        class="btn btn-primary mt-3"
                                    >
                                        <i class="mdi mdi-magnify"></i> Website analysieren
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <div class="add-block-container">
            <button @click="isAddBlockMenuOpen = !isAddBlockMenuOpen" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> Block hinzufügen
            </button>

            <div v-if="isAddBlockMenuOpen" class="add-block-menu">
                <div class="block-type" @click="addBlock('hero')">
                    <i class="mdi mdi-view-carousel"></i> Hero-Bereich
                </div>
                <div class="block-type" @click="addBlock('text')">
                    <i class="mdi mdi-text-box"></i> Textblock
                </div>
                <div class="block-type" @click="addBlock('columns')">
                    <i class="mdi mdi-view-column"></i> Spalten
                </div>
                <div class="block-type" @click="addBlock('team')">
                    <i class="mdi mdi-account-group"></i> Team
                </div>
                <div class="block-type" @click="addBlock('testimonials')">
                    <i class="mdi mdi-format-quote-close"></i> Testimonials
                </div>
                <div class="block-type" @click="addBlock('gallery')">
                    <i class="mdi mdi-image-multiple"></i> Galerie
                </div>
                <div class="block-type" @click="addBlock('cta')">
                    <i class="mdi mdi-bullhorn"></i> Call-to-Action
                </div>
                <div class="block-type" @click="addBlock('faq')">
                    <i class="mdi mdi-help-circle-outline"></i> FAQ
                </div>
                <div class="block-type" @click="addBlock('pricing')">
                    <i class="mdi mdi-currency-eur"></i> Preistabellen
                </div>
                <div class="block-type" @click="addBlock('stats')">
                    <i class="mdi mdi-chart-bar"></i> Statistiken
                </div>
                <div class="block-type" @click="addBlock('features')">
                    <i class="mdi mdi-star-outline"></i> Feature-Boxen
                </div>
                <div class="block-type" @click="addBlock('countdown')">
                    <i class="mdi mdi-timer-outline"></i> Countdown-Timer
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { v4 as uuidv4 } from 'uuid';
import { useI18n } from 'vue-i18n';

interface ColorScheme {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    text: string;
    bannerBackground: string;
    bannerText: string;
    heroBackground: string;
    heroText: string;
    buttonBackground: string;
    buttonText: string;
}

interface Props {
    modelValue?: any[];
    pages?: any[];
    websiteId: number;
    colorScheme?: ColorScheme; // Neue Prop für das Farbschema
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    pages: () => [],
    colorScheme: () => ({
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: 'var(--k-accent)',
        bannerText: '#ffffff',
        heroBackground: 'rgba(96, 165, 250, 0.8)',
        heroText: '#ffffff',
        buttonBackground: 'var(--k-accent)',
        buttonText: '#ffffff',
    }),
});

const emit = defineEmits(['update:modelValue', 'block-image-upload']);

const blocks = computed({
    get: () => props.modelValue || [],
    set: value => emit('update:modelValue', value),
});

const activeBlockIndex = ref(null);
const isAddBlockMenuOpen = ref(false);
const { t } = useI18n();

function toggleBlock(index) {
    activeBlockIndex.value = activeBlockIndex.value === index ? null : index;
}

function getBlockTitle(block) {
    const titles = {
        hero: 'Hero-Bereich',
        text: 'Textblock',
        columns: `Spalten (${block.content.columnCount || 2})`,
        team: 'Team',
        testimonials: 'Testimonials',
        gallery: 'Galerie',
        cta: 'Call-to-Action',
        faq: 'FAQ',
        pricing: 'Preistabellen',
        stats: 'Statistiken',
        features: 'Feature-Boxen',
    };

    let title = titles[block.type] || 'Block';

    if (block.content.title) {
        title += `: ${block.content.title}`;
    }

    return title;
}

function addBlock(type) {
    const newBlock = {
        id: uuidv4(),
        type,
        content: getDefaultContent(type),
    };

    blocks.value.push(newBlock);
    activeBlockIndex.value = blocks.value.length - 1;
    isAddBlockMenuOpen.value = false;

    onChange();
}

function getDefaultContent(type) {
    // Verwende das übergebene colorScheme oder definiere Standardwerte
    const scheme = props.colorScheme || {
        primary: 'var(--k-accent)',
        secondary: 'var(--k-accent)',
        accent: 'var(--k-accent-line)',
        background: '#ffffff',
        text: '#111827',
        bannerBackground: 'var(--k-accent)',
        bannerText: '#ffffff',
        heroBackground: 'rgba(96, 165, 250, 0.8)',
        heroText: '#ffffff',
        buttonBackground: 'var(--k-accent)',
        buttonText: '#ffffff'
    };
    
    switch (type) {
        case 'hero':
            return {
                title: t('website.defaultHeroTitle'),
                subtitle: t('website.defaultHeroSubtitle'),
                bgImage: null,
                bgColor: scheme.heroBackground || scheme.primary || 'var(--k-accent-hover)',
                parallax: true,
                buttonText: '',
                buttonTarget: '',
            };
        case 'text':
            return {
                title: 'Überschrift',
                text: 'Hier Text eingeben...',
                bgColor: scheme.background || '#ffffff',
            };
        case 'columns':
            return {
                title: 'Spalten-Überschrift',
                columnCount: 3,
                bgColor: scheme.background || '#f8fafc',
                columns: [
                    { title: 'Spalte 1', text: 'Inhalt der ersten Spalte...' },
                    { title: 'Spalte 2', text: 'Inhalt der zweiten Spalte...' },
                    { title: 'Spalte 3', text: 'Inhalt der dritten Spalte...' },
                ],
            };
        case 'team':
            return {
                title: 'Unser Team',
                description: 'Lernen Sie unser Team kennen.',
                bgColor: scheme.background || '#ffffff',
                textColor: scheme.text || '#111827',
                members: [
                    {
                        name: 'Max Mustermann',
                        position: 'CEO',
                        bio: 'Kurze Biografie...',
                        image: null,
                    },
                ],
            };
        case 'testimonials':
            return {
                title: 'Kundenstimmen',
                bgColor: scheme.background || '#ffffff',
                textColor: scheme.text || '#111827',
                items: [
                    {
                        name: 'Kunde 1',
                        position: 'Firma',
                        quote: 'Ein tolles Unternehmen!',
                        image: null,
                    },
                ],
            };
        case 'gallery':
            return {
                title: 'Bildergalerie',
                description: 'Hier sehen Sie unsere Bilder',
                layout: 'grid',
                bgColor: scheme.background || '#ffffff',
                textColor: scheme.text || '#111827',
                images: [{ src: null, title: 'Bild 1', caption: 'Beschreibung des Bildes' }],
            };
        case 'cta':
            return {
                title: 'Handlungsaufforderung',
                text: 'Beschreibungstext für Ihre Call-to-Action. Motivieren Sie Besucher zur Interaktion.',
                buttonText: 'Jetzt starten',
                buttonTarget: '',
                bgImage: null,
                bgColor: scheme.primary || 'var(--k-accent-hover)',
                textColor: scheme.bannerText || '#ffffff',
                style: 'standard',
            };
        case 'faq':
            return {
                title: 'Häufig gestellte Fragen',
                introduction: 'Hier finden Sie Antworten auf die am häufigsten gestellten Fragen.',
                bgColor: scheme.background || '#f8fafc',
                textColor: scheme.text || '#111827',
                titleColor: scheme.primary || 'var(--k-accent)',
                introductionColor: scheme.text || '#4b5563',
                questionBgColor: scheme.accent || '#e5e7eb',
                questionColor: scheme.text || '#111827',
                answerBgColor: scheme.background || '#ffffff',
                answerColor: scheme.text || '#374151',
                borderColor: scheme.accent || '#d1d5db',
                items: [
                    {
                        question: 'Was macht Ihr Unternehmen?',
                        answer: 'Hier steht Ihre Antwort. Sie können diese beliebig formatieren und gestalten.',
                    },
                    {
                        question: 'Wie kann ich Kontakt aufnehmen?',
                        answer: 'Nutzen Sie unser Kontaktformular oder rufen Sie uns direkt an.',
                    },
                    {
                        question: 'Welche Dienstleistungen bieten Sie an?',
                        answer: 'Eine Übersicht unserer Dienstleistungen finden Sie hier...',
                    },
                ],
            };
        case 'pricing':
            return {
                title: 'Unsere Preise',
                subtitle: 'Wählen Sie das passende Paket für Ihre Bedürfnisse',
                bgColor: scheme.background || '#f8fafc',
                textColor: scheme.text || '#111827',
                titleColor: scheme.primary || 'var(--k-accent)',
                subtitleColor: scheme.text || '#4b5563',
                style: 'cards', // cards, table
                comparison: false,
                currency: '€',
                showBillingToggle: false,
                billingPeriods: ['monatlich', 'jährlich'],
                selectedBillingPeriod: 'monatlich',
                plans: [
                    {
                        name: 'Basis',
                        price: '9.99',
                        description: 'Perfekt für Einsteiger',
                        features: ['Feature 1', 'Feature 2', 'Feature 3'],
                        buttonText: 'Jetzt auswählen',
                        buttonUrl: '',
                        highlighted: false,
                        bgColor: scheme.background || '#ffffff',
                        textColor: scheme.text || '#000000',
                    },
                    {
                        name: 'Professional',
                        price: '19.99',
                        description: 'Ideal für kleinere Unternehmen',
                        features: ['Alles aus Basis', 'Feature 4', 'Feature 5', 'Feature 6'],
                        buttonText: 'Jetzt auswählen',
                        buttonUrl: '',
                        highlighted: true,
                        bgColor: scheme.primary || 'var(--k-accent)',
                        textColor: scheme.bannerText || '#ffffff',
                    },
                    {
                        name: 'Enterprise',
                        price: '49.99',
                        description: 'Für größere Unternehmen',
                        features: ['Alles aus Professional', 'Feature 7', 'Feature 8', 'Feature 9'],
                        buttonText: 'Jetzt auswählen',
                        buttonUrl: '',
                        highlighted: false,
                        bgColor: scheme.background || '#ffffff',
                        textColor: scheme.text || '#000000',
                    },
                ],
            };
        case 'stats':
            return {
                title: 'Unsere Erfolge in Zahlen',
                subtitle: 'Die folgenden Statistiken zeigen unsere Erfolge',
                bgColor: scheme.background || '#f8fafc',
                textColor: scheme.text || '#111827',
                titleColor: scheme.primary || 'var(--k-accent)',
                subtitleColor: scheme.text || '#4b5563',
                layout: 'grid', // 'grid' oder 'row'
                items: [
                    {
                        value: '500+',
                        label: 'Kunden',
                        icon: 'mdi-account-group',
                        iconColor: scheme.primary || 'var(--k-accent)',
                    },
                    {
                        value: '1000+',
                        label: 'Projekte',
                        icon: 'mdi-briefcase',
                        iconColor: scheme.secondary || '#10b981',
                    },
                    {
                        value: '5',
                        label: 'Jahre Erfahrung',
                        icon: 'mdi-calendar-check',
                        iconColor: scheme.accent || '#f59e0b',
                    },
                    {
                        value: '98%',
                        label: 'Kundenzufriedenheit',
                        icon: 'mdi-thumb-up',
                        iconColor: scheme.primary || '#ef4444',
                    },
                ],
            };
        case 'features':
            return {
                title: 'Unsere Leistungen',
                subtitle: 'Entdecken Sie, was wir für Sie tun können',
                bgColor: scheme.background || '#f8fafc',
                textColor: scheme.text || '#111827',
                titleColor: scheme.primary || 'var(--k-accent)',
                subtitleColor: scheme.text || '#4b5563',
                layout: 'grid', // 'grid', 'list', oder 'cards'
                columnsPerRow: 3,
                features: [
                    {
                        title: 'Feature 1',
                        description:
                            'Beschreibungstext für dieses Feature. Erklären Sie, welchen Nutzen es bietet.',
                        icon: 'mdi-rocket-launch',
                        iconColor: scheme.primary || 'var(--k-accent)',
                        image: null,
                    },
                    {
                        title: 'Feature 2',
                        description:
                            'Beschreibungstext für dieses Feature. Erklären Sie, welchen Nutzen es bietet.',
                        icon: 'mdi-shield-check',
                        iconColor: scheme.secondary || '#10b981',
                        image: null,
                    },
                    {
                        title: 'Feature 3',
                        description:
                            'Beschreibungstext für dieses Feature. Erklären Sie, welchen Nutzen es bietet.',
                        icon: 'mdi-chart-line',
                        iconColor: scheme.accent || '#f59e0b',
                        image: null,
                    },
                    {
                        title: 'Feature 4',
                        description:
                            'Beschreibungstext für dieses Feature. Erklären Sie, welchen Nutzen es bietet.',
                        icon: 'mdi-cog',
                        iconColor: scheme.primary || '#ef4444',
                        image: null,
                    },
                ],
            };
        case 'countdown':
            return {
                title: 'Event-Countdown',
                subtitle: 'Nicht verpassen!',
                bgColor: scheme.background || '#f8fafc',
                textColor: scheme.text || '#111827',
                endDate: new Date(new Date().setDate(new Date().getDate() + 30))
                    .toISOString()
                    .split('T')[0], // 30 Tage ab heute
                endTime: '12:00',
                showDays: true,
                showHours: true,
                showMinutes: true,
                showSeconds: true,
                style: 'standard', // 'standard', 'minimal', 'detailed'
                buttonText: 'Mehr erfahren',
                buttonUrl: '',
                timerColor: scheme.primary || 'var(--k-accent)',
                labelColor: scheme.text || '#4b5563',
            };
        default:
            return {};
    }
}

function removeBlock(index) {
    if (confirm(t('pageBlockEditor.deleteBlockConfirm'))) {
        blocks.value.splice(index, 1);
        activeBlockIndex.value = null;
        onChange();
    }
}

function duplicateBlock(index) {
    const original = blocks.value[index];
    const duplicate = JSON.parse(JSON.stringify(original));
    duplicate.id = uuidv4();

    blocks.value.splice(index + 1, 0, duplicate);
    activeBlockIndex.value = index + 1;
    onChange();
}

function updateColumns(index) {
    const block = blocks.value[index];
    const columnCount = parseInt(block.content.columnCount);

    // Adjust columns array based on the new count
    if (block.content.columns.length > columnCount) {
        // Remove extra columns
        block.content.columns = block.content.columns.slice(0, columnCount);
    } else if (block.content.columns.length < columnCount) {
        // Add more columns
        const addCount = columnCount - block.content.columns.length;
        for (let i = 0; i < addCount; i++) {
            const columnNumber = block.content.columns.length + 1;
            block.content.columns.push({
                title: `Spalte ${columnNumber}`,
                text: 'Inhalt der Spalte...',
            });
        }
    }

    onChange();
}

function addTeamMember(index) {
    blocks.value[index].content.members.push({
        name: 'Neues Teammitglied',
        position: 'Position',
        bio: 'Kurze Biografie...',
        image: null,
    });

    onChange();
}

function removeTeamMember(blockIndex, memberIndex) {
    blocks.value[blockIndex].content.members.splice(memberIndex, 1);
    onChange();
}

function addTestimonial(index) {
    blocks.value[index].content.items.push({
        name: 'Neuer Kunde',
        position: 'Firma',
        quote: 'Testimonial-Text...',
        image: null,
    });

    onChange();
}

function removeTestimonial(blockIndex, testimonialIndex) {
    blocks.value[blockIndex].content.items.splice(testimonialIndex, 1);
    onChange();
}

function addGalleryImage(blockIndex) {
    blocks.value[blockIndex].content.images.push({
        src: null,
        title: `Bild ${blocks.value[blockIndex].content.images.length + 1}`,
        caption: 'Beschreibung des Bildes',
    });

    onChange();
}

function removeGalleryImage(blockIndex, imageIndex) {
    blocks.value[blockIndex].content.images.splice(imageIndex, 1);
    onChange();
}

function uploadGalleryImage(event, blockIndex, imageIndex) {
    const file = event.target.files[0];
    if (!file) return;

    emit('block-image-upload', {
        file,
        blockIndex,
        imageIndex,
        type: 'gallery',
    });
}

function removeGalleryImageSrc(blockIndex, imageIndex) {
    blocks.value[blockIndex].content.images[imageIndex].src = null;
    onChange();
}

// Image handling
function uploadBlockImage(event, blockIndex, imageField) {
    const file = event.target.files[0];
    if (!file) return;

    emit('block-image-upload', {
        file,
        blockIndex,
        imageField,
        type: 'block',
    });
}

function uploadTeamMemberImage(event, blockIndex, memberIndex) {
    const file = event.target.files[0];
    if (!file) return;

    emit('block-image-upload', {
        file,
        blockIndex,
        memberIndex,
        type: 'team',
    });
}

function uploadTestimonialImage(event, blockIndex, testimonialIndex) {
    const file = event.target.files[0];
    if (!file) return;

    emit('block-image-upload', {
        file,
        blockIndex,
        testimonialIndex,
        type: 'testimonial',
    });
}

function removeBlockImage(blockIndex, imageField) {
    blocks.value[blockIndex].content[imageField] = null;
    onChange();
}

function removeTeamMemberImage(blockIndex, memberIndex) {
    blocks.value[blockIndex].content.members[memberIndex].image = null;
    onChange();
}

function removeTestimonialImage(blockIndex, testimonialIndex) {
    blocks.value[blockIndex].content.items[testimonialIndex].image = null;
    onChange();
}

function getMediaUrl(fileName) {
    if (!fileName) return '';

    // Assuming there's a getMediaUrl function in the parent component
    return window.getMediaUrl ? window.getMediaUrl(fileName) : fileName;
}

function onChange() {
    emit('update:modelValue', blocks.value);
}

// FAQ-Block Funktionen
function addFaqItem(blockIndex) {
    blocks.value[blockIndex].content.items.push({
        question: 'Neue Frage',
        answer: 'Ihre Antwort hier...',
        questionColor: blocks.value[blockIndex].content.questionColor || '#111827',
        answerColor: blocks.value[blockIndex].content.answerColor || '#333333',
        questionBgColor: blocks.value[blockIndex].content.questionBgColor || '#f5f5f5',
        answerBgColor: blocks.value[blockIndex].content.answerBgColor || '#ffffff',
        iconColor: blocks.value[blockIndex].content.iconColor || '#666666',
    });
    onChange();
}

function removeFaqItem(blockIndex, itemIndex) {
    blocks.value[blockIndex].content.items.splice(itemIndex, 1);
    onChange();
}

function moveFaqItem(blockIndex, itemIndex, direction) {
    const newIndex = itemIndex + direction;
    if (newIndex < 0 || newIndex >= blocks.value[blockIndex].content.items.length) return;

    // Tausche die Positionen
    const items = blocks.value[blockIndex].content.items;
    const temp = items[itemIndex];
    items[itemIndex] = items[newIndex];
    items[newIndex] = temp;

    onChange();
}

// Pricing-Block Funktionen
function addPricingPlan(blockIndex) {
    blocks.value[blockIndex].content.plans.push({
        name: 'Neuer Plan',
        price: '0.00',
        description: 'Beschreibung des Plans',
        features: ['Feature 1', 'Feature 2', 'Feature 3'],
        buttonText: 'Jetzt auswählen',
        buttonUrl: '',
        highlighted: false,
        bgColor: '#ffffff',
        textColor: '#000000',
        nameColor: blocks.value[blockIndex].content.nameColor || '#111827',
        priceColor: blocks.value[blockIndex].content.priceColor || 'var(--k-accent)',
        descriptionColor: blocks.value[blockIndex].content.descriptionColor || '#4b5563',
        featureColor: blocks.value[blockIndex].content.featureColor || '#333333',
        buttonBgColor: blocks.value[blockIndex].content.buttonBgColor || 'var(--k-accent)',
        buttonTextColor: blocks.value[blockIndex].content.buttonTextColor || '#ffffff',
    });
    onChange();
}

function removePricingPlan(blockIndex, planIndex) {
    blocks.value[blockIndex].content.plans.splice(planIndex, 1);
    onChange();
}

function movePricingPlan(blockIndex, planIndex, direction) {
    const newIndex = planIndex + direction;
    if (newIndex < 0 || newIndex >= blocks.value[blockIndex].content.plans.length) return;

    // Tausche die Positionen
    const plans = blocks.value[blockIndex].content.plans;
    const temp = plans[planIndex];
    plans[planIndex] = plans[newIndex];
    plans[newIndex] = temp;

    onChange();
}

function addPlanFeature(blockIndex, planIndex) {
    blocks.value[blockIndex].content.plans[planIndex].features.push('Neues Feature');
    onChange();
}

function removePlanFeature(blockIndex, planIndex, featureIndex) {
    blocks.value[blockIndex].content.plans[planIndex].features.splice(featureIndex, 1);
    onChange();
}

// Stats-Block Funktionen
function addStatItem(blockIndex) {
    blocks.value[blockIndex].content.items.push({
        value: '100+',
        label: 'Neue Statistik',
        icon: 'mdi-star',
        iconColor: 'var(--k-accent)',
        valueColor: blocks.value[blockIndex].content.valueColor || '#111827',
        labelColor: blocks.value[blockIndex].content.labelColor || '#4b5563',
        bgColor: blocks.value[blockIndex].content.itemBgColor || '#ffffff',
        textColor: blocks.value[blockIndex].content.textColor || '#333333',
        highlighted: false,
    });
    onChange();
}

function removeStatItem(blockIndex, statIndex) {
    blocks.value[blockIndex].content.items.splice(statIndex, 1);
    onChange();
}

// Features-Block Funktionen
function addFeatureItem(blockIndex) {
    blocks.value[blockIndex].content.features.push({
        title: 'Neues Feature',
        description: 'Beschreibung des Features',
        icon: 'mdi-star',
        iconColor: 'var(--k-accent)',
        image: null,
        titleColor: blocks.value[blockIndex].content.titleColor || '#111827',
        descriptionColor: blocks.value[blockIndex].content.descriptionColor || '#4b5563',
        bgColor: blocks.value[blockIndex].content.itemBgColor || '#ffffff',
        textColor: blocks.value[blockIndex].content.textColor || '#333333',
        highlighted: false,
    });
    onChange();
}

function removeFeatureItem(blockIndex, featureIndex) {
    blocks.value[blockIndex].content.features.splice(featureIndex, 1);
    onChange();
}

function uploadFeatureImage(event, blockIndex, featureIndex) {
    const file = event.target.files[0];
    if (!file) return;

    emit('block-image-upload', {
        file,
        blockIndex,
        featureIndex,
        type: 'feature',
    });
}

function removeFeatureImage(blockIndex, featureIndex) {
    blocks.value[blockIndex].content.features[featureIndex].image = null;
    onChange();
}

// Hilfsfunktionen für Farbschema
function selectColorScheme(blockIndex, schemeName) {
    const block = blocks.value[blockIndex];
    block.content.selectedScheme = schemeName;
    block.content.useCustomColors = false;

    // Finde das ausgewählte Farbschema und aktualisiere die benutzerdefinierten Farben als Backup
    const selectedScheme = block.content.presetSchemes.find(s => s.name === schemeName);
    if (selectedScheme) {
        block.content.customColors = { ...selectedScheme };
    }

    onChange();
}

// Hilfsfunktion für Ladezeit-Optimierer
function scanWebsite(blockIndex) {
    // Hier würde in einer echten Implementierung ein API-Aufruf erfolgen
    // Für diese Demo simulieren wir einfach eine Analyse

    setTimeout(() => {
        blocks.value[blockIndex].content.status = {
            imagesScanned: 24,
            optimizationPotential: '3.2 MB',
            estimatedSpeedImprovement: '42%',
        };

        onChange();
    }, 1500);
}
</script>

<style scoped>
.page-block-editor {
    margin-top: 20px;
    border: 1px solid var(--k-line);
    border-radius: 6px;
    background-color: var(--k-surface);
    padding: 20px;
}

.blocks-container {
    margin-bottom: 20px;
}

.block-item {
    margin-bottom: 10px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-sunken);
    overflow: hidden;
}

.block-header {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    background-color: #374151;
    cursor: pointer;
}

.block-handle {
    margin-right: 10px;
    cursor: move;
    color: var(--k-ink-faint);
}

.block-title {
    flex: 1;
    font-weight: 500;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.block-actions {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: none;
    color: var(--k-ink-faint);
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-icon:hover {
    color: var(--k-ink);
    background-color: var(--k-row-hover);
}

.block-content {
    padding: 15px;
    border-top: 1px solid #4b5563;
}

.block-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
    color: var(--k-ink);
}

.columns-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
}

.column-item {
    flex: 1;
    min-width: 30%;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.column-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.team-members,
.testimonials {
    margin-top: 15px;
}

.team-member-item,
.testimonial-item {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.member-header,
.testimonial-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.add-block-container {
    position: relative;
    display: inline-block;
}

.add-block-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 3;
    width: 200px;
    margin-top: 5px;
    padding: 10px 0;
    background-color: var(--k-surface);
    border: 1px solid #4b5563;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.block-type {
    padding: 8px 15px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.block-type:hover {
    background-color: #374151;
}

.block-type i {
    margin-right: 10px;
}

.media-selector {
    margin-top: 5px;
}

.selected-media {
    margin-bottom: 10px;
    max-width: 200px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    overflow: hidden;
}

.selected-media img {
    width: 100%;
    height: auto;
    display: block;
}

.hidden-upload {
    display: none;
}

.media-actions {
    display: flex;
    gap: 10px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-switch label {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--k-neutral);
    transition: 0.4s;
    border-radius: 20px;
}

.toggle-switch label:before {
    position: absolute;
    content: '';
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: #fff;
    transition: 0.4s;
    border-radius: 50%;
}

.toggle-switch input:checked + label {
    background-color: var(--k-accent);
}

.toggle-switch input:checked + label:before {
    transform: translateX(20px);
}

.color-picker {
    width: 100%;
    height: 40px;
    padding: 5px;
    cursor: pointer;
    background-color: transparent;
    border: 1px solid #4b5563;
}

.gallery-items-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.gallery-item {
    border: 1px solid #4b5563;
    border-radius: 4px;
    padding: 15px;
    background-color: var(--k-surface);
}

.gallery-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.map-location {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
}

/* FAQ Block Styles */
.faq-items {
    margin-top: 20px;
}

.faq-item {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.faq-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.faq-item-actions {
    display: flex;
    gap: 5px;
}

/* Pricing Block Styles */
.pricing-plans {
    margin-top: 20px;
}

.pricing-plan-item {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.pricing-plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.pricing-plan-actions {
    display: flex;
    gap: 5px;
}

.plan-features {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #4b5563;
}

.plan-feature-item {
    margin-top: 8px;
}

.feature-input-group {
    display: flex;
    gap: 10px;
}

/* Statistics Block Styles */
.stats-items {
    margin-top: 20px;
}

.stat-item {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.stat-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

/* Features Block Styles */
.feature-items {
    margin-top: 20px;
}

.feature-item {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.feature-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

/* Countdown Timer Styles */
.countdown-preview {
    margin-top: 20px;
    padding: 15px;
    border: 1px dashed #4b5563;
    border-radius: 4px;
    background-color: var(--k-surface);
}

.countdown-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 15px;
}

.countdown-item {
    text-align: center;
    min-width: 60px;
}

.countdown-value {
    font-size: 24px;
    font-weight: bold;
}

.countdown-label {
    font-size: 12px;
    margin-top: 5px;
}

.countdown-container.minimal .countdown-value {
    font-size: 20px;
}

.countdown-container.detailed .countdown-item {
    background-color: rgba(0, 0, 0, 0.2);
    padding: 10px;
    border-radius: 4px;
    min-width: 80px;
}

.countdown-container.detailed .countdown-value {
    font-size: 28px;
}

/* Checkbox Group Styles */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
}

.checkbox-item label {
    margin-left: 5px;
    margin-bottom: 0;
}

/* Color Scheme Styles */
.color-schemes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.color-scheme-item {
    border: 1px solid #4b5563;
    border-radius: 4px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.color-scheme-item:hover {
    background-color: var(--k-row-hover);
}

.color-scheme-item.selected {
    border-color: var(--k-accent);
    background-color: var(--k-accent-weak);
}

.scheme-colors {
    display: flex;
    height: 20px;
    margin-bottom: 10px;
    border-radius: 2px;
    overflow: hidden;
}

.scheme-color {
    flex: 1;
}

.scheme-name {
    text-align: center;
    font-size: 12px;
}

.toggle-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toggle-label {
    font-weight: 500;
}

.form-text {
    margin-top: 5px;
    font-size: 12px;
    color: var(--k-ink-faint);
}

.custom-colors-section {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-row-hover);
}

/* Optimizer Styles */
.range-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 8px;
    background: #374151;
    outline: none;
    border-radius: 4px;
    cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: var(--k-accent);
    border-radius: 50%;
    cursor: pointer;
}

.range-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: var(--k-accent);
    border-radius: 50%;
    cursor: pointer;
}

.image-optimization-settings {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #4b5563;
    border-radius: 4px;
    background-color: var(--k-row-hover);
}

.optimizer-status {
    margin-top: 20px;
    padding: 15px;
    border: 1px dashed #4b5563;
    border-radius: 4px;
    background-color: var(--k-row-hover);
}

.status-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #4b5563;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
}

.status-value {
    color: var(--k-accent);
    font-weight: 500;
}

.mt-2 {
    margin-top: 8px;
}

.mt-3 {
    margin-top: 16px;
}
</style>
