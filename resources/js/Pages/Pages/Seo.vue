<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
  pageId: { type: [Number, String], required: true },
  pageTitle: { type: String, default: '' },
  page: { type: Object, default: null },
});

const loading = ref(true);
const processing = ref(false);
const loadError = ref('');
const form = reactive({
  meta_title: '',
  meta_description: '',
  focus_keyword: '',
  canonical_url: '',
  meta_robots: 'index,follow',
  og_title: '',
  og_description: '',
  og_image: '',
});
const errors = reactive({});
const pageIdNum = computed(() => Number(props.pageId) || 0);

const metaRobotsOptions = [
  { value: 'index,follow', label: 'Index, Follow' },
  { value: 'index,nofollow', label: 'Index, No Follow' },
  { value: 'noindex,follow', label: 'No Index, Follow' },
  { value: 'noindex,nofollow', label: 'No Index, No Follow' },
];

onMounted(async () => {
  if (props.page) {
    form.meta_title = props.page.meta_title ?? '';
    form.meta_description = props.page.meta_description ?? '';
    form.focus_keyword = props.page.focus_keyword ?? '';
    form.canonical_url = props.page.canonical_url ?? '';
    form.meta_robots = props.page.meta_robots ?? 'index,follow';
    form.og_title = props.page.og_title ?? '';
    form.og_description = props.page.og_description ?? '';
    form.og_image = props.page.og_image ?? '';
    loading.value = false;
    return;
  }
  try {
    const { data } = await window.axios.get(`/api/pages/${pageIdNum.value}/seo`);
    const p = data.page ?? {};
    form.meta_title = p.meta_title ?? '';
    form.meta_description = p.meta_description ?? '';
    form.focus_keyword = p.focus_keyword ?? '';
    form.canonical_url = p.canonical_url ?? '';
    form.meta_robots = p.meta_robots ?? 'index,follow';
    form.og_title = p.og_title ?? '';
    form.og_description = p.og_description ?? '';
    form.og_image = p.og_image ?? '';
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Failed to load SEO settings.';
  } finally {
    loading.value = false;
  }
});

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    await window.axios.put(`/api/pages/${pageIdNum.value}/seo`, form);
    router.visit(route('pages.index') + '?success=seo-updated');
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
  <Head :title="`Edit SEO – ${pageTitle || 'Page'}`" />

  <AuthenticatedLayout>
    <template #header>Edit SEO</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Edit SEO</h1>
          <p class="admin-form-page-desc text-muted small">
            SEO settings for <strong>{{ pageTitle || 'this page' }}</strong>. Meta tags, focus keyword, canonical URL, robots, and Open Graph fields for search and social sharing.
          </p>
        </div>
        <div v-if="!loading && !loadError" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="seo-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
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
          <form id="seo-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
            <div class="admin-form-page-header mb-3">
              <h2 class="admin-form-page-title admin-form-page-title-sm" style="font-size: 1rem;">Meta tags (search engines)</h2>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="meta_title" value="Meta title" tip="Title shown in search results. Recommended 50–60 characters." optional />
                <TextInput id="meta_title" v-model="form.meta_title" class="form-control form-control-sm" placeholder="e.g. Page Title | Site Name" />
                <InputError :message="errors.meta_title?.[0]" />
              </div>
              <div class="col-12">
                <LabelWithTooltip for="meta_description" value="Meta description" tip="Short description in search results. Recommended 150–160 characters." optional />
                <textarea id="meta_description" v-model="form.meta_description" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Brief description for search snippets"></textarea>
                <InputError :message="errors.meta_description?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="focus_keyword" value="Focus keyword" tip="Primary keyword or phrase this page targets for SEO." optional />
                <TextInput id="focus_keyword" v-model="form.focus_keyword" class="form-control form-control-sm" placeholder="e.g. contact us" />
                <InputError :message="errors.focus_keyword?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="canonical_url" value="Canonical URL" tip="Preferred URL if this content exists at multiple URLs. Leave blank to use current page URL." optional />
                <TextInput id="canonical_url" v-model="form.canonical_url" type="url" class="form-control form-control-sm" placeholder="https://example.com/page" />
                <InputError :message="errors.canonical_url?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="meta_robots" value="Meta robots" tip="Tell search engines whether to index this page and follow links." optional />
                <select id="meta_robots" v-model="form.meta_robots" class="form-select form-select-sm">
                  <option v-for="opt in metaRobotsOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>
            </div>

            <hr class="my-4" />

            <div class="admin-form-page-header mb-3">
              <h2 class="admin-form-page-title admin-form-page-title-sm" style="font-size: 1rem;">Open Graph (social sharing)</h2>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="og_title" value="OG title" tip="Title when shared on social (Facebook, LinkedIn, etc.). Falls back to meta title if empty." optional />
                <TextInput id="og_title" v-model="form.og_title" class="form-control form-control-sm" />
                <InputError :message="errors.og_title?.[0]" />
              </div>
              <div class="col-12">
                <LabelWithTooltip for="og_description" value="OG description" tip="Description when shared on social. Falls back to meta description if empty." optional />
                <textarea id="og_description" v-model="form.og_description" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
                <InputError :message="errors.og_description?.[0]" />
              </div>
              <div class="col-12">
                <LabelWithTooltip for="og_image" value="OG image URL" tip="Image URL shown when the page is shared (e.g. 1200×630px)." optional />
                <TextInput id="og_image" v-model="form.og_image" type="url" class="form-control form-control-sm" placeholder="https://example.com/image.jpg" />
                <InputError :message="errors.og_image?.[0]" />
              </div>
            </div>

            <InputError v-if="errors.form" :message="errors.form" />
            <div class="d-flex gap-2">
              <Link :href="route('pages.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
              <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save SEO settings</PrimaryButton>
            </div>
          </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
