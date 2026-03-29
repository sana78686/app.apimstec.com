<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
  pages: { type: Array, default: () => [] },
  selectedPage: { type: Object, default: null },
});

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
const processing = reactive({ save: false });

/** SEO requirements (character limits). */
const META_TITLE_MIN = 30;
const META_TITLE_IDEAL_MAX = 60;
const META_TITLE_ABSOLUTE_MAX = 255;
const META_DESC_MIN = 120;
const META_DESC_IDEAL_MAX = 160;
const META_DESC_ABSOLUTE_MAX = 500;

const metaTitleLen = computed(() => (form.meta_title || '').length);
const metaDescLen = computed(() => (form.meta_description || '').length);

const metaTitleStatus = computed(() => {
  const n = metaTitleLen.value;
  if (n === 0) return { status: 'empty', text: `Required for SEO. Recommended: ${META_TITLE_MIN}–${META_TITLE_IDEAL_MAX} characters.` };
  if (n < META_TITLE_MIN) return { status: 'short', text: `Too short. Aim for ${META_TITLE_MIN}–${META_TITLE_IDEAL_MAX} characters (search may not show full title).` };
  if (n <= META_TITLE_IDEAL_MAX) return { status: 'ok', text: `Good length (${META_TITLE_IDEAL_MAX} chars or less).` };
  if (n <= META_TITLE_ABSOLUTE_MAX) return { status: 'long', text: `Long. Google often truncates after ~${META_TITLE_IDEAL_MAX} characters.` };
  return { status: 'ok', text: `${n} / ${META_TITLE_ABSOLUTE_MAX} max.` };
});

const metaDescStatus = computed(() => {
  const n = metaDescLen.value;
  if (n === 0) return { status: 'empty', text: `Recommended: ${META_DESC_MIN}–${META_DESC_IDEAL_MAX} characters for search snippets.` };
  if (n < META_DESC_MIN) return { status: 'short', text: `Short. Aim for ${META_DESC_MIN}–${META_DESC_IDEAL_MAX} characters.` };
  if (n <= META_DESC_IDEAL_MAX) return { status: 'ok', text: `Good length for snippets.` };
  if (n <= META_DESC_ABSOLUTE_MAX) return { status: 'long', text: `May be truncated in search results (ideal: ${META_DESC_IDEAL_MAX} chars).` };
  return { status: 'ok', text: `${n} / ${META_DESC_ABSOLUTE_MAX} max.` };
});

const metaRobotsOptions = [
  { value: 'index,follow', label: 'Index, Follow' },
  { value: 'index,nofollow', label: 'Index, No Follow' },
  { value: 'noindex,follow', label: 'No Index, Follow' },
  { value: 'noindex,nofollow', label: 'No Index, No Follow' },
];

function goToPage(pageId) {
  const url = pageId ? route('seo.meta-manager.create', { page_id: pageId }) : route('seo.meta-manager.create');
  router.visit(url);
}

function fillFromSelectedPage() {
  if (!props.selectedPage) return;
  form.meta_title = props.selectedPage.meta_title ?? props.selectedPage.title ?? '';
  form.meta_description = props.selectedPage.meta_description ?? '';
  if (!form.meta_description && props.selectedPage.content_stripped) {
    const text = props.selectedPage.content_stripped;
    form.meta_description = text.length > 155 ? text.slice(0, 152) + '...' : text;
  }
  form.canonical_url = props.selectedPage.canonical_url ?? '';
  form.meta_robots = props.selectedPage.meta_robots ?? 'index,follow';
}

watch(
  () => props.selectedPage,
  () => fillFromSelectedPage(),
  { immediate: true }
);

async function submit() {
  if (!props.selectedPage?.id) return;
  processing.save = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    await window.axios.put(`/api/pages/${props.selectedPage.id}/seo`, {
      meta_title: form.meta_title,
      meta_description: form.meta_description,
      focus_keyword: form.focus_keyword || null,
      canonical_url: form.canonical_url || null,
      meta_robots: form.meta_robots,
      og_title: form.og_title || null,
      og_description: form.og_description || null,
      og_image: form.og_image || null,
    });
    router.visit(route('seo.meta-manager') + '?success=meta-saved');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.form = e.response?.data?.message || 'Something went wrong.';
    }
  } finally {
    processing.save = false;
  }
}
</script>

<template>
  <Head :title="selectedPage ? `Edit SEO – ${selectedPage.title}` : 'Create meta tag'" />

  <AuthenticatedLayout>
    <template #header>{{ selectedPage ? 'Edit SEO' : 'Create meta tag' }}</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">{{ selectedPage ? 'Edit SEO' : 'Create meta tag' }}</h1>
          <p class="admin-form-page-desc text-muted small">
            <template v-if="selectedPage">SEO settings for <strong>{{ selectedPage.title }}</strong>. Meta tags, focus keyword, canonical URL, robots, and Open Graph fields for search and social sharing.</template>
            <template v-else>Select a page, then set or edit its meta title, description, canonical URL, robots and Open Graph.</template>
          </p>
        </div>
        <div class="admin-form-page-top-actions d-flex gap-2 align-items-center">
          <Link :href="route('seo.meta-manager')" class="btn btn-secondary btn-sm admin-btn-smooth">Back to list</Link>
          <PrimaryButton v-if="selectedPage" type="submit" form="meta-manager-form" :loading="processing.save" :disabled="processing.save" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <div class="mb-4">
          <label class="form-label small fw-semibold">Select page</label>
          <select
            :value="selectedPage?.id ?? ''"
            class="form-select form-select-sm"
            @change="(e) => goToPage(e.target.value ? Number(e.target.value) : null)"
          >
            <option value="">— Choose a page —</option>
            <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }} ({{ p.slug }})</option>
          </select>
        </div>

        <template v-if="selectedPage">
          <p class="text-muted small mb-3">
            Editing meta for <strong>{{ selectedPage.title }}</strong>. Set meta title and description within the recommended character limits.
          </p>

          <div class="admin-seo-requirements mb-4">
            <div class="admin-seo-requirements-title">SEO requirements</div>
            <ul class="admin-seo-requirements-list">
              <li class="admin-seo-requirement-row">
                <span class="admin-seo-requirement-circle" :class="metaTitleStatus.status === 'ok' ? 'admin-seo-requirement-circle--ok' : 'admin-seo-requirement-circle--fail'" :title="metaTitleStatus.status === 'ok' ? 'Done' : 'Not done'">
                  <svg v-if="metaTitleStatus.status === 'ok'" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </span>
                <strong>Meta title:</strong> 30–60 characters recommended (max 255). Shorter titles may be padded; longer ones get cut off in search results.
              </li>
              <li class="admin-seo-requirement-row">
                <span class="admin-seo-requirement-circle" :class="metaDescStatus.status === 'ok' ? 'admin-seo-requirement-circle--ok' : 'admin-seo-requirement-circle--fail'" :title="metaDescStatus.status === 'ok' ? 'Done' : 'Not done'">
                  <svg v-if="metaDescStatus.status === 'ok'" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </span>
                <strong>Meta description:</strong> 120–160 characters recommended (max 500). Used as the snippet in search; too short or too long can hurt clicks.
              </li>
              <li class="admin-seo-requirement-row">
                <span class="admin-seo-requirement-circle admin-seo-requirement-circle--optional" title="Optional">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                </span>
                <strong>Canonical URL:</strong> Optional. Set only if this page is duplicated elsewhere; use the single preferred URL to avoid duplicate-content issues.
              </li>
              <li class="admin-seo-requirement-row">
                <span class="admin-seo-requirement-circle" :class="form.meta_robots === 'index,follow' ? 'admin-seo-requirement-circle--ok' : 'admin-seo-requirement-circle--fail'" :title="form.meta_robots === 'index,follow' ? 'Done (indexing enabled)' : 'No index'">
                  <svg v-if="form.meta_robots === 'index,follow'" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </span>
                <strong>Meta robots:</strong> Use “Index, Follow” for normal pages. Use “No index” for thank-you pages, drafts, or duplicate content you don’t want in search.</li>
            </ul>
          </div>

          <form id="meta-manager-form" class="admin-form-smooth" @submit.prevent="submit">
            <div class="admin-form-page-header mb-3">
              <h2 class="admin-form-page-title admin-form-page-title-sm" style="font-size: 1rem;">Meta tags (search engines)</h2>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="meta_title" value="Meta title" tip="Shown as the clickable title in Google and other search results." optional />
                <div class="d-flex align-items-center gap-2">
                  <TextInput id="meta_title" v-model="form.meta_title" class="form-control form-control-sm flex-grow-1" placeholder="e.g. Page Title | Site Name" maxlength="255" />
                  <span class="admin-seo-requirement-icon admin-seo-requirement-icon--bold" :class="metaTitleStatus.status === 'ok' ? 'admin-seo-requirement-icon--ok' : 'admin-seo-requirement-icon--fail'" :title="metaTitleStatus.status === 'ok' ? 'Meets requirement' : 'Does not meet requirement'">
                    <svg v-if="metaTitleStatus.status === 'ok'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                  </span>
                </div>
                <div class="admin-seo-field-hint" :class="'admin-seo-field-hint--' + metaTitleStatus.status">
                  <span class="admin-seo-char-count">{{ metaTitleLen }} / {{ META_TITLE_ABSOLUTE_MAX }}</span>
                  {{ metaTitleStatus.text }}
                </div>
                <InputError :message="errors.meta_title?.[0]" />
              </div>
              <div class="col-12">
                <LabelWithTooltip for="meta_description" value="Meta description" tip="Shown under the title in search results; influences click-through rate." optional />
                <div class="d-flex align-items-start gap-2">
                  <textarea id="meta_description" v-model="form.meta_description" class="form-control form-control-sm flex-grow-1" rows="2" maxlength="500" placeholder="Brief description for search snippets"></textarea>
                  <span class="admin-seo-requirement-icon admin-seo-requirement-icon--bold mt-1" :class="metaDescStatus.status === 'ok' ? 'admin-seo-requirement-icon--ok' : 'admin-seo-requirement-icon--fail'" :title="metaDescStatus.status === 'ok' ? 'Meets requirement' : 'Does not meet requirement'">
                    <svg v-if="metaDescStatus.status === 'ok'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                  </span>
                </div>
                <div class="admin-seo-field-hint" :class="'admin-seo-field-hint--' + metaDescStatus.status">
                  <span class="admin-seo-char-count">{{ metaDescLen }} / {{ META_DESC_ABSOLUTE_MAX }}</span>
                  {{ metaDescStatus.text }}
                </div>
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
                <div class="admin-seo-field-hint admin-seo-field-hint--muted">Leave blank unless you have duplicate URLs for the same content.</div>
                <InputError :message="errors.canonical_url?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="meta_robots" value="Meta robots" tip="Tells search engines whether to index this page and follow its links." optional />
                <select id="meta_robots" v-model="form.meta_robots" class="form-select form-select-sm">
                  <option v-for="opt in metaRobotsOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <div class="admin-seo-field-hint admin-seo-field-hint--muted">Most pages: Index, Follow. Use No index for drafts or duplicate content.</div>
              </div>
            </div>

            <hr class="my-4" />

            <div class="admin-form-page-header mb-3">
              <h2 class="admin-form-page-title admin-form-page-title-sm" style="font-size: 1rem;">Open Graph (social sharing)</h2>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="og_title" value="OG title" tip="Title when shared on social (Facebook, LinkedIn, etc.). Falls back to meta title if empty." optional />
                <TextInput id="og_title" v-model="form.og_title" class="form-control form-control-sm" placeholder="Falls back to meta title" />
                <InputError :message="errors.og_title?.[0]" />
              </div>
              <div class="col-12">
                <LabelWithTooltip for="og_description" value="OG description" tip="Description when shared on social. Falls back to meta description if empty." optional />
                <textarea id="og_description" v-model="form.og_description" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Falls back to meta description"></textarea>
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
              <button type="button" class="btn btn-secondary btn-sm admin-btn-smooth" @click="goToPage(null)">Change page</button>
              <PrimaryButton type="submit" :loading="processing.save" :disabled="processing.save" class="btn btn-primary btn-sm admin-btn-smooth">Save SEO settings</PrimaryButton>
            </div>
          </form>
        </template>

        <p v-else class="text-muted small mb-0">Select a page above to set or edit its meta title, description, canonical URL and robots tags.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
