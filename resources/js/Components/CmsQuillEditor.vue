<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { computed } from 'vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
  /** `full` = blogs/pages; `home` = home page editor + Card button */
  variant: { type: String, default: 'full' },
  placeholder: { type: String, default: '' },
});

const fullToolbar = [
  ['bold', 'italic', 'underline', 'strike'],
  [{ header: [1, 2, 3, false] }],
  [{ list: 'ordered' }, { list: 'bullet' }],
  [{ indent: '-1' }, { indent: '+1' }],
  [{ align: [] }],
  ['blockquote', 'code-block'],
  ['link', 'image'],
  ['clean'],
];

const homeToolbar = {
  container: [
    ['bold', 'italic', 'underline'],
    [{ header: [1, 2, 3, false] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ align: [] }],
    ['blockquote'],
    ['card'],
    ['link'],
    ['clean'],
  ],
  handlers: {
    card() {
      const q = this.quill;
      const range = q.getSelection(true);
      const idx = range ? range.index : Math.max(0, q.getLength() - 1);
      const html =
        '<blockquote class="content-card"><p><strong>Card title</strong></p><p>Replace with your text.</p></blockquote>';
      q.clipboard.dangerouslyPasteHTML(idx, html);
    },
  },
};

const toolbar = computed(() => (props.variant === 'home' ? homeToolbar : fullToolbar));
</script>

<template>
  <div class="rich-text-editor admin-rich-text-editor cms-quill-editor">
    <QuillEditor
      v-model:content="model"
      content-type="html"
      theme="snow"
      :toolbar="toolbar"
      :placeholder="placeholder || undefined"
    />
  </div>
</template>

<style scoped>
.cms-quill-editor :deep(.ql-toolbar.ql-snow) {
  border: none;
  border-bottom: 1px solid var(--admin-card-border, #eaeaef);
  background: var(--admin-main-bg, #f6f6f9);
}
.cms-quill-editor :deep(.ql-container.ql-snow) {
  border: none;
  font-size: 0.9375rem;
  min-height: 300px;
}
.cms-quill-editor :deep(.ql-editor) {
  min-height: 280px;
  line-height: 1.6;
}
.cms-quill-editor :deep(.ql-editor blockquote.content-card) {
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  margin: 1rem 0;
  background: #fafafa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  border-left: 4px solid var(--admin-primary, #181826);
}

/* Custom toolbar control for home “Card” */
.cms-quill-editor :deep(button.ql-card) {
  width: auto !important;
  padding: 0 8px !important;
  font-size: 0.75rem;
  font-weight: 600;
}
.cms-quill-editor :deep(button.ql-card::after) {
  content: 'Card';
}
</style>
