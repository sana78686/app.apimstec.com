<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { Table, TableRow, TableCell, TableHeader } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import { watch, onBeforeUnmount, ref, computed } from 'vue';

const model = defineModel({ type: String, default: '' });

const linkUrl = ref('');
const linkModalOpen = ref(false);
const imageUrl = ref('');
const imageModalOpen = ref(false);
const imageTitle = ref('');
const imageAlt = ref('');
const imageUploadLoading = ref(false);
const imageUploadError = ref('');
const imageFileInput = ref(null);

const editor = useEditor({
  content: model.value || '',
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3, 4, 5, 6] },
      codeBlock: { HTMLAttributes: { class: 'rich-text-code-block' } },
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer' },
    }),
    Placeholder.configure({ placeholder: 'Write your page content here…' }),
    Underline,
    Highlight.configure({ multicolor: true }),
    Image.configure({
      inline: false,
      allowBase64: true,
      HTMLAttributes: { class: 'rich-text-editor-img' },
    }),
    Table.configure({
      resizable: true,
      HTMLAttributes: { class: 'rich-text-editor-table' },
    }),
    TableRow,
    TableHeader,
    TableCell,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    TaskList,
    TaskItem.configure({ nested: true }),
  ],
  editorProps: {
    attributes: {
      class: 'rich-text-editor-body',
    },
  },
  onUpdate: ({ editor: e }) => {
    model.value = e.getHTML();
  },
});

function openLinkModal() {
  const prev = editor.value?.getAttributes('link');
  linkUrl.value = prev?.href || '';
  linkModalOpen.value = true;
}

function setLink() {
  const url = linkUrl.value?.trim() || null;
  editor.value?.chain().focus().setLink({ href: url }).run();
  if (!url) editor.value?.chain().focus().unsetLink().run();
  linkModalOpen.value = false;
  linkUrl.value = '';
}

function openImageModal() {
  imageUrl.value = '';
  imageTitle.value = '';
  imageAlt.value = '';
  imageUploadError.value = '';
  imageModalOpen.value = true;
}

async function onImageFileSelected(event) {
  const file = event.target.files?.[0];
  if (!file || !file.type.startsWith('image/')) {
    imageUploadError.value = 'Please select an image file (JPEG, PNG, GIF, WebP).';
    return;
  }
  imageUploadError.value = '';
  imageUploadLoading.value = true;
  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('alt_text', imageAlt.value || '');
    const { data } = await window.axios.post('/api/media/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    imageUrl.value = data.url || '';
    if (!imageAlt.value && file.name) {
      imageAlt.value = file.name.replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
    }
  } catch (err) {
    imageUploadError.value = err.response?.data?.message || err.response?.data?.errors?.image?.[0] || 'Upload failed.';
  } finally {
    imageUploadLoading.value = false;
    event.target.value = '';
  }
}

function triggerImageFileInput() {
  imageFileInput.value?.click();
}

function setImage() {
  const src = imageUrl.value?.trim();
  if (src) {
    editor.value?.chain().focus().setImage({ src, title: imageTitle.value || undefined, alt: imageAlt.value || undefined }).run();
  }
  imageModalOpen.value = false;
  imageUrl.value = '';
  imageTitle.value = '';
  imageAlt.value = '';
}

const isTableActive = computed(() => editor.value && (editor.value.isActive('table') || editor.value.isActive('tableCell') || editor.value.isActive('tableHeader')));

// Sync when parent sets content (e.g. Edit page after API load)
watch(
  () => model.value,
  (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
      editor.value.commands.setContent(val || '', false);
    }
  }
);

// When editor becomes ready, set content from model (handles async init on Edit page)
watch(
  () => editor.value,
  (inst) => {
    if (inst && model.value) {
      const current = inst.getHTML();
      if (current !== model.value) {
        inst.commands.setContent(model.value, false);
      }
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  editor.value?.destroy();
});
</script>

<template>
  <div class="rich-text-editor admin-rich-text-editor">
    <!-- Link modal -->
    <Teleport to="body">
      <div v-if="linkModalOpen" class="rich-text-editor-modal-backdrop" @click.self="linkModalOpen = false">
        <div class="rich-text-editor-modal" role="dialog" aria-label="Insert link">
          <div class="rich-text-editor-modal-header">
            <span>{{ editor?.isActive('link') ? 'Edit link' : 'Insert link' }}</span>
            <button type="button" class="rich-text-editor-modal-close" aria-label="Close" @click="linkModalOpen = false">&times;</button>
          </div>
          <div class="rich-text-editor-modal-body">
            <label class="rich-text-editor-modal-label">URL</label>
            <input v-model="linkUrl" type="url" class="rich-text-editor-modal-input" placeholder="https://" @keydown.enter.prevent="setLink" />
          </div>
          <div class="rich-text-editor-modal-footer">
            <button type="button" class="rich-text-editor-btn" @click="linkModalOpen = false">Cancel</button>
            <button type="button" class="rich-text-editor-btn is-active" @click="setLink">Apply</button>
          </div>
        </div>
      </div>
      <div v-if="imageModalOpen" class="rich-text-editor-modal-backdrop" @click.self="imageModalOpen = false">
        <div class="rich-text-editor-modal rich-text-editor-modal--image" role="dialog" aria-label="Insert image">
          <div class="rich-text-editor-modal-header">
            <span>Insert image</span>
            <button type="button" class="rich-text-editor-modal-close" aria-label="Close" @click="imageModalOpen = false">&times;</button>
          </div>
          <div class="rich-text-editor-modal-body">
            <div class="rich-text-editor-modal-field">
              <label class="rich-text-editor-modal-label">Upload image</label>
              <input ref="imageFileInput" type="file" accept="image/*" class="rich-text-editor-file-input" @change="onImageFileSelected" />
              <button type="button" class="rich-text-editor-upload-btn" :disabled="imageUploadLoading" @click="triggerImageFileInput">
                {{ imageUploadLoading ? 'Uploading…' : 'Choose file' }}
              </button>
              <p v-if="imageUploadError" class="rich-text-editor-upload-error">{{ imageUploadError }}</p>
            </div>
            <div class="rich-text-editor-modal-divider">or enter URL</div>
            <div class="rich-text-editor-modal-field">
              <label class="rich-text-editor-modal-label">Image URL</label>
              <input v-model="imageUrl" type="url" class="rich-text-editor-modal-input" placeholder="https:// or paste after upload" />
            </div>
            <div class="rich-text-editor-modal-field">
              <label class="rich-text-editor-modal-label">Alt text (optional)</label>
              <input v-model="imageAlt" type="text" class="rich-text-editor-modal-input" placeholder="Describe the image" />
            </div>
            <div class="rich-text-editor-modal-field">
              <label class="rich-text-editor-modal-label">Title (optional)</label>
              <input v-model="imageTitle" type="text" class="rich-text-editor-modal-input" placeholder="Tooltip text" />
            </div>
          </div>
          <div class="rich-text-editor-modal-footer">
            <button type="button" class="rich-text-editor-btn" @click="imageModalOpen = false">Cancel</button>
            <button type="button" class="rich-text-editor-btn is-active" :disabled="!imageUrl.trim()" @click="setImage">Insert</button>
          </div>
        </div>
      </div>
    </Teleport>

    <div v-if="editor" class="rich-text-editor-toolbar">
      <!-- Row 1: Text formatting -->
      <div class="rich-text-editor-toolbar-group">
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('bold') }" title="Bold (Ctrl+B)" @click="editor.chain().focus().toggleBold().run()"><strong>B</strong></button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('italic') }" title="Italic (Ctrl+I)" @click="editor.chain().focus().toggleItalic().run()"><em>I</em></button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('underline') }" title="Underline" @click="editor.chain().focus().toggleUnderline().run()"><u>U</u></button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('strike') }" title="Strikethrough" @click="editor.chain().focus().toggleStrike().run()"><s>S</s></button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('highlight') }" title="Highlight" @click="editor.chain().focus().toggleHighlight().run()">Highlight</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('code') }" title="Inline code" @click="editor.chain().focus().toggleCode().run()">&lt;/&gt;</button>
      </div>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <!-- Headings -->
      <div class="rich-text-editor-toolbar-group">
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }" title="Heading 1" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">H1</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" title="Heading 2" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" title="Heading 3" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 4 }) }" title="Heading 4" @click="editor.chain().focus().toggleHeading({ level: 4 }).run()">H4</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 5 }) }" title="Heading 5" @click="editor.chain().focus().toggleHeading({ level: 5 }).run()">H5</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('heading', { level: 6 }) }" title="Heading 6" @click="editor.chain().focus().toggleHeading({ level: 6 }).run()">H6</button>
      </div>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <!-- Paragraph & lists -->
      <div class="rich-text-editor-toolbar-group">
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('paragraph') }" title="Paragraph" @click="editor.chain().focus().setParagraph().run()">P</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('bulletList') }" title="Bullet list" @click="editor.chain().focus().toggleBulletList().run()">• List</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('orderedList') }" title="Numbered list" @click="editor.chain().focus().toggleOrderedList().run()">1. List</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('taskList') }" title="Task list" @click="editor.chain().focus().toggleTaskList().run()">☑ List</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('blockquote') }" title="Blockquote" @click="editor.chain().focus().toggleBlockquote().run()">Quote</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('codeBlock') }" title="Code block" @click="editor.chain().focus().toggleCodeBlock().run()">Code</button>
      </div>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <!-- Alignment -->
      <div class="rich-text-editor-toolbar-group">
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" title="Align left" @click="editor.chain().focus().setTextAlign('left').run()">≡ Left</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" title="Align center" @click="editor.chain().focus().setTextAlign('center').run()">≡ Center</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" title="Align right" @click="editor.chain().focus().setTextAlign('right').run()">≡ Right</button>
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }" title="Justify" @click="editor.chain().focus().setTextAlign('justify').run()">≡ Justify</button>
      </div>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <!-- Insert -->
      <div class="rich-text-editor-toolbar-group">
        <button type="button" class="rich-text-editor-btn" :class="{ 'is-active': editor.isActive('link') }" title="Link" @click="openLinkModal">Link</button>
        <button type="button" class="rich-text-editor-btn" title="Insert image" @click="openImageModal">Image</button>
        <button type="button" class="rich-text-editor-btn" title="Horizontal rule" @click="editor.chain().focus().setHorizontalRule().run()">—</button>
        <button type="button" class="rich-text-editor-btn" title="Insert table" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">Table</button>
      </div>
      <!-- Table operations (visible when cursor in table) -->
      <template v-if="isTableActive">
        <span class="rich-text-editor-sep" aria-hidden="true"></span>
        <div class="rich-text-editor-toolbar-group">
          <button type="button" class="rich-text-editor-btn" title="Add row before" @click="editor.chain().focus().addRowBefore().run()">+ Row ↑</button>
          <button type="button" class="rich-text-editor-btn" title="Add row after" @click="editor.chain().focus().addRowAfter().run()">+ Row ↓</button>
          <button type="button" class="rich-text-editor-btn" title="Add column before" @click="editor.chain().focus().addColumnBefore().run()">+ Col ←</button>
          <button type="button" class="rich-text-editor-btn" title="Add column after" @click="editor.chain().focus().addColumnAfter().run()">+ Col →</button>
          <button type="button" class="rich-text-editor-btn" title="Delete row" @click="editor.chain().focus().deleteRow().run()">− Row</button>
          <button type="button" class="rich-text-editor-btn" title="Delete column" @click="editor.chain().focus().deleteColumn().run()">− Col</button>
          <button type="button" class="rich-text-editor-btn" title="Delete table" @click="editor.chain().focus().deleteTable().run()">− Table</button>
          <button type="button" class="rich-text-editor-btn" title="Merge cells" @click="editor.chain().focus().mergeCells().run()">Merge</button>
          <button type="button" class="rich-text-editor-btn" title="Split cell" @click="editor.chain().focus().splitCell().run()">Split</button>
          <button type="button" class="rich-text-editor-btn" title="Toggle header row" @click="editor.chain().focus().toggleHeaderRow().run()">Header row</button>
          <button type="button" class="rich-text-editor-btn" title="Toggle header column" @click="editor.chain().focus().toggleHeaderColumn().run()">Header col</button>
        </div>
      </template>
    </div>
    <EditorContent :editor="editor" class="rich-text-editor-content" />
  </div>
</template>

<style scoped>
.rich-text-editor {
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: var(--admin-radius, 4px);
  background: #fff;
  overflow: hidden;
}
.rich-text-editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 2px;
  padding: 0.35rem 0.5rem;
  background: var(--admin-main-bg, #f6f6f9);
  border-bottom: 1px solid var(--admin-card-border, #eaeaef);
}
.rich-text-editor-toolbar-group {
  display: flex;
  align-items: center;
  gap: 2px;
}
.rich-text-editor-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 6px;
  font-size: 0.75rem;
  color: var(--admin-text, #32324d);
  background: transparent;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.rich-text-editor-btn:hover {
  background: rgba(24, 24, 38, 0.08);
  color: var(--admin-text, #32324d);
}
.rich-text-editor-btn.is-active {
  background: var(--admin-primary, #181826);
  color: #fff;
}
.rich-text-editor-sep {
  width: 1px;
  height: 20px;
  background: var(--admin-card-border, #eaeaef);
  margin: 0 4px;
}
.rich-text-editor-content {
  min-height: 280px;
}
.rich-text-editor-content :deep(.tiptap) {
  min-height: 280px;
  padding: 0.75rem 1rem;
  font-size: 0.9375rem;
  line-height: 1.6;
  outline: none;
}
.rich-text-editor-content :deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #999;
  pointer-events: none;
  height: 0;
}
.rich-text-editor-content :deep(.tiptap h1) { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.rich-text-editor-content :deep(.tiptap h2) { font-size: 1.25rem; font-weight: 600; margin: 0.875rem 0 0.5rem; }
.rich-text-editor-content :deep(.tiptap h3) { font-size: 1.125rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.rich-text-editor-content :deep(.tiptap h4) { font-size: 1rem; font-weight: 600; margin: 0.65rem 0 0.4rem; }
.rich-text-editor-content :deep(.tiptap h5) { font-size: 0.9375rem; font-weight: 600; margin: 0.5rem 0 0.35rem; }
.rich-text-editor-content :deep(.tiptap h6) { font-size: 0.875rem; font-weight: 600; margin: 0.5rem 0 0.35rem; }
.rich-text-editor-content :deep(.tiptap ul) { padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-text-editor-content :deep(.tiptap ol) { padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-text-editor-content :deep(.tiptap ul[data-type="taskList"]) { list-style: none; padding-left: 0; }
.rich-text-editor-content :deep(.tiptap li[data-type="taskItem"]) { display: flex; align-items: flex-start; gap: 0.5rem; }
.rich-text-editor-content :deep(.tiptap li[data-type="taskItem"] > label) { flex-shrink: 0; margin: 0.2em 0 0 0; }
.rich-text-editor-content :deep(.tiptap li[data-type="taskItem"] > div) { flex: 1; }
.rich-text-editor-content :deep(.tiptap li[data-type="taskItem"][data-checked="true"]) { text-decoration: line-through; color: #666; }
.rich-text-editor-content :deep(.tiptap blockquote) { border-left: 4px solid var(--admin-card-border, #eaeaef); padding-left: 1rem; margin: 0.75rem 0; color: #555; }
.rich-text-editor-content :deep(.tiptap pre) { background: #f4f4f6; padding: 0.75rem 1rem; border-radius: 4px; overflow-x: auto; margin: 0.5rem 0; }
.rich-text-editor-content :deep(.tiptap code) { background: #f4f4f6; padding: 0.15em 0.35em; border-radius: 3px; font-size: 0.9em; }
.rich-text-editor-content :deep(.tiptap .rich-text-code-block) { font-family: ui-monospace, monospace; font-size: 0.875rem; }
.rich-text-editor-content :deep(.tiptap a) { color: var(--admin-primary, #181826); text-decoration: underline; }
.rich-text-editor-content :deep(.tiptap hr) { border: none; border-top: 1px solid #eaeaef; margin: 1rem 0; }
.rich-text-editor-content :deep(.tiptap mark) { background: rgba(255, 212, 0, 0.4); padding: 0 0.1em; }
.rich-text-editor-content :deep(.tiptap .rich-text-editor-img) { max-width: 100%; height: auto; border-radius: 4px; }
.rich-text-editor-content :deep(.tiptap .rich-text-editor-table) { border-collapse: collapse; width: 100%; margin: 0.75rem 0; }
.rich-text-editor-content :deep(.tiptap .rich-text-editor-table td),
.rich-text-editor-content :deep(.tiptap .rich-text-editor-table th) { border: 1px solid #eaeaef; padding: 0.5rem 0.75rem; text-align: left; }
.rich-text-editor-content :deep(.tiptap .rich-text-editor-table th) { background: var(--admin-main-bg, #f6f6f9); font-weight: 600; }
.rich-text-editor-content :deep(.tiptap [style*="text-align: center"]) { text-align: center; }
.rich-text-editor-content :deep(.tiptap [style*="text-align: right"]) { text-align: right; }
.rich-text-editor-content :deep(.tiptap [style*="text-align: justify"]) { text-align: justify; }

/* Modals */
.rich-text-editor-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.rich-text-editor-modal {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  min-width: 320px;
  max-width: 90vw;
}
.rich-text-editor-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--admin-card-border, #eaeaef);
  font-weight: 600;
}
.rich-text-editor-modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: #666;
  padding: 0 0.25rem;
}
.rich-text-editor-modal-close:hover { color: #333; }
.rich-text-editor-modal-body { padding: 1rem; }
.rich-text-editor-modal-field { margin-bottom: 0.75rem; }
.rich-text-editor-modal-field:last-child { margin-bottom: 0; }
.rich-text-editor-modal-label { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.25rem; color: #555; }
.rich-text-editor-modal-input {
  width: 100%;
  padding: 0.5rem 0.6rem;
  font-size: 0.9375rem;
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 4px;
}
.rich-text-editor-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--admin-card-border, #eaeaef);
}
.rich-text-editor-file-input {
  position: absolute;
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  z-index: -1;
}
.rich-text-editor-upload-btn {
  display: inline-block;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  color: var(--admin-primary, #181826);
  background: var(--admin-main-bg, #f6f6f9);
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 4px;
  cursor: pointer;
}
.rich-text-editor-upload-btn:hover:not(:disabled) {
  background: rgba(24, 24, 38, 0.06);
}
.rich-text-editor-upload-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.rich-text-editor-upload-error {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: #c00;
}
.rich-text-editor-modal-divider {
  font-size: 0.75rem;
  color: #888;
  margin: 0.75rem 0 0.5rem;
  text-align: center;
}
</style>
