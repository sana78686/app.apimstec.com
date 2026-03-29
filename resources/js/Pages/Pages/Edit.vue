<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
  pageId: { type: [Number, String], required: true },
  page: { type: Object, default: null },
  parents: { type: Array, default: () => [] },
});

const parents = ref(props.parents || []);
const loading = ref(true);
const processing = ref(false);
const loadError = ref('');
const STATUS_OPTIONS = [
  { value: 'draft',    label: 'Draft — not visible on site'     },
  { value: 'visible',  label: 'Visible — live on frontend'      },
  { value: 'disabled', label: 'Disabled — hidden from frontend' },
];

const form = reactive({
  title: '',
  slug: '',
  content: '',
  meta_title: '',
  meta_description: '',
  placement: '',
  parent_id: '',
  visibility: 'draft',
  sort_order: 0,
});
const errors = reactive({});
const pageIdNum = computed(() => Number(props.pageId) || 0);

onMounted(async () => {
  if (props.page) {
    form.title = props.page.title ?? '';
    form.slug = props.page.slug ?? '';
    form.content = props.page.content ?? '';
    form.meta_title = props.page.meta_title ?? '';
    form.meta_description = props.page.meta_description ?? '';
    form.placement = props.page.placement ?? '';
    form.parent_id = props.page.parent_id ?? '';
    form.visibility = props.page.visibility ?? 'draft';
    form.sort_order = props.page.sort_order ?? 0;
    loading.value = false;
    return;
  }
  try {
    const { data } = await window.axios.get(`/api/pages/${pageIdNum.value}/edit`);
    const p = data.page ?? {};
    form.title = p.title ?? '';
    form.slug = p.slug ?? '';
    form.content = p.content ?? '';
    form.meta_title = p.meta_title ?? '';
    form.meta_description = p.meta_description ?? '';
    form.placement = p.placement ?? '';
    form.parent_id = p.parent_id ?? '';
    form.visibility = p.visibility ?? 'draft';
    form.sort_order = p.sort_order ?? 0;
    parents.value = data.parents ?? [];
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Failed to load page.';
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
    await window.axios.put(`/api/pages/${pageIdNum.value}`, payload);
    router.visit(route('pages.index') + '?success=updated');
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
  <Head title="Edit page" />

  <AuthenticatedLayout>
    <template #header>Edit page</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Edit page</h1>
          <p class="admin-form-page-desc text-muted small">Update page details. Child pages appear under their parent in the frontend nav.</p>
        </div>
        <div v-if="!loading && !loadError" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="edit-page-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <div v-else-if="loadError" key="error" class="text-danger small mb-0">
            {{ loadError }}
            <Link :href="route('pages.index')" class="ms-2">Back to pages</Link>
          </div>
          <form id="edit-page-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="title" value="Title" required />
                <TextInput id="title" v-model="form.title" required class="form-control form-control-sm" @blur="slugFromTitle" />
                <InputError :message="errors.title?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="slug" value="Slug" required />
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
                <LabelWithTooltip for="meta_title" value="Meta title" optional />
                <TextInput id="meta_title" v-model="form.meta_title" class="form-control form-control-sm" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="placement" value="Placement" optional />
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
                <LabelWithTooltip for="parent_id" value="Parent page" optional />
                <select id="parent_id" v-model="form.parent_id" class="form-select form-select-sm">
                  <option value="">— None (top-level) —</option>
                  <option v-for="p in parents" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="sort_order" value="Sort order" optional />
                <TextInput id="sort_order" v-model.number="form.sort_order" type="number" min="0" class="form-control form-control-sm" />
              </div>
            </div>
            <div class="mb-3">
              <LabelWithTooltip for="visibility" value="Status" tip="Draft = not shown. Visible = live on site. Disabled = hidden." />
              <select id="visibility" v-model="form.visibility" class="form-select form-select-sm" style="max-width:260px;">
                <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
            <InputError v-if="errors.form" :message="errors.form" />
            <div class="d-flex gap-2">
              <Link :href="route('pages.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
              <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Update page</PrimaryButton>
            </div>
          </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
