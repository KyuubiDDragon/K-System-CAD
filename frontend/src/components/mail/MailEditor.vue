<template>
  <div class="mail-editor">
    <!-- Toolbar -->
    <v-toolbar density="compact" class="editor-toolbar" color="surface">
      <v-btn-toggle v-model="activeFormats" multiple density="compact" divided>
        <!-- Text formatting -->
        <v-tooltip text="Bold (Ctrl+B)" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleBold().run()"
              :class="{ 'is-active': editor?.isActive('bold') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-bold</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Italic (Ctrl+I)" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleItalic().run()"
              :class="{ 'is-active': editor?.isActive('italic') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-italic</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Underline (Ctrl+U)" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleUnderline().run()"
              :class="{ 'is-active': editor?.isActive('underline') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-underline</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Strikethrough" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleStrike().run()"
              :class="{ 'is-active': editor?.isActive('strike') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-strikethrough</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </v-btn-toggle>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Headings -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" size="small" variant="text">
            <v-icon>mdi-format-header-pound</v-icon>
            <v-icon size="small">mdi-menu-down</v-icon>
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item @click="editor?.chain().focus().setParagraph().run()">
            <v-list-item-title>Paragraph</v-list-item-title>
          </v-list-item>
          <v-list-item @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()">
            <v-list-item-title>Heading 1</v-list-item-title>
          </v-list-item>
          <v-list-item @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
            <v-list-item-title>Heading 2</v-list-item-title>
          </v-list-item>
          <v-list-item @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
            <v-list-item-title>Heading 3</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Lists -->
      <v-btn-toggle v-model="activeFormats" multiple density="compact" divided>
        <v-tooltip :text="$t('mail.bulletList')" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleBulletList().run()"
              :class="{ 'is-active': editor?.isActive('bulletList') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-list-bulleted</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip :text="$t('mail.numberedList')" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().toggleOrderedList().run()"
              :class="{ 'is-active': editor?.isActive('orderedList') }"
              size="small"
              icon
            >
              <v-icon>mdi-format-list-numbered</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </v-btn-toggle>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Alignment -->
      <v-btn-toggle v-model="activeFormats" multiple density="compact" divided>
        <v-tooltip text="Align Left" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().setTextAlign('left').run()"
              :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }"
              size="small"
              icon
            >
              <v-icon>mdi-format-align-left</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Align Center" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().setTextAlign('center').run()"
              :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }"
              size="small"
              icon
            >
              <v-icon>mdi-format-align-center</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Align Right" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().setTextAlign('right').run()"
              :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }"
              size="small"
              icon
            >
              <v-icon>mdi-format-align-right</v-icon>
            </v-btn>
          </template>
        </v-tooltip>

        <v-tooltip text="Justify" location="bottom">
          <template v-slot:activator="{ props }">
            <v-btn
              v-bind="props"
              @click="editor?.chain().focus().setTextAlign('justify').run()"
              :class="{ 'is-active': editor?.isActive({ textAlign: 'justify' }) }"
              size="small"
              icon
            >
              <v-icon>mdi-format-align-justify</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </v-btn-toggle>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Insert -->
      <v-tooltip text="Insert Link" location="bottom">
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            @click="showLinkDialog = true"
            :class="{ 'is-active': editor?.isActive('link') }"
            size="small"
            icon
          >
            <v-icon>mdi-link</v-icon>
          </v-btn>
        </template>
      </v-tooltip>

      <v-tooltip text="Insert Image" location="bottom">
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            @click="showImageDialog = true"
            size="small"
            icon
          >
            <v-icon>mdi-image</v-icon>
          </v-btn>
        </template>
      </v-tooltip>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Text Color -->
      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" size="small" icon>
            <v-icon>mdi-format-color-text</v-icon>
          </v-btn>
        </template>
        <v-card>
          <v-card-text>
            <v-color-picker
              v-model="textColor"
              @update:model-value="setTextColor"
              mode="hex"
              hide-inputs
            ></v-color-picker>
          </v-card-text>
        </v-card>
      </v-menu>

      <v-divider vertical class="mx-2"></v-divider>

      <!-- Clear Formatting -->
      <v-tooltip text="Clear Formatting" location="bottom">
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            @click="editor?.chain().focus().clearNodes().unsetAllMarks().run()"
            size="small"
            icon
          >
            <v-icon>mdi-format-clear</v-icon>
          </v-btn>
        </template>
      </v-tooltip>

      <v-spacer></v-spacer>

      <!-- Undo/Redo -->
      <v-tooltip text="Undo (Ctrl+Z)" location="bottom">
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            @click="editor?.chain().focus().undo().run()"
            :disabled="!editor?.can().undo()"
            size="small"
            icon
          >
            <v-icon>mdi-undo</v-icon>
          </v-btn>
        </template>
      </v-tooltip>

      <v-tooltip text="Redo (Ctrl+Y)" location="bottom">
        <template v-slot:activator="{ props }">
          <v-btn
            v-bind="props"
            @click="editor?.chain().focus().redo().run()"
            :disabled="!editor?.can().redo()"
            size="small"
            icon
          >
            <v-icon>mdi-redo</v-icon>
          </v-btn>
        </template>
      </v-tooltip>
    </v-toolbar>

    <!-- Inline Link Form -->
    <v-expand-transition>
      <v-card v-if="showLinkDialog" variant="outlined" class="mb-2">
        <v-card-text class="pa-3">
          <div class="d-flex align-center gap-2">
            <v-text-field
              v-model="linkUrl"
              label="URL"
              placeholder="https://example.com"
              variant="outlined"
              density="compact"
              autofocus
              hide-details
              @keyup.enter="insertLink"
            ></v-text-field>
            <v-btn
              color="primary"
              variant="flat"
              @click="insertLink"
              size="small"
            >
              Insert
            </v-btn>
            <v-btn
              variant="text"
              @click="showLinkDialog = false"
              size="small"
              icon="mdi-close"
            ></v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-expand-transition>

    <!-- Inline Image Form -->
    <v-expand-transition>
      <v-card v-if="showImageDialog" variant="outlined" class="mb-2">
        <v-card-text class="pa-3">
          <div class="d-flex align-center gap-2">
            <v-text-field
              v-model="imageUrl"
              label="Image URL"
              placeholder="https://example.com/image.jpg"
              variant="outlined"
              density="compact"
              autofocus
              hide-details
              @keyup.enter="insertImage"
            ></v-text-field>
            <v-btn
              color="primary"
              variant="flat"
              @click="insertImage"
              size="small"
            >
              Insert
            </v-btn>
            <v-btn
              variant="text"
              @click="showImageDialog = false"
              size="small"
              icon="mdi-close"
            ></v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-expand-transition>

    <!-- Editor Content -->
    <div class="editor-content-wrapper">
      <editor-content :editor="editor" class="editor-content" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import TextStyle from '@tiptap/extension-text-style'
import Color from '@tiptap/extension-color'

// Props
interface Props {
  modelValue: string
  placeholder?: string
  editable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Schreiben Sie Ihre Nachricht...',
  editable: true
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// State
const activeFormats = ref<string[]>([])
const textColor = ref('#000000')
const showLinkDialog = ref(false)
const showImageDialog = ref(false)
const linkUrl = ref('')
const imageUrl = ref('')

// Initialize TipTap editor
const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    TextStyle,
    Color,
    TextAlign.configure({
      types: ['heading', 'paragraph']
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        target: '_blank',
        rel: 'noopener noreferrer'
      }
    }),
    Image.configure({
      HTMLAttributes: {
        class: 'editor-image'
      }
    })
  ],
  content: props.modelValue,
  editable: props.editable,
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl focus:outline-none'
    }
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  const isSame = editor.value?.getHTML() === newValue
  if (!isSame && editor.value) {
    editor.value.commands.setContent(newValue, false)
  }
})

// Watch for editable changes
watch(() => props.editable, (newValue) => {
  if (editor.value) {
    editor.value.setEditable(newValue)
  }
})

// Insert link
function insertLink() {
  if (!linkUrl.value) {
    showLinkDialog.value = false
    return
  }

  editor.value
    ?.chain()
    .focus()
    .extendMarkRange('link')
    .setLink({ href: linkUrl.value })
    .run()

  linkUrl.value = ''
  showLinkDialog.value = false
}

// Insert image
function insertImage() {
  if (!imageUrl.value) {
    showImageDialog.value = false
    return
  }

  editor.value
    ?.chain()
    .focus()
    .setImage({ src: imageUrl.value })
    .run()

  imageUrl.value = ''
  showImageDialog.value = false
}

// Set text color
function setTextColor(color: string) {
  editor.value?.chain().focus().setColor(color).run()
}

// Cleanup
onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<style scoped>
.mail-editor {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
  overflow: hidden;
}

.editor-toolbar {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface)) !important;
}

.editor-toolbar .v-btn.is-active {
  background-color: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
}

.editor-content-wrapper {
  min-height: 300px;
  max-height: 600px;
  overflow-y: auto;
  background: white;
}

.editor-content {
  padding: 16px;
}

:deep(.ProseMirror) {
  min-height: 268px;
  outline: none;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: #adb5bd;
  pointer-events: none;
  height: 0;
  float: left;
}

:deep(.ProseMirror h1) {
  font-size: 2em;
  font-weight: bold;
  margin: 0.67em 0;
}

:deep(.ProseMirror h2) {
  font-size: 1.5em;
  font-weight: bold;
  margin: 0.75em 0;
}

:deep(.ProseMirror h3) {
  font-size: 1.17em;
  font-weight: bold;
  margin: 0.83em 0;
}

:deep(.ProseMirror ul),
:deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 1em 0;
}

:deep(.ProseMirror ul li) {
  list-style-type: disc;
}

:deep(.ProseMirror ol li) {
  list-style-type: decimal;
}

:deep(.ProseMirror a) {
  color: rgb(var(--v-theme-primary));
  text-decoration: underline;
  cursor: pointer;
}

:deep(.ProseMirror a:hover) {
  text-decoration: none;
}

:deep(.ProseMirror img.editor-image) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em 0;
  border-radius: 4px;
}

:deep(.ProseMirror blockquote) {
  border-left: 3px solid rgb(var(--v-theme-primary));
  padding-left: 1em;
  margin-left: 0;
  font-style: italic;
  color: #666;
}
</style>
