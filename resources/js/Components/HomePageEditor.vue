<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Card } from '@/Extensions/Card';
import { watch, onBeforeUnmount } from 'vue';

const model = defineModel({ type: String, default: '' });

const editor = useEditor({
  content: model.value || '',
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3] },
    }),
    Link.configure({
      openOnClick: false,
      HTMLAttributes: { target: '_blank', rel: 'noopener' },
    }),
    Placeholder.configure({ placeholder: 'Design your home page. Use "Card" to add card blocks…' }),
    Card,
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

watch(
  () => model.value,
  (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
      editor.value.commands.setContent(val || '', false);
    }
  }
);

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
  <div class="rich-text-editor admin-rich-text-editor home-page-editor">
    <div v-if="editor" class="rich-text-editor-toolbar">
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('bold') }"
        title="Bold"
        @click="editor.chain().focus().toggleBold().run()"
      >
        <strong>B</strong>
      </button>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('italic') }"
        title="Italic"
        @click="editor.chain().focus().toggleItalic().run()"
      >
        <em>I</em>
      </button>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
        title="Heading 1"
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
      >
        H1
      </button>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        title="Heading 2"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
      >
        H2
      </button>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
        title="Heading 3"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
      >
        H3
      </button>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        title="Bullet list"
        @click="editor.chain().focus().toggleBulletList().run()"
      >
        •
      </button>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        title="Numbered list"
        @click="editor.chain().focus().toggleOrderedList().run()"
      >
        1.
      </button>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('contentCard') }"
        title="Insert card (for home page design)"
        @click="editor.chain().focus().setCard().run()"
      >
        Card
      </button>
      <span class="rich-text-editor-sep" aria-hidden="true"></span>
      <button
        type="button"
        class="rich-text-editor-btn"
        :class="{ 'is-active': editor.isActive('link') }"
        title="Link"
        @click="editor.chain().focus().toggleLink({ href: prompt('URL:') || undefined }).run()"
      >
        Link
      </button>
      <button
        type="button"
        class="rich-text-editor-btn"
        title="Horizontal rule"
        @click="editor.chain().focus().setHorizontalRule().run()"
      >
        —
      </button>
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
.rich-text-editor-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 6px;
  font-size: 0.8125rem;
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
  min-height: 320px;
}
.rich-text-editor-content :deep(.tiptap) {
  min-height: 320px;
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
.rich-text-editor-content :deep(.tiptap ul) { padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-text-editor-content :deep(.tiptap ol) { padding-left: 1.5rem; margin: 0.5rem 0; }
.rich-text-editor-content :deep(.tiptap a) { color: var(--admin-primary, #181826); text-decoration: underline; }
.rich-text-editor-content :deep(.tiptap hr) { border: none; border-top: 1px solid #eaeaef; margin: 1rem 0; }
/* Card block styling in editor */
.rich-text-editor-content :deep(.tiptap .content-card) {
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  margin: 1rem 0;
  background: #fafafa;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
</style>
