<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const parents = ref([]);
const loading = ref(true);
const processing = ref(false);
const form = reactive({
  title: '',
  slug: '',
  content: '',
  meta_title: '',
  meta_description: '',
  placement: '',
  parent_id: '',
  is_published: false,
  sort_order: 0,
});
const errors = reactive({});

onMounted(async () => {
  try {
    const { data } = await window.axios.get('/api/pages/create');
    parents.value = data.parents ?? [];
  } finally {
    loading.value = false;
  }
});

function slugFromTitle() {
  if (!form.title) return;
  form.slug = form.title.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
}

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    const payload = {
      ...form,
      parent_id: form.parent_id ? Number(form.parent_id) : null,
      sort_order: Number(form.sort_order) || 0,
    };
    await window.axios.post('/api/pages', payload);
    router.visit(route('pages.index') + '?success=created');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.form = e.response?.data?.message || 'Something went wrong.';
    }
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <Head title="Add page" />

  <AuthenticatedLayout>
    <template #header>Add page</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Add page</h1>
          <p class="admin-form-page-desc text-muted small">Create a website page (e.g. FAQ, Contact us). Set parent to make this a child page (shown under parent on frontend).</p>
        </div>
        <div v-if="!loading" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="create-page-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <form id="create-page-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="title" value="Title" tip="Display name of the page." required />
                <TextInput id="title" v-model="form.title" required class="form-control form-control-sm" @blur="slugFromTitle" />
                <InputError :message="errors.title?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="slug" value="Slug" tip="URL-friendly identifier (e.g. contact-us)." required />
                <TextInput id="slug" v-model="form.slug" required class="form-control form-control-sm" />
                <InputError :message="errors.slug?.[0]" />
              </div>
            </div>
            <div class="mb-3">
              <LabelWithTooltip for="content" value="Content" tip="Full page content. Use the toolbar for headings, lists, links and formatting." optional />
              <RichTextEditor v-model="form.content" />
              <InputError :message="errors.content?.[0]" />
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="meta_title" value="Meta title" tip="SEO title." optional />
                <TextInput id="meta_title" v-model="form.meta_title" class="form-control form-control-sm" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="placement" value="Placement" tip="Show in header, footer, or both." optional />
                <select id="placement" v-model="form.placement" class="form-select form-select-sm">
                  <option value="">—</option>
                  <option value="header">Header</option>
                  <option value="footer">Footer</option>
                  <option value="both">Both</option>
                </select>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="parent_id" value="Parent page" tip="Leave empty for a top-level page. Child pages appear under parent in nav." optional />
                <select id="parent_id" v-model="form.parent_id" class="form-select form-select-sm">
                  <option value="">— None (top-level) —</option>
                  <option v-for="p in parents" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="sort_order" value="Sort order" tip="Lower numbers first." optional />
                <TextInput id="sort_order" v-model.number="form.sort_order" type="number" min="0" class="form-control form-control-sm" />
              </div>
            </div>
            <div class="mb-3">
              <div class="form-check">
                <input id="is_published" v-model="form.is_published" type="checkbox" class="form-check-input" />
                <label for="is_published" class="form-check-label small">Published</label>
              </div>
            </div>
            <InputError v-if="errors.form" :message="errors.form" />
            <div class="d-flex gap-2">
              <Link :href="route('pages.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
              <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Create page</PrimaryButton>
            </div>
          </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
