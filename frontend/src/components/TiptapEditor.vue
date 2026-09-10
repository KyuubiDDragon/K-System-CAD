<template>
    <div class="tiptap-editor-wrapper" :class="{ 'is-readonly': !editable, 'is-fullscreen': isFullscreen, 'has-toc': showTableOfContents }">

      <!-- Table of Contents Sidebar -->
      <div v-if="showTableOfContents" class="toc-sidebar">
        <div class="toc-header">
          <v-icon size="small" class="mr-2">mdi-format-list-bulleted</v-icon>
          <span class="text-subtitle-2 font-weight-bold">Inhaltsverzeichnis</span>
        </div>
        <div class="toc-items">
          <div v-if="tocItems.length === 0" class="toc-empty">
            <v-icon size="small" class="mb-2" color="grey">mdi-text-box-outline</v-icon>
            <div class="text-caption text-grey">Keine Überschriften vorhanden</div>
          </div>
          <div
            v-for="item in tocItems"
            :key="item.id"
            :class="['toc-item', `toc-level-${item.level}`]"
            @click="scrollToHeading(item.id)"
          >
            <span class="toc-text">{{ item.text }}</span>
          </div>
        </div>
      </div>

      <!-- Editor Area -->
      <div class="editor-area">
        <div v-if="editor && editable" class="toolbar-area">
  
  
           <div v-if="showSource" class="source-code-area pa-2">
              <!-- Button bar for source view -->
              <div class="source-code-header mb-2">
                <v-btn
                  @click="toggleSourceView"
                  variant="elevated"
                  color="primary"
                  prepend-icon="mdi-eye"
                  size="small"
                >
                  {{ $t('tiptap.visual') }}
                </v-btn>
                <v-btn
                  @click="toggleSourceEditMode"
                  variant="tonal"
                  :color="sourceEditMode ? 'success' : 'secondary'"
                  :prepend-icon="sourceEditMode ? 'mdi-check' : 'mdi-pencil'"
                  size="small"
                  class="ml-2"
                >
                  {{ sourceEditMode ? $t('tiptap.applyChanges') : $t('tiptap.edit') }}
                </v-btn>
                <v-spacer></v-spacer>
              </div>

              <!-- View Mode: Formatted and highlighted HTML -->
              <div v-if="!sourceEditMode" class="source-code-view">
                <pre class="source-code-pre"><code class="hljs language-html" v-html="formattedSourceHtml"></code></pre>
              </div>

              <!-- Edit Mode: Textarea for editing -->
              <v-textarea
                  v-else
                  v-model="sourceContent"
                  :label="$t('tiptap.htmlSource')"
                  variant="outlined"
                  rows="15"
                  density="compact"
                  class="source-textarea"
                  hide-details
                  @input="updateSourceContent"
              ></v-textarea>
          </div>
  
          <div v-if="!showSource" class="tiptap-toolbar">
              <v-tooltip location="top" :text="$t('tiptap.undo')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().chain().focus().undo().run()" v-bind="props"><v-icon>mdi-undo</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.redo')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().chain().focus().redo().run()" v-bind="props"><v-icon>mdi-redo</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
               <v-menu location="bottom" :attach="true">
                   <template v-slot:activator="{ props }">
                       <v-tooltip location="top" :text="$t('tiptap.textStyle')">
                           <template v-slot:activator="{ props: tooltipProps }">
                               <v-btn size="small" variant="tonal" v-bind="{...props, ...tooltipProps}" class="px-2 text-none">
                                  <span v-if="editor.isActive('heading', { level: 1 })">{{ $t('tiptap.heading1') }}</span>
                                  <span v-else-if="editor.isActive('heading', { level: 2 })">{{ $t('tiptap.heading2') }}</span>
                                  <span v-else-if="editor.isActive('heading', { level: 3 })">{{ $t('tiptap.heading3') }}</span>
                                  <span v-else-if="editor.isActive('heading', { level: 4 })">{{ $t('tiptap.heading4') }}</span>
                                  <span v-else>{{ $t('tiptap.text') }}</span>
                                  <v-icon end>mdi-menu-down</v-icon>
                               </v-btn>
                          </template>
                      </v-tooltip>
                  </template>
                  <v-list density="compact">
                      <v-list-item @click="editor.chain().focus().setParagraph().run()" :active="editor.isActive('paragraph')"> <v-list-item-title>{{ $t('tiptap.text') }}</v-list-item-title></v-list-item>
                      <v-list-item @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :active="editor.isActive('heading', { level: 1 })"><v-list-item-title>{{ $t('tiptap.heading1') }}</v-list-item-title></v-list-item>
                      <v-list-item @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :active="editor.isActive('heading', { level: 2 })"><v-list-item-title>{{ $t('tiptap.heading2') }}</v-list-item-title></v-list-item>
                      <v-list-item @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :active="editor.isActive('heading', { level: 3 })"><v-list-item-title>{{ $t('tiptap.heading3') }}</v-list-item-title></v-list-item>
                      <v-list-item @click="editor.chain().focus().toggleHeading({ level: 4 }).run()" :active="editor.isActive('heading', { level: 4 })"><v-list-item-title>{{ $t('tiptap.heading4') }}</v-list-item-title></v-list-item>
                  </v-list>
              </v-menu>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleBold().run()" :disabled="!editor.can().chain().focus().toggleBold().run()" :class="{ 'is-active': editor.isActive('bold') }"><v-icon>mdi-format-bold</v-icon></v-btn>
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleItalic().run()" :disabled="!editor.can().chain().focus().toggleItalic().run()" :class="{ 'is-active': editor.isActive('italic') }"><v-icon>mdi-format-italic</v-icon></v-btn>
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleUnderline().run()" :disabled="!editor.can().chain().focus().toggleUnderline().run()" :class="{ 'is-active': editor.isActive('underline') }"><v-icon>mdi-format-underline</v-icon></v-btn>
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleStrike().run()" :disabled="!editor.can().chain().focus().toggleStrike().run()" :class="{ 'is-active': editor.isActive('strike') }"><v-icon>mdi-format-strikethrough</v-icon></v-btn>
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleSubscript().run()" :disabled="!editor.can().chain().focus().toggleSubscript().run()" :class="{ 'is-active': editor.isActive('subscript') }"><v-icon>mdi-format-subscript</v-icon></v-btn>
              <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleSuperscript().run()" :disabled="!editor.can().chain().focus().toggleSuperscript().run()" :class="{ 'is-active': editor.isActive('superscript') }"><v-icon>mdi-format-superscript</v-icon></v-btn>
  
              <v-menu v-model="colorMenu" :close-on-content-click="false" location="bottom">
                  <template v-slot:activator="{ props: menuProps }">
                      <v-tooltip location="top" :text="$t('tiptap.textColor')">
                          <template v-slot:activator="{ props: tooltipProps }">
                              <v-btn icon size="small" variant="tonal" v-bind="{...menuProps, ...tooltipProps}" :color="editor.getAttributes('textStyle').color || undefined"><v-icon>mdi-format-color-text</v-icon></v-btn>
                          </template>
                      </v-tooltip>
                  </template>
                  <v-color-picker @update:modelValue="setColor" hide-inputs show-swatches :swatches="colorSwatches" elevation="10"></v-color-picker>
                  <v-btn block @click="setColor('')" class="mt-1">{{ $t('tiptap.removeColor') }}</v-btn>
                  </v-menu>
                  <v-menu v-model="highlightMenu" :close-on-content-click="false" location="bottom">
                  <template v-slot:activator="{ props: menuProps }">
                      <v-tooltip location="top" :text="$t('tiptap.backgroundColor')">
                          <template v-slot:activator="{ props: tooltipProps }">
                              <v-btn icon size="small" variant="tonal" v-bind="{...menuProps, ...tooltipProps}" :style="{ backgroundColor: editor.getAttributes('highlight').color || 'transparent' }"><v-icon>mdi-format-highlight</v-icon></v-btn>
                          </template>
                      </v-tooltip>
                  </template>
                  <v-color-picker @update:modelValue="setHighlight" hide-inputs show-swatches :swatches="highlightSwatches" elevation="10"></v-color-picker>
                      <v-btn block @click="setHighlight('')" class="mt-1">{{ $t('tiptap.removeHighlight') }}</v-btn>
                  </v-menu>
  
              <v-tooltip location="top" :text="$t('tiptap.removeFormatting')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="clearFormat" v-bind="props"><v-icon>mdi-format-clear</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
              <v-tooltip location="top" :text="$t('tiptap.alignLeft')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="setTextAlign('left')" :disabled="!editor.can().setTextAlign('left')" :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" v-bind="props"><v-icon>mdi-format-align-left</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.alignCenter')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="setTextAlign('center')" :disabled="!editor.can().setTextAlign('center')" :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" v-bind="props"><v-icon>mdi-format-align-center</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.alignRight')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="setTextAlign('right')" :disabled="!editor.can().setTextAlign('right')" :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" v-bind="props"><v-icon>mdi-format-align-right</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.alignJustify')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="setTextAlign('justify')" :disabled="!editor.can().setTextAlign('justify')" :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }" v-bind="props"><v-icon>mdi-format-align-justify</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
              <v-tooltip location="top" :text="$t('tiptap.bulletList')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'is-active': editor.isActive('bulletList') }" v-bind="props"><v-icon>mdi-format-list-bulleted</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.orderedList')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'is-active': editor.isActive('orderedList') }" v-bind="props"><v-icon>mdi-format-list-numbered</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
              <v-tooltip location="top" :text="$t('tiptap.blockquote')">
                   <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ 'is-active': editor.isActive('blockquote') }" v-bind="props"><v-icon>mdi-format-quote-close</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" text="Collapsible Section">
                   <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().setDetails().run()" :class="{ 'is-active': editor.isActive('details') }" v-bind="props"><v-icon>mdi-chevron-down-box</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.horizontalLine')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().setHorizontalRule().run()" v-bind="props"><v-icon>mdi-minus</v-icon></v-btn>
                  </template>
              </v-tooltip>
              
              <!-- Aufgabenliste -->
              <v-tooltip location="top" :text="$t('tiptap.taskList')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="toggleTaskList" :class="{ 'is-active': editor.isActive('taskList') }" v-bind="props">
                          <v-icon>mdi-checkbox-marked-outline</v-icon>
                      </v-btn>
                  </template>
              </v-tooltip>
              
              <!-- Code Block Menu -->
              <v-menu v-model="codeBlockMenu" :close-on-content-click="false" location="bottom">
                  <template v-slot:activator="{ props: menuProps }">
                      <v-tooltip location="top" :text="$t('tiptap.codeBlock')">
                          <template v-slot:activator="{ props: tooltipProps }">
                              <v-btn icon size="small" variant="tonal" v-bind="{...menuProps, ...tooltipProps}" :class="{ 'is-active': editor.isActive('codeBlock') }">
                                  <v-icon>mdi-code-tags</v-icon>
                              </v-btn>
                          </template>
                      </v-tooltip>
                  </template>
                  <v-list density="compact">
                      <v-list-item v-for="lang in codeLanguages" :key="lang.value" @click="updateCodeLanguage(lang.value)" :active="editor.isActive('codeBlock', { language: lang.value })">
                          <v-list-item-title>{{ $t(lang.label) }}</v-list-item-title>
                      </v-list-item>
                  </v-list>
              </v-menu>
              
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
              <v-tooltip location="top" :text="$t('tiptap.insertLink')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="openLinkDialog" :class="{ 'is-active': editor.isActive('link') }" v-bind="props"><v-icon>mdi-link-variant</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.removeLink')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="editor.chain().focus().unsetLink().run()" :disabled="!editor.isActive('link')" v-bind="props"><v-icon>mdi-link-variant-off</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.insertImage')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="openImageDialog" v-bind="props"><v-icon>mdi-image-plus</v-icon></v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.uploadImage')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" v-bind="props">
                          <input 
                              type="file" 
                              accept="image/*" 
                              class="file-input" 
                              @change="handleImageUpload"
                          />
                          <v-icon>mdi-file-upload</v-icon>
                      </v-btn>
                  </template>
              </v-tooltip>
              <v-tooltip location="top" :text="$t('tiptap.insertYoutube')">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="openVideoDialog" v-bind="props"><v-icon>mdi-youtube</v-icon></v-btn>
                  </template>
              </v-tooltip>
  
                  <v-menu v-model="tableMenu" :close-on-content-click="false" location="bottom">
                      <template v-slot:activator="{ props: menuProps }">
                          <v-tooltip location="top" :text="$t('tiptap.table')">
                              <template v-slot:activator="{ props: tooltipProps }">
                                  <v-btn icon size="small" variant="tonal" v-bind="{...menuProps, ...tooltipProps}" :disabled="!editor.can().insertTable() && !editor.isActive('table')"><v-icon>mdi-table</v-icon></v-btn>
                              </template>
                          </v-tooltip>
                      </template>
                      <v-list density="compact">
                          <v-list-item @click="insertTable" :disabled="!editor.can().insertTable()"><v-list-item-title>{{ $t('tiptap.insertTable') }}</v-list-item-title></v-list-item>
                          <template v-if="editor.isActive('table')">
                              <v-divider></v-divider>
                              <v-list-item @click="editor.chain().focus().addColumnBefore().run()" :disabled="!editor.can().addColumnBefore()"><v-list-item-title>{{ $t('tiptap.addColumnBefore') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="addColumnAfter" :disabled="!editor.can().addColumnAfter()"><v-list-item-title>{{ $t('tiptap.addColumnAfter') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="editor.chain().focus().deleteColumn().run()" :disabled="!editor.can().deleteColumn()"><v-list-item-title>{{ $t('tiptap.deleteColumn') }}</v-list-item-title></v-list-item>
                                  <v-divider></v-divider>
                              <v-list-item @click="editor.chain().focus().addRowBefore().run()" :disabled="!editor.can().addRowBefore()"><v-list-item-title>{{ $t('tiptap.addRowBefore') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="addRowAfter" :disabled="!editor.can().addRowAfter()"><v-list-item-title>{{ $t('tiptap.addRowAfter') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="editor.chain().focus().deleteRow().run()" :disabled="!editor.can().deleteRow()"><v-list-item-title>{{ $t('tiptap.deleteRow') }}</v-list-item-title></v-list-item>
                              <v-divider></v-divider>
                              <v-list-item @click="editor.chain().focus().mergeOrSplit().run()" :disabled="!editor.can().mergeOrSplit()"><v-list-item-title>{{ $t('tiptap.mergeCells') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="editor.chain().focus().toggleHeaderRow().run()" :disabled="!editor.can().toggleHeaderRow()"><v-list-item-title>{{ $t('tiptap.toggleHeaderRow') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="editor.chain().focus().toggleHeaderColumn().run()" :disabled="!editor.can().toggleHeaderColumn()"><v-list-item-title>{{ $t('tiptap.toggleHeaderColumn') }}</v-list-item-title></v-list-item>
                              <v-list-item @click="editor.chain().focus().toggleHeaderCell().run()" :disabled="!editor.can().toggleHeaderCell()"><v-list-item-title>{{ $t('tiptap.toggleHeaderCell') }}</v-list-item-title></v-list-item>
                              <v-divider></v-divider>
                              <v-list-item @click="deleteTable" :disabled="!editor.can().deleteTable()"><v-list-item-title>{{ $t('tiptap.deleteTable') }}</v-list-item-title></v-list-item>
                          </template>
                      </v-list>
                  </v-menu>
              <v-divider vertical class="mx-1 my-1"></v-divider>
  
               <v-menu v-model="emojiMenu" :close-on-content-click="false" location="bottom">
                   <template v-slot:activator="{ props: menuProps }">
                       <v-tooltip location="top" :text="$t('tiptap.insertEmoji')">
                           <template v-slot:activator="{ props: tooltipProps }">
                               <v-btn icon size="small" variant="tonal" v-bind="{...menuProps, ...tooltipProps}"><v-icon>mdi-emoticon-outline</v-icon></v-btn>
                           </template>
                       </v-tooltip>
                   </template>
                   <v-card max-width="300">
                      <v-card-text class="pa-1">
                           <v-row dense>
                               <v-col v-for="emoji in commonEmojis" :key="emoji" cols="auto">
                                   <v-btn variant="text" size="small" @click="insertEmoji(emoji)" class="emoji-button">
                                       {{ emoji }}
                                   </v-btn>
                               </v-col>
                           </v-row>
                      </v-card-text>
                   </v-card>
               </v-menu>

              <!-- Source Code and Fullscreen buttons moved here for compact toolbar -->
              <v-divider vertical class="mx-1 my-1"></v-divider>

              <v-tooltip v-if="showSourceButton" :text="showSource ? $t('tiptap.visual') : $t('tiptap.code')" location="top">
                  <template v-slot:activator="{ props }">
                      <v-btn icon size="small" variant="tonal" @click="toggleSourceView" v-bind="props">
                          <v-icon>{{ showSource ? 'mdi-eye' : 'mdi-code-tags' }}</v-icon>
                      </v-btn>
                  </template>
              </v-tooltip>

              <v-tooltip :text="isFullscreen ? $t('tiptap.exitFullscreen') : $t('tiptap.fullscreen')" location="top">
                   <template v-slot:activator="{ props }">
                       <v-btn
                           icon
                           size="small"
                           variant="tonal"
                           @click="toggleFullscreen"
                           v-bind="props"
                       >
                           <v-icon>{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
                       </v-btn>
                   </template>
               </v-tooltip>
          </div>
      </div>
  
      <div v-show="!showSource" class="tiptap-editor-container" :class="{ 'editable': editable }" @click="editable && editor?.commands.focus()">
        <editor-content :editor="editor" />

      </div>
  
      <div v-if="editor && editable && showCharacterCount && !showSource" class="editor-character-count text-caption text-disabled pa-1 text-right">
        {{ editor.storage.characterCount.characters() }} {{ $t('tiptap.characters') }}
        <span v-if="characterLimit"> / {{ characterLimit }}</span>
      </div>
  
      <v-dialog v-model="linkDialog" max-width="500px" persistent>
          <v-card>
              <v-card-title>{{ $t('tiptap.linkDialogTitle') }}</v-card-title>
              <v-card-text>
                  <v-text-field v-model="linkUrl" :label="$t('tiptap.url')" variant="outlined" density="compact" autofocus @keyup.enter="applyLink"></v-text-field>
              </v-card-text>
              <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn text @click="cancelLinkDialog">{{ $t('cancel') }}</v-btn>
                  <v-btn color="primary" @click="applyLink">{{ $t('tiptap.apply') }}</v-btn>
              </v-card-actions>
          </v-card>
      </v-dialog>
  
      <v-dialog v-model="imageDialog" max-width="600px" persistent>
          <v-card>
              <v-card-title>{{ $t('tiptap.imageDialogTitle') }}</v-card-title>
              <v-card-text>
                  <v-text-field v-model="imageUrl" :label="$t('tiptap.imageUrl')" variant="outlined" density="compact" autofocus></v-text-field>
                  <v-text-field v-model="imageAlt" :label="$t('tiptap.imageAlt')" variant="outlined" density="compact"></v-text-field>
                  
                  <v-radio-group v-model="imageAlignment" inline :label="$t('tiptap.alignment')" density="compact">
                      <v-radio value="left" :label="$t('tiptap.alignLeftShort')"></v-radio>
                      <v-radio value="center" :label="$t('tiptap.alignCenterShort')"></v-radio>
                      <v-radio value="right" :label="$t('tiptap.alignRightShort')"></v-radio>
                  </v-radio-group>
              </v-card-text>
              <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn text @click="cancelImageDialog">{{ $t('cancel') }}</v-btn>
                  <v-btn color="primary" @click="applyImage" :disabled="!imageUrl">{{ $t('tiptap.insert') }}</v-btn>
              </v-card-actions>
          </v-card>
      </v-dialog>
  
      <v-dialog v-model="videoDialog" max-width="600px" persistent>
          <v-card>
              <v-card-title>{{ $t('tiptap.videoDialogTitle') }}</v-card-title>
              <v-card-text>
                  <v-text-field v-model="videoUrl" :label="$t('tiptap.videoUrl')" variant="outlined" density="compact" autofocus></v-text-field>
              </v-card-text>
              <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn text @click="cancelVideoDialog">{{ $t('cancel') }}</v-btn>
                  <v-btn color="primary" @click="applyVideo" :disabled="!videoUrl">{{ $t('tiptap.insert') }}</v-btn>
              </v-card-actions>
          </v-card>
      </v-dialog>
  
      <div v-if="mentionsActive" class="mentions-dropdown">
        <div 
          v-for="mention in filteredMentions" 
          :key="mention.id" 
          class="mention-item"
          @click="insertMention(mention)"
        >
          <strong>{{ mention.name }}</strong> 
          <span class="mention-role">{{ mention.role }}</span>
        </div>
        <div v-if="filteredMentions.length === 0" class="mention-item no-results">
          {{ $t('tiptap.noResults') }}
        </div>
      </div>
      </div><!-- /editor-area -->

    </div><!-- /tiptap-editor-wrapper -->
  </template>
  
  <script setup lang="ts">
  import { ref, watch, onBeforeUnmount, computed, nextTick } from 'vue';
  import { useEditor, EditorContent, VueNodeViewRenderer } from '@tiptap/vue-3';

  // Tiptap Core & Extensions
  import Document from '@tiptap/extension-document';
  import Paragraph from '@tiptap/extension-paragraph';
  import Text from '@tiptap/extension-text';
  import { UndoRedo } from '@tiptap/extensions';
  import { Markdown } from '@tiptap/markdown';
  import FileHandler from '@tiptap/extension-file-handler';
  import HardBreak from '@tiptap/extension-hard-break';
  import Dropcursor from '@tiptap/extension-dropcursor';
  import Gapcursor from '@tiptap/extension-gapcursor';
  import Bold from '@tiptap/extension-bold';
  import Italic from '@tiptap/extension-italic';
  import Strike from '@tiptap/extension-strike';
  import Heading from '@tiptap/extension-heading';
  import { BulletList, OrderedList, ListItem } from '@tiptap/extension-list';
  import Blockquote from '@tiptap/extension-blockquote';
  import HorizontalRule from '@tiptap/extension-horizontal-rule';
  import Link from '@tiptap/extension-link';
  import Image from '@tiptap/extension-image';
  import Underline from '@tiptap/extension-underline';
  import Subscript from '@tiptap/extension-subscript';
  import Superscript from '@tiptap/extension-superscript';
  import TextAlign from '@tiptap/extension-text-align';
  import Highlight from '@tiptap/extension-highlight';
  import { Color } from '@tiptap/extension-color';
  import { TextStyle } from '@tiptap/extension-text-style';
  import Youtube from '@tiptap/extension-youtube';
  import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table';
  import CharacterCount from '@tiptap/extension-character-count';
  import Placeholder from '@tiptap/extension-placeholder';
  import CodeBlock from '@tiptap/extension-code-block';
  import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
  import { TaskList, TaskItem } from '@tiptap/extension-list';
  import Typography from '@tiptap/extension-typography';
  import Mention from '@tiptap/extension-mention';
  import { Details, DetailsSummary, DetailsContent } from '@tiptap/extension-details';
  import { common, createLowlight } from 'lowlight';
  const lowlight = createLowlight(common);
  import javascript from 'highlight.js/lib/languages/javascript';
  import css from 'highlight.js/lib/languages/css';
  import html from 'highlight.js/lib/languages/xml';
  import php from 'highlight.js/lib/languages/php';
  import sql from 'highlight.js/lib/languages/sql';
  import typescript from 'highlight.js/lib/languages/typescript';
  import bash from 'highlight.js/lib/languages/bash';
  
  // Syntax Highlighting
  lowlight.register('javascript', javascript);
  lowlight.register('css', css);
  lowlight.register('typescript', typescript);
  lowlight.register('sql', sql);
  lowlight.register('php', php);
  lowlight.register('bash', bash);
  
  // --- Props ---
  interface Props {
    modelValue?: string;
    placeholder?: string;
    characterLimit?: number | null;
    showCharacterCount?: boolean;
    editable?: boolean; // Prop für Read-Only Modus
    showSourceButton?: boolean; // Prop um Source-Button anzuzeigen
    showTableOfContents?: boolean; // Prop um Table of Contents Sidebar anzuzeigen
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '',
    characterLimit: null,
    showCharacterCount: true,
    editable: true,
    showSourceButton: true,
    showTableOfContents: false,
  });
  
  // --- Emits ---
  const emit = defineEmits(['update:modelValue']);
  
  // --- Internal State ---
  const linkDialog = ref(false);
  const linkUrl = ref('');
  const imageDialog = ref(false);
  const imageUrl = ref('');
  const imageAlt = ref('');
  const imageAlignment = ref('left'); // New image alignment state
  const videoDialog = ref(false);
  const videoUrl = ref('');
  const tableMenu = ref(false);
  const colorMenu = ref(false);
  const highlightMenu = ref(false);
  const showSource = ref(false); // State für Source Code View
  const sourceContent = ref(''); // Temporärer State für Textarea
  const sourceEditMode = ref(false); // State für Edit-Modus im Source View
  const formattedSourceHtml = ref(''); // Formatierter und gehighlighteter HTML-Code
  const isFullscreen = ref(false); // State für Fullscreen
  const emojiMenu = ref(false); // State für Emoji Menü

  // --- Table of Contents ---
  interface TocItem {
    id: string;
    text: string;
    level: number;
  }
  const tocItems = ref<TocItem[]>([]);
  const codeLanguages = ref([
    { label: 'tiptap.langPlainText', value: 'plaintext' },
    { label: 'tiptap.langHtml', value: 'html' },
    { label: 'tiptap.langCss', value: 'css' },
    { label: 'tiptap.langJavascript', value: 'javascript' },
    { label: 'tiptap.langPhp', value: 'php' },
    { label: 'tiptap.langSql', value: 'sql' }
  ]);
  const codeBlockMenu = ref(false);
  const currentCodeLanguage = ref('plaintext');
  const imageAlignmentMenu = ref(false);
  const currentTaskListType = ref('bullet');
  
  // Farbfelder für die Picker
  const colorSwatches = ref([
      ['#000000', '#444444', '#666666', '#999999'],
      ['#FFFFFF', '#DDDDDD', '#AAAAAA', '#777777'],
      ['#FF0000', '#FF9900', '#FFFF00', '#00FF00'],
      ['#00FFFF', '#0000FF', '#9900FF', '#FF00FF']
  ]);
  const highlightSwatches = ref([
      ['#FFFF00', '#ADFF2F', '#ADD8E6', '#FFB6C1'],
      ['#FFD700', '#90EE90', '#87CEFA', '#FFA07A'],
      ['#FFA500', '#32CD32', '#00BFFF', '#F08080'],
      ['#FF8C00', '#98FB98', '#AFEEEE', '#E9967A']
  ]);
  
  // Emoji Liste
  const commonEmojis = ref([
    '😀', '😂', '😍', '🤔', '😎', '😭', '🥳', '👍', '👎', '❤️', '✅', '⚠️', '💡', '✨', '🎉', '🚀'
  ]);
  
  // Mention support
  const mentionSuggestions = ref([
    { id: 1, name: 'Max Mustermann', role: 'Admin' },
    { id: 2, name: 'Anna Schmidt', role: 'Benutzer' },
    { id: 3, name: 'Klaus Meyer', role: 'Support' },
    { id: 4, name: 'Laura Weber', role: 'Kunde' },
    { id: 5, name: 'Thomas Becker', role: 'Gast' },
  ]);
  
  const mentionSearch = ref('');
  const mentionsActive = ref(false);
  const filteredMentions = computed(() => {
    return mentionSuggestions.value.filter(mention => 
      mention.name.toLowerCase().includes(mentionSearch.value.toLowerCase()) || 
      mention.role.toLowerCase().includes(mentionSearch.value.toLowerCase())
    );
  });
  
  // --- Editor Instance ---
  const editor = useEditor({
    editable: props.editable,
    content: props.modelValue,
    extensions: [
      // Minimal Setup
      Document, Paragraph, Text, UndoRedo, HardBreak, Dropcursor, Gapcursor,
      // Basic Marks & Blocks
      Bold, Italic, Strike, Underline, Subscript, Superscript,
      Heading.configure({
        levels: [1, 2, 3, 4],
        HTMLAttributes: {
          class: 'tiptap-heading'
        }
      }).extend({
        addAttributes() {
          return {
            ...this.parent?.(),
            id: {
              default: null,
              parseHTML: element => element.getAttribute('id'),
              renderHTML: attributes => {
                // Always render an ID - either use existing or generate from text content
                const id = attributes.id;
                if (id) {
                  return { id: id, 'data-toc-id': id };
                }
                return {};
              }
            }
          };
        },
        // Add a custom render function to ensure IDs are present in the DOM
        addNodeView() {
          return ({ node, HTMLAttributes }) => {
            const level = node.attrs.level;
            const dom = document.createElement(`h${level}`);

            // Generate ID from text content if not present
            let id = node.attrs.id;
            if (!id) {
              const text = node.textContent;
              id = text
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            }

            // Set all attributes
            Object.entries(HTMLAttributes).forEach(([key, value]) => {
              if (value !== null && value !== undefined) {
                dom.setAttribute(key, String(value));
              }
            });

            // Always set the ID and data-toc-id
            if (id) {
              dom.setAttribute('id', id);
              dom.setAttribute('data-toc-id', id);
            }

            const contentDOM = document.createElement('span');
            dom.appendChild(contentDOM);

            return {
              dom,
              contentDOM
            };
          };
        }
      }),
      BulletList, OrderedList, ListItem,
      Blockquote, HorizontalRule,
      // Links & Media
      Link.configure({
          openOnClick: false, autolink: true, linkOnPaste: true,
          HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer nofollow', class: 'editor-link' },
      }),
      Image.configure({
        inline: false,
        allowBase64: true,
        HTMLAttributes: {
          class: 'editor-image',
        },
      }),
      Youtube.configure({ controls: true, nocookie: true, HTMLAttributes: { class: 'editor-video' } }),
      // Formatting & Styles
      TextAlign.configure({ 
        types: ['heading', 'paragraph', 'image'],
        alignments: ['left', 'center', 'right', 'justify'], 
        defaultAlignment: 'left' 
      }),
      Highlight.configure({ multicolor: true }),
      TextStyle, Color,
      // Code Blocks mit Syntax-Highlighting
      CodeBlockLowlight.configure({
        lowlight,
        languageClassPrefix: 'language-',
        defaultLanguage: 'plaintext',
        HTMLAttributes: {
          class: 'code-block',
        }
      }),
      // Task Lists (Checklisten)
      TaskList.configure({
        HTMLAttributes: {
          class: 'task-list'
        }
      }),
      TaskItem.configure({
        nested: true,
        HTMLAttributes: {
          class: 'task-item'
        }
      }),
      // Mention support
      Mention.configure({
        HTMLAttributes: {
          class: 'mention',
        },
        suggestion: {
          items: ({ query }) => {
            mentionSearch.value = query || '';
            return filteredMentions.value.slice(0, 5);
          },
          render: () => {
            return {
              onStart: () => {
                mentionsActive.value = true;
              },
              onExit: () => {
                mentionsActive.value = false;
              },
              onKeyDown: ({ event }) => {
                // Handle keydown events if needed
                return false;
              },
              onUpdate: ({ query }) => {
                mentionSearch.value = query || '';
              },
            };
          },
        },
      }),
      // Typographie
      Typography,
      // Tabellen mit erweiterten Features
      Table.configure({ 
        resizable: true,
        cellMinWidth: 50,
        HTMLAttributes: { 
          class: 'editor-table' 
        }
      }), 
      TableRow, 
      TableHeader, 
      TableCell.configure({
        HTMLAttributes: {
          class: 'editor-table-cell'
        }
      }),
      // Collapsible Details
      Details,
      DetailsSummary,
      DetailsContent,
      // Fußnoten
      // Footnote,
      // Utilities
      CharacterCount.configure({ limit: props.characterLimit }),
      Placeholder.configure({ placeholder: props.placeholder }),
      // Markdown Support
      Markdown.configure({
        html: true,
        tightLists: true,
        bulletListMarker: '-',
        linkify: true,
      }),
      // File Handler for Drag & Drop
      FileHandler.configure({
        allowedMimeTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        onDrop: (currentEditor, files, pos) => {
          files.forEach(file => {
            const reader = new FileReader();
            reader.onload = () => {
              currentEditor.chain().insertContentAt(pos, {
                type: 'image',
                attrs: {
                  src: reader.result,
                },
              }).focus().run();
            };
            reader.readAsDataURL(file);
          });
        },
        onPaste: (currentEditor, files) => {
          files.forEach(file => {
            const reader = new FileReader();
            reader.onload = () => {
              currentEditor.chain().insertContent({
                type: 'image',
                attrs: {
                  src: reader.result,
                },
              }).focus().run();
            };
            reader.readAsDataURL(file);
          });
        },
      }),
    ],
    onUpdate: ({ editor }) => {
        if (!showSource.value) { // Nur updaten, wenn nicht im Source-Modus
            emit('update:modelValue', editor.getHTML());
        }
        // Update Table of Contents
        if (props.showTableOfContents) {
            updateTableOfContents(editor);
        }
    },
    onSelectionUpdate() {
        // Optional: Menüs schließen
    },
    editorProps: {
          attributes: {
              class: 'tiptap-editor-content ProseMirror',
          },
      },
  });
  
  // --- Watchers ---
  watch(() => props.modelValue, (newValue) => {
    if (editor.value && !showSource.value) { // Nur aktualisieren, wenn nicht im Source-Modus
      const isSame = editor.value.getHTML() === newValue;
      if (!isSame) {
        editor.value.commands.setContent(newValue || '', { emitUpdate: false });
      }
    } else if (showSource.value) {
         // Wenn Source-Modus aktiv ist, update die Textarea
         sourceContent.value = newValue || '';
    }
  });
  
  watch(() => props.editable, (isEditable) => {
      editor.value?.setEditable(isEditable);
  });
  
  // --- Lifecycle ---
  onBeforeUnmount(() => {
    editor.value?.destroy();
    // Sicherstellen, dass Body-Overflow zurückgesetzt wird, falls Komponente zerstört wird
    if (isFullscreen.value) {
        document.body.style.overflow = '';
    }
  });
  
  // --- Toolbar Methods ---
  const setTextAlign = (align: 'left' | 'center' | 'right' | 'justify') => editor.value?.chain().focus().setTextAlign(align).run();
  const setColor = (color: string) => {
      if (color) editor.value?.chain().focus().setColor(color).run();
      else editor.value?.chain().focus().unsetColor().run();
      colorMenu.value = false;
  };
  const setHighlight = (color: string) => {
      if (color) editor.value?.chain().focus().setHighlight({ color: color }).run();
      else editor.value?.chain().focus().unsetHighlight().run();
      highlightMenu.value = false;
  };
  const clearFormat = () => editor.value?.chain().focus().unsetAllMarks().clearNodes().run(); //.setParagraph().run();
  
  // Link Dialog
  const openLinkDialog = () => {
      linkUrl.value = editor.value?.getAttributes('link').href || '';
      linkDialog.value = true;
  };
  const applyLink = () => {
      if (!editor.value) return;
      if (linkUrl.value) {
          editor.value.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.value, target: '_blank' }).run();
      } else {
          editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
      }
      linkDialog.value = false;
  };
  const cancelLinkDialog = () => {
      linkDialog.value = false;
  }
  
  // Image Dialog
  const openImageDialog = () => {
      imageUrl.value = editor.value?.getAttributes('image').src || '';
      imageAlt.value = editor.value?.getAttributes('image').alt || '';
      imageDialog.value = true;
  };
  const applyImage = () => {
      if (!editor.value || !imageUrl.value) return;
      editor.value.chain().focus()
        .setImage({ src: imageUrl.value, alt: imageAlt.value || undefined })
        .run();
        
      // Apply alignment after inserting the image
      if (imageAlignment.value) {
          editor.value.chain().focus().setTextAlign(imageAlignment.value).run();
      }
      
      imageDialog.value = false;
  };
  const cancelImageDialog = () => {
       imageDialog.value = false;
  }
  
  // Video Dialog
  const openVideoDialog = () => {
      videoUrl.value = ''; // YouTube Extension braucht URL oder ID
      videoDialog.value = true;
  }
  const applyVideo = () => {
      if (!editor.value || !videoUrl.value) return;
      editor.value.commands.setYoutubeVideo({ src: videoUrl.value });
      videoDialog.value = false;
  }
  const cancelVideoDialog = () => {
      videoDialog.value = false;
  }
  
  // Table Methods
  const insertTable = () => {
      editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
      tableMenu.value = false;
  };
  const addColumnAfter = () => editor.value?.chain().focus().addColumnAfter().run();
  const addRowAfter = () => editor.value?.chain().focus().addRowAfter().run();
  const deleteTable = () => editor.value?.chain().focus().deleteTable().run();
  
  // Methode zum Erfassen von Änderungen aus dem Source-Editor
  const updateSourceContent = () => {
      if (showSource.value) {
          emit('update:modelValue', sourceContent.value);
      }
  };
  
  // HTML Formatting Function - formatiert HTML mit Einrückung
  const formatHTML = (html: string): string => {
    if (!html) return '';

    // Block-Level Tags, die auf neue Zeile kommen
    const blockTags = ['html', 'head', 'body', 'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'header', 'footer',
      'main', 'section', 'article', 'nav', 'aside', 'details', 'summary', 'blockquote',
      'pre', 'figure', 'figcaption', 'form', 'fieldset', 'legend'];

    let result = '';
    let indent = 0;
    const indentStr = '  '; // 2 Leerzeichen pro Ebene

    // Parse HTML character by character
    let i = 0;
    while (i < html.length) {
      if (html[i] === '<') {
        // Find end of tag
        let tagEnd = html.indexOf('>', i);
        if (tagEnd === -1) break;

        const tag = html.substring(i, tagEnd + 1);
        const tagNameMatch = tag.match(/^<\/?([a-zA-Z0-9]+)/);
        const tagName = tagNameMatch?.[1]?.toLowerCase() || '';
        const isClosingTag = tag.startsWith('</');
        const isSelfClosing = tag.endsWith('/>') || ['br', 'hr', 'img', 'input', 'meta', 'link'].includes(tagName);
        const isBlockTag = blockTags.includes(tagName);

        if (isClosingTag && isBlockTag) {
          indent = Math.max(0, indent - 1);
          result += '\n' + indentStr.repeat(indent) + tag;
        } else if (isBlockTag) {
          result += '\n' + indentStr.repeat(indent) + tag;
          if (!isSelfClosing) {
            indent++;
          }
        } else {
          result += tag;
        }
        i = tagEnd + 1;
      } else {
        // Text content
        let textEnd = html.indexOf('<', i);
        if (textEnd === -1) textEnd = html.length;
        const text = html.substring(i, textEnd).trim();
        if (text) {
          result += text;
        }
        i = textEnd;
      }
    }

    return result.trim();
  };

  // Syntax-Highlighting für HTML mit lowlight
  const highlightHTML = (code: string): string => {
    try {
      const highlighted = lowlight.highlight('html', code);
      return renderHighlightNodes(highlighted.children);
    } catch {
      return escapeHtml(code);
    }
  };

  // Rekursive Render-Funktion für lowlight nodes
  const renderHighlightNodes = (nodes: any[]): string => {
    return nodes.map((node: any) => {
      if (node.type === 'text') {
        return escapeHtml(node.value);
      }
      if (node.type === 'element') {
        const className = node.properties?.className?.join(' ') || '';
        const content = node.children ? renderHighlightNodes(node.children) : '';
        return `<span class="${className}">${content}</span>`;
      }
      return '';
    }).join('');
  };

  // HTML-Escape Funktion
  const escapeHtml = (text: string): string => {
    const map: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  };

  // Update formatted source HTML
  const updateFormattedSource = () => {
    const formatted = formatHTML(sourceContent.value);
    formattedSourceHtml.value = highlightHTML(formatted);
    sourceContent.value = formatted;
  };

  // Toggle zwischen View und Edit Modus im Source View
  const toggleSourceEditMode = () => {
    if (sourceEditMode.value) {
      // Von Edit zu View wechseln - formatieren und highlighten
      updateFormattedSource();
    }
    sourceEditMode.value = !sourceEditMode.value;
  };

  // Source Code View Method
  const toggleSourceView = () => {
      if (!editor.value) return;

      if (!showSource.value) {
          // Wechsle von normalem Editor zu Source-Ansicht
          sourceContent.value = editor.value.getHTML();
          updateFormattedSource();
          sourceEditMode.value = false; // Start im View-Modus
          showSource.value = true;
      } else {
          // Wechsle von Source-Ansicht zu normalem Editor
          editor.value.commands.setContent(sourceContent.value || '', { emitUpdate: false });
          emit('update:modelValue', sourceContent.value || '');
          showSource.value = false;
          sourceEditMode.value = false;
      }
  };
  
  // Emoji Method
  const insertEmoji = (emoji: string) => {
      if (!editor.value) return;
      editor.value.chain().focus().insertContent(emoji).run();
      emojiMenu.value = false; // Menü schließen
  };
  
  // Mention Method
  const insertMention = (mention: { id: number, name: string, role: string }) => {
      if (!editor.value) return;
      
      // Use insertContent instead of insertMention as it's more reliable
      editor.value.chain().focus().insertContent({
          type: 'mention',
          attrs: {
              id: String(mention.id),
              label: mention.name
          }
      }).run();
      
      mentionsActive.value = false;
  };
  
  // Fullscreen Method
  const toggleFullscreen = () => {
      isFullscreen.value = !isFullscreen.value;
      // Optional: Body-Scroll verhindern (mit Vorsicht verwenden)
      if (isFullscreen.value) {
          document.body.style.overflow = 'hidden';
      } else {
          document.body.style.overflow = '';
      }
  };

  // Table of Contents Method
  const updateTableOfContents = (editor: any) => {
    const headings: TocItem[] = [];
    const json = editor.getJSON();

    // Helper function to generate slug from text
    const generateSlug = (text: string): string => {
      return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    };

    // Traverse the document and extract headings
    const traverse = (node: any) => {
      if (node.type === 'heading') {
        const text = node.content?.map((c: any) => c.text).join('') || '';
        if (text) {
          const id = node.attrs.id || generateSlug(text);
          headings.push({
            id,
            text,
            level: node.attrs.level,
          });
        }
      }

      if (node.content) {
        node.content.forEach((child: any) => traverse(child));
      }
    };

    traverse(json);
    tocItems.value = headings;
  };

  // Scroll to heading when clicking TOC item
  const scrollToHeading = (id: string) => {
    if (!editor.value) return;

    console.log('ScrollToHeading called with id:', id);

    // Find heading in document - try multiple selectors
    let element = document.querySelector(`[data-toc-id="${id}"]`) as HTMLElement;

    // If not found, try finding by id directly
    if (!element) {
      element = document.getElementById(id) as HTMLElement;
    }

    // If still not found, try within the editor content
    if (!element) {
      const editorContent = document.querySelector('.ProseMirror');
      if (editorContent) {
        element = editorContent.querySelector(`[data-toc-id="${id}"]`) as HTMLElement;
        if (!element) {
          element = editorContent.querySelector(`#${id}`) as HTMLElement;
        }
      }
    }

    if (!element) {
      console.warn('ScrollToHeading: Element not found for id:', id);
      return;
    }

    console.log('ScrollToHeading: Found element:', element);

    // Find the scrollable container
    // In DocumentEditor context, .tiptap-editor-container is the actual scrollable element
    const editorContainer = document.querySelector('.tiptap-editor-container') as HTMLElement;

    console.log('Found tiptap-editor-container:', !!editorContainer);
    if (editorContainer) {
      console.log('Container dimensions:', {
        scrollHeight: editorContainer.scrollHeight,
        clientHeight: editorContainer.clientHeight,
        scrollTop: editorContainer.scrollTop,
        isScrollable: editorContainer.scrollHeight > editorContainer.clientHeight
      });
    }

    // Use tiptap-editor-container as the scrollable container
    const scrollableContainer: HTMLElement | null = editorContainer;

    const offset = 80;

    if (scrollableContainer) {
      // Scroll within the container
      const elementRect = element.getBoundingClientRect();
      const containerRect = scrollableContainer.getBoundingClientRect();
      const relativeTop = elementRect.top - containerRect.top;
      const scrollTop = scrollableContainer.scrollTop + relativeTop - offset;

      console.log('Scroll calculation:', {
        elementTop: elementRect.top,
        containerTop: containerRect.top,
        relativeTop: relativeTop,
        currentScrollTop: scrollableContainer.scrollTop,
        targetScrollTop: scrollTop,
        offset: offset
      });

      scrollableContainer.scrollTo({
        top: scrollTop,
        behavior: 'smooth'
      });
    } else {
      // Fallback: scroll the element into view
      console.log('No scrollable container found, using scrollIntoView fallback');
      element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
        inline: 'nearest'
      });
    }
  };

  // Füge diese Code-Block-Methoden hinzu
  const addCodeBlock = (language = 'plaintext') => {
    if (!editor.value) return;
    editor.value.chain().focus()
      .setCodeBlock({ language: language })
      .run();
    codeBlockMenu.value = false;
    currentCodeLanguage.value = language;
  };

  const updateCodeLanguage = (language: string) => {
    if (!editor.value) return;
    
    // Prüfe, ob sich der Cursor in einem Code-Block befindet
    if (editor.value.isActive('codeBlock')) {
      editor.value.chain().focus()
        .updateAttributes('codeBlock', { language: language })
        .run();
      currentCodeLanguage.value = language;
    } else {
      // Falls kein Code-Block aktiv ist, füge einen neuen hinzu
      addCodeBlock(language);
    }
    codeBlockMenu.value = false;
  };

  // Methode für Aufgabenlisten
  const toggleTaskList = () => {
    if (!editor.value) return;
    if (editor.value.isActive('taskList')) {
      editor.value.chain().focus().liftListItem('taskItem').run();
    } else {
      editor.value.chain().focus().toggleTaskList().run();
    }
  };

  // Methode für Bildausrichtung
  const setImageAlignment = (alignment: string) => {
    if (!editor.value) return;
    if (editor.value.isActive('image')) {
      editor.value.chain().focus()
        .setTextAlign(alignment)
        .run();
    }
    imageAlignmentMenu.value = false;
  };
  
  // Add the method to handle image uploads
  const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || !target.files.length || !editor.value) return;
    
    const file = target.files[0];
    const reader = new FileReader();
    
    reader.onload = (e) => {
        if (typeof e.target?.result === 'string' && editor.value) {
            // Insert as base64 image
            editor.value.chain().focus().setImage({ src: e.target.result }).run();
            
            // Optional: Apply alignment
            if (imageAlignment.value) {
                editor.value.chain().focus().setTextAlign(imageAlignment.value).run();
            }
        }
    };
    
    reader.readAsDataURL(file);
    // Reset the file input for next use
    target.value = '';
  };

  // Watch editor to initialize TOC on mount
  watch(editor, (newEditor) => {
    if (newEditor && props.showTableOfContents) {
      updateTableOfContents(newEditor);
    }
  }, { immediate: true });

  </script>
  
  <style scoped>
  /* ===== Modern Editor Wrapper ===== */
  .tiptap-editor-wrapper {
    border: 1px solid rgba(var(--v-border-color), 0.12);
    border-radius: 12px;
    transition: all 0.3s ease;
    background-color: rgb(var(--v-theme-surface));
    display: flex;
    flex-direction: row;
    overflow: hidden;
    position: relative;
    height: 100%;
    width: 100%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }
  .tiptap-editor-wrapper:focus-within {
     border-color: rgb(var(--v-theme-primary));
  }
  .tiptap-editor-wrapper.is-readonly {
      background-color: rgba(var(--v-theme-on-surface), 0.04);
      border-color: transparent;
  }

  /* ===== Fullscreen Mode ===== */
  .tiptap-editor-wrapper.is-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    z-index: 2000;
    border-radius: 0;
    border: none;
    background-color: var(--k-sunken) !important; /* Match editor background color */
  }

  .tiptap-editor-wrapper.is-fullscreen .toc-sidebar {
    height: 100vh;
    background-color: var(--k-sunken) !important;
  }

  .tiptap-editor-wrapper.is-fullscreen .editor-area {
    height: 100vh;
    background-color: var(--k-sunken) !important;
  }

  .tiptap-editor-wrapper.is-fullscreen .tiptap-editor-container {
    height: calc(100vh - 144px);
    max-height: calc(100vh - 190px);
    min-height: calc(100vh - 150px);
    background-color: var(--k-sunken) !important;
    overflow-y: auto !important; /* Allow scrolling for long content */
    margin-top: 18px;
  }

  /* Ensure all nested elements in fullscreen have correct background */
  .tiptap-editor-wrapper.is-fullscreen .toolbar-area {
    background-color: var(--k-sunken) !important;
    flex-wrap: wrap !important; /* Force toolbar to wrap to multiple lines */
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
  }

  /* Make ProseMirror editor fill the fullscreen container properly */
  .tiptap-editor-wrapper.is-fullscreen :deep(.ProseMirror) {
    min-height: calc(100vh - 180px) !important;
    height: auto !important;
  }

  /* ===== Modern TOC Sidebar ===== */
  .toc-sidebar {
    width: 250px;
    border-right: 1px solid rgba(var(--v-border-color), 0.08);
    background-color: rgba(var(--v-theme-surface-variant), 0.02);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    height: 100%;
    max-height: 100%;
    overflow: hidden;
  }

  .toc-header {
    padding: 16px;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: transparent;
    font-weight: 600;
    font-size: 0.875rem;
    color: rgba(var(--v-theme-on-surface), 0.7);
  }
  .toc-items {
    padding: 8px 0;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
  }
  .toc-empty {
    padding: 24px 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.6;
  }
  .toc-item {
    padding: 8px 16px;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.875rem;
    line-height: 1.5;
    border-left: 2px solid transparent;
    border-radius: 0 6px 6px 0;
    margin: 2px 8px 2px 0;
  }
  .toc-item:hover {
    background-color: rgba(var(--v-theme-primary), 0.06);
    border-left-color: rgb(var(--v-theme-primary));
    padding-left: 20px;
  }
  .toc-item.toc-level-1 {
    padding-left: 16px;
    font-weight: 600;
  }
  .toc-item.toc-level-2 {
    padding-left: 28px;
    font-weight: 500;
  }
  .toc-item.toc-level-3 {
    padding-left: 40px;
    font-size: 0.8125rem;
  }
  .toc-item.toc-level-4 {
    padding-left: 52px;
    font-size: 0.8125rem;
    opacity: 0.9;
  }
  .toc-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Editor Area */
  .editor-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    height: 100%;
    min-width: 0;
  }

  /* ===== Modern Toolbar Styling ===== */
  .toolbar-area {
      border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      overflow: visible;
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: rgb(var(--v-theme-surface));
      backdrop-filter: blur(8px);
  }

  /* Ensure toolbar wraps in fullscreen mode - already set above */

  .utility-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      background-color: transparent;
      flex-shrink: 0;
  }

  .tiptap-toolbar {
      display: flex;
      flex-wrap: wrap; /* Allow buttons to wrap to multiple lines */
      gap: 2px;
      padding: 8px 12px;
      background-color: transparent;
      flex-shrink: 0;
  }

  /* In fullscreen mode, ensure toolbar has enough space and wraps properly */
  .is-fullscreen .tiptap-toolbar {
      flex-wrap: wrap;
      max-width: 100%;
  }

  /* Modern Button Styles */
  .tiptap-toolbar .v-btn {
      color: rgba(var(--v-theme-on-surface), 0.7);
      border-radius: 6px !important;
      transition: all 0.15s ease;
      min-width: 32px !important;
      height: 32px !important;
  }

  .tiptap-toolbar .v-btn:hover:not([disabled]) {
      background-color: rgba(var(--v-theme-on-surface), 0.08) !important;
      color: rgba(var(--v-theme-on-surface), 0.9);
      transform: translateY(-1px);
  }

  .tiptap-toolbar .v-btn.is-active {
      background-color: rgba(var(--v-theme-primary), 0.12) !important;
      color: rgb(var(--v-theme-primary)) !important;
      font-weight: 600;
  }

  .tiptap-toolbar .v-btn.is-active:hover {
      background-color: rgba(var(--v-theme-primary), 0.18) !important;
  }

  .tiptap-toolbar .v-divider--vertical {
      align-self: stretch;
      height: auto;
      border-color: rgba(var(--v-border-color), 0.15);
      margin: 0 4px;
  }

  /* Emoji Button Styling */
  .emoji-button {
      font-size: 1.2em; /* Emojis größer darstellen */
      min-width: 36px !important; /* Feste Breite für Buttons im Menü */
      padding: 0 !important;
  }
  .emoji-button:hover {
       background-color: rgba(var(--v-theme-on-surface), 0.08);
  }

  /* Source Code Styling */
  .source-code-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 0;
      height: 100%;
  }

  .source-code-header {
      display: flex;
      align-items: center;
      padding: 8px 0;
      flex-shrink: 0;
  }

  .source-code-area .v-textarea {
      flex: 1;
      min-height: 0;
  }

  .source-code-area .v-textarea :deep(.v-input__control) {
      height: 100%;
  }

  .source-code-area .v-textarea :deep(.v-field) {
      height: 100%;
  }

  .source-code-area .v-textarea :deep(.v-field__field) {
      height: 100%;
  }

  .source-code-area .v-textarea :deep(textarea) {
      height: 100% !important;
      max-height: none !important;
  }

  /* Toolbar needs to grow when source mode is active */
  .toolbar-area:has(.source-code-area) {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border-bottom: none;
  }

  /* Fullscreen source code area - ensure proper height and positioning */
  .tiptap-editor-wrapper.is-fullscreen .source-code-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 0;
      padding: 16px;
  }

  .tiptap-editor-wrapper.is-fullscreen .source-code-area .v-textarea {
      flex: 1;
      min-height: calc(100vh - 200px);
  }
  .source-textarea :deep(textarea) {
      font-family: 'Courier New', Courier, monospace;
      line-height: 1.4;
      font-size: 0.9em;
  }

  /* Editor Content Area */
  .tiptap-editor-container {
      padding: 12px;
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      outline: none;
      /* min-height removed to prevent excessive scrolling */
      display: flex;
      flex-direction: column;
  }
  /* Nur wenn bearbeitbar, Cursor anzeigen etc. */
  .tiptap-editor-container.editable {
      cursor: text;
  }

  /* Optimize for FullHD and larger screens */
  @media (min-height: 1080px) {
    .tiptap-editor-container {
      min-height: 70vh;
    }
  }

  /* Make editor-content fill the container */
  .tiptap-editor-container :deep(.ProseMirror-wrapper) {
      flex: 1;
      display: flex;
      flex-direction: column;
  }

  /* Allgemeine Editor-Inhaltsstile */
  :deep(.tiptap-editor-content) {
      outline: none;
      line-height: 1.6;
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      width: 100%;
  }
  :deep(.tiptap-editor-content.ProseMirror) {
      min-height: 100%;
      width: 100%;
  }
  :deep(.tiptap-editor-content p.is-editor-empty:first-child::before) {
      content: attr(data-placeholder);
      float: left;
      color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity));
      pointer-events: none;
      height: 0;
  }
  :deep(.tiptap-editor-content p) { margin-bottom: 0.8em; }
  :deep(.tiptap-editor-content h1),
  :deep(.tiptap-editor-content h2),
  :deep(.tiptap-editor-content h3),
  :deep(.tiptap-editor-content h4),
  :deep(.tiptap-editor-content h5),
  :deep(.tiptap-editor-content h6) {
      line-height: 1.2; margin-top: 1.2em; margin-bottom: 0.6em;
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      font-weight: 600;
  }
  :deep(.tiptap-editor-content ul),
  :deep(.tiptap-editor-content ol) { padding-left: 1.8rem; margin-top: 0.5em; margin-bottom: 0.8em; }
  :deep(.tiptap-editor-content li > p) { margin-bottom: 0.2em; }
  :deep(.tiptap-editor-content li > ul),
  :deep(.tiptap-editor-content li > ol) { margin-top: 0.3em; }
  :deep(.tiptap-editor-content a.editor-link) {
      color: rgb(var(--v-theme-primary));
      text-decoration: underline;
      text-decoration-thickness: 1px;
      text-underline-offset: 2px;
      cursor: pointer;
      transition: color 0.2s ease;
  }
  :deep(.tiptap-editor-content a.editor-link:hover) { color: rgb(var(--v-theme-info)); }
  :deep(.tiptap-editor-content img.editor-image) {
      max-width: 100%; height: auto; display: block; margin: 1em 0; border-radius: 4px;
  }
  :deep(.tiptap-editor-content img.ProseMirror-selectednode) { outline: 3px solid rgb(var(--v-theme-primary)); }
  :deep(.tiptap-editor-content iframe.editor-video) {
      width: 100%; aspect-ratio: 16 / 9; height: auto; border-radius: 4px; border: none; margin: 1em 0;
  }
  :deep(.tiptap-editor-content blockquote) {
      border-left: 3px solid rgba(var(--v-theme-on-surface), 0.3);
      margin: 1rem 0 1rem 1rem;
      padding-left: 1rem;
      color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
      font-style: italic;
  }
  :deep(.tiptap-editor-content hr) {
      border: none;
      border-top: 1px solid rgba(var(--v-theme-on-surface), 0.2);
      margin: 1.5em 0;
  }
  
  /* Code Block Styling (Basis) */
  :deep(.tiptap-editor-content pre) {
      background: rgba(var(--v-theme-on-surface), 0.05);
      color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
      font-family: 'Courier New', Courier, monospace; /* Bessere Monospace-Schrift wählen */
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      margin: 1em 0;
      white-space: pre-wrap;
      overflow-x: auto; /* Falls Code zu breit ist */
  }
  :deep(.tiptap-editor-content pre code) {
      color: inherit; padding: 0; background: none; font-size: 0.9em;
  }
  /* Syntax Highlighting Farben (minimales Beispiel) */
  :deep(.tiptap-editor-content .hljs-comment),
  :deep(.tiptap-editor-content .hljs-quote) { color: #616161; }
  :deep(.tiptap-editor-content .hljs-variable), /*...*/
  :deep(.tiptap-editor-content .hljs-tag), /*...*/
  :deep(.tiptap-editor-content .hljs-regexp) { color: #d32f2f; } /* Rot */
  :deep(.tiptap-editor-content .hljs-string), /*...*/
  :deep(.tiptap-editor-content .hljs-number), /*...*/
  :deep(.tiptap-editor-content .hljs-literal) { color: #f57c00; } /* Orange */
  :deep(.tiptap-editor-content .hljs-keyword), /*...*/
  :deep(.tiptap-editor-content .hljs-selector-tag) { color: #7b1fa2; } /* Lila */
  :deep(.tiptap-editor-content .hljs-function), /*...*/
  :deep(.tiptap-editor-content .hljs-title) { color: var(--k-accent); } /* Blau */
  :deep(.tiptap-editor-content .hljs-built_in), /*...*/
  :deep(.tiptap-editor-content .hljs-class .hljs-title) { color: #388e3c; } /* Grün */

  /* Tabellen Styling */
  :deep(.tiptap-editor-content table) {
      width: 100%; border-collapse: collapse; margin: 1em 0;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.15);
      table-layout: fixed; overflow: hidden;
  }
  :deep(.tiptap-editor-content th),
  :deep(.tiptap-editor-content td) {
      border: 1px solid rgba(var(--v-theme-on-surface), 0.15);
      padding: 8px 12px; vertical-align: top; box-sizing: border-box;
      position: relative; min-width: 50px;
  }
  :deep(.tiptap-editor-content th) {
      background-color: rgba(var(--v-theme-on-surface), 0.05);
      font-weight: bold; text-align: left;
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  }
  /* Für resizable tables */
  :deep(.tiptap-editor-content .resize-cursor) { cursor: col-resize; }
  :deep(.tiptap-editor-content .selectedCell::after) {
       content: ""; position: absolute; left: 0; top: 0; right: 0; bottom: 0;
       background: rgba(var(--v-theme-primary), 0.1);
       pointer-events: none; z-index: 2;
  }
  :deep(.tiptap-editor-content .column-resize-handle) {
      position: absolute; right: -2px; top: 0; bottom: 0; width: 4px;
      background-color: rgba(var(--v-theme-primary), 0.5);
      pointer-events: none; z-index: 20;
  }
  
  /* Character Count */
  .editor-character-count {
       background-color: rgba(var(--v-theme-surface-variant), 0.3);
       border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
       flex-shrink: 0; /* Verhindert Schrumpfen */
  }

  /* Duplicate fullscreen styles removed - using styles defined earlier in file */
  
  /* Erweiterung der Stile für neue Features */
  :deep(.tiptap-editor-content .task-list) {
      padding-left: 1.5em;
      list-style-type: none;
  }

  :deep(.tiptap-editor-content .task-item) {
      display: flex;
      align-items: flex-start;
      margin-bottom: 0.5em;
  }

  :deep(.tiptap-editor-content .task-checkbox) {
      margin-right: 0.5em;
      cursor: pointer;
      user-select: none;
      -webkit-user-select: none;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.4);
      border-radius: 3px;
      width: 1.2em;
      height: 1.2em;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      position: relative;
      box-sizing: border-box;
  }

  :deep(.tiptap-editor-content .task-checkbox.checked::before) {
      content: '✓';
      font-size: 0.8em;
      color: rgb(var(--v-theme-primary));
  }

  :deep(.tiptap-editor-content .code-block) {
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
      background: rgba(var(--v-theme-surface-variant), 0.5);
      color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
      padding: 0.75em 1em;
      border-radius: 6px;
      font-size: 0.9em;
      overflow-x: auto;
      margin: 1em 0;
      white-space: pre;
  }

  :deep(.tiptap-editor-content .code-block .hljs-keyword),
  :deep(.tiptap-editor-content .code-block .hljs-tag),
  :deep(.tiptap-editor-content .code-block .hljs-attr) {
      color: #d63384;
  }

  :deep(.tiptap-editor-content .code-block .hljs-string),
  :deep(.tiptap-editor-content .code-block .hljs-literal) {
      color: #0d6efd;
  }

  :deep(.tiptap-editor-content .code-block .hljs-comment) {
      color: #6c757d;
      font-style: italic;
  }

  :deep(.tiptap-editor-content .code-block .hljs-function),
  :deep(.tiptap-editor-content .code-block .hljs-built_in) {
      color: #fd7e14;
  }

  :deep(.tiptap-editor-content .footnote-content) {
      background: rgba(var(--v-theme-surface-variant), 0.3);
      padding: 0.75em 1em;
      border-radius: 6px;
      margin-top: 2em;
      border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  :deep(.tiptap-editor-content .footnote-item) {
      display: flex;
      align-items: flex-start;
      margin-bottom: 0.5em;
  }

  :deep(.tiptap-editor-content .footnote-marker) {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 1.5em;
      height: 1.5em;
      border-radius: 999px;
      background: rgba(var(--v-theme-primary), 0.1);
      color: rgb(var(--v-theme-primary));
      font-size: 0.75em;
      margin-right: 0.5em;
      padding: 0 0.3em;
      font-weight: bold;
  }

  :deep(.tiptap-editor-content .editor-table) {
      width: 100%;
      border-collapse: collapse;
      margin: 1em 0;
      overflow: hidden;
      table-layout: fixed;
  }

  :deep(.tiptap-editor-content .editor-table td),
  :deep(.tiptap-editor-content .editor-table th) {
      position: relative;
      border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
      padding: 0.5em;
      vertical-align: top;
  }

  :deep(.tiptap-editor-content .editor-table th) {
      font-weight: 600;
      text-align: left;
      background-color: rgba(var(--v-theme-surface-variant), 0.3);
  }

  :deep(.tiptap-editor-content .resize-cursor) {
      cursor: col-resize;
      position: absolute;
      right: -2px;
      top: 0;
      bottom: 0;
      width: 4px;
      background-color: rgb(var(--v-theme-primary));
      opacity: 0;
      transition: opacity 0.3s ease;
  }

  :deep(.tiptap-editor-content .resize-cursor:hover) {
      opacity: 0.5;
  }

  :deep(.tiptap-editor-content img[style*="text-align: center"]) {
      margin-left: auto;
      margin-right: auto;
      display: block;
  }

  :deep(.tiptap-editor-content img[style*="text-align: right"]) {
      margin-left: auto;
      margin-right: 0;
      display: block;
  }

  :deep(.tiptap-editor-content img[style*="text-align: left"]) {
      margin-left: 0;
      margin-right: auto;
      display: block;
  }
  
  /* Mention Styling */
  :deep(.tiptap-editor-content .mention) {
    background: rgba(var(--v-theme-primary), 0.1);
    color: rgb(var(--v-theme-primary));
    border-radius: 4px;
    padding: 0.1em 0.3em;
    margin: 0 0.1em;
    font-weight: 500;
    white-space: nowrap;
  }

  .mentions-dropdown {
    position: absolute;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 50;
    max-width: 300px;
    overflow: hidden;
  }

  .mention-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .mention-item:hover {
    background: rgba(var(--v-theme-primary), 0.05);
  }

  .mention-role {
    font-size: 0.8em;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    margin-left: 8px;
  }
  
  /* File upload button */
  .file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 1;
  }

  /* When hovering the upload button */
  .v-btn:has(.file-input:hover) {
    background-color: rgba(var(--v-theme-on-surface), 0.08);
  }

  /* ===== Details (Collapsible Sections) Styles ===== */
  :deep(.tiptap-editor-content details) {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 8px;
    padding: 12px 16px;
    margin: 1em 0;
    background-color: rgba(var(--v-theme-surface-variant), 0.05);
    transition: all 0.2s ease;
  }

  :deep(.tiptap-editor-content details[open]) {
    background-color: rgba(var(--v-theme-surface-variant), 0.1);
    border-color: rgba(var(--v-theme-primary), 0.3);
  }

  :deep(.tiptap-editor-content details summary) {
    cursor: pointer;
    font-weight: 600;
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    user-select: none;
    list-style: none;
    padding: 4px 0;
    display: flex;
    align-items: center;
  }

  :deep(.tiptap-editor-content details summary::-webkit-details-marker) {
    display: none;
  }

  :deep(.tiptap-editor-content details summary::before) {
    content: '▶';
    display: inline-block;
    margin-right: 8px;
    transition: transform 0.2s ease;
    font-size: 0.8em;
    color: rgb(var(--v-theme-primary));
  }

  :deep(.tiptap-editor-content details[open] summary::before) {
    transform: rotate(90deg);
  }

  :deep(.tiptap-editor-content details summary:hover) {
    color: rgb(var(--v-theme-primary));
  }

  :deep(.tiptap-editor-content details > div) {
    padding-top: 12px;
    padding-left: 20px;
    animation: slideDown 0.2s ease;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* ===== Source Code View Styles ===== */
  .source-code-view {
    flex: 1;
    overflow: auto;
    border-radius: 8px;
    background: var(--k-sunken);
    border: 1px solid rgba(var(--v-border-color), 0.15);
    min-height: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .source-code-pre {
    margin: 0;
    padding: 16px;
    font-family: 'SF Mono', 'Fira Code', 'JetBrains Mono', 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-x: auto;
    background: transparent;
    color: var(--k-ink);
    flex: 1;
    min-height: 0;
  }

  .source-code-pre code {
    font-family: inherit;
    background: transparent;
    padding: 0;
  }

  /* Syntax Highlighting Colors (One Dark inspired) */
  .source-code-pre .hljs-tag {
    color: #e06c75;
  }

  .source-code-pre .hljs-name {
    color: #e06c75;
  }

  .source-code-pre .hljs-attr {
    color: #d19a66;
  }

  .source-code-pre .hljs-string {
    color: #98c379;
  }

  .source-code-pre .hljs-comment {
    color: #5c6370;
    font-style: italic;
  }

  .source-code-pre .hljs-keyword {
    color: #c678dd;
  }

  .source-code-pre .hljs-built_in {
    color: #e6c07b;
  }

  .source-code-pre .hljs-number {
    color: #d19a66;
  }

  .source-code-pre .hljs-literal {
    color: #56b6c2;
  }

  .source-code-pre .hljs-meta {
    color: #61afef;
  }

  .source-code-pre .hljs-doctag {
    color: #c678dd;
  }

  .source-code-pre .hljs-symbol {
    color: #61afef;
  }

  /* Light theme support */
  @media (prefers-color-scheme: light) {
    .source-code-view {
      background: var(--k-sunken);
      border-color: rgba(0, 0, 0, 0.1);
    }

    .source-code-pre {
      color: var(--k-ink-muted);
    }

    .source-code-pre .hljs-tag {
      color: #e45649;
    }

    .source-code-pre .hljs-name {
      color: #e45649;
    }

    .source-code-pre .hljs-attr {
      color: #986801;
    }

    .source-code-pre .hljs-string {
      color: #50a14f;
    }

    .source-code-pre .hljs-comment {
      color: #a0a1a7;
    }

    .source-code-pre .hljs-keyword {
      color: #a626a4;
    }
  }

  /* Vuetify dark theme support */
  .v-theme--dark .source-code-view {
    background: var(--k-sunken);
  }

  .v-theme--light .source-code-view {
    background: var(--k-sunken);
    border-color: rgba(0, 0, 0, 0.1);
  }

  .v-theme--light .source-code-pre {
    color: var(--k-ink-muted);
  }

  .v-theme--light .source-code-pre .hljs-tag,
  .v-theme--light .source-code-pre .hljs-name {
    color: #e45649;
  }

  .v-theme--light .source-code-pre .hljs-attr {
    color: #986801;
  }

  .v-theme--light .source-code-pre .hljs-string {
    color: #50a14f;
  }

  .v-theme--light .source-code-pre .hljs-comment {
    color: #a0a1a7;
  }

  .v-theme--light .source-code-pre .hljs-keyword {
    color: #a626a4;
  }

  /* Fullscreen source code view */
  .tiptap-editor-wrapper.is-fullscreen .source-code-view {
    height: calc(100vh - 120px);
    max-height: calc(100vh - 120px);
  }

  </style>