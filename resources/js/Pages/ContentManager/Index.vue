<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CmsLocaleSelect from '@/Components/CmsLocaleSelect.vue';
import HomePageEditor from '@/Components/HomePageEditor.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  contentLocale: { type: String, default: 'id' },
  homePageContent: { type: String, default: '' },
  homeMetaTitle: { type: String, default: '' },
  homeMetaDescription: { type: String, default: '' },
  homeMetaKeywords: { type: String, default: '' },
  homeFocusKeyword: { type: String, default: '' },
  homeOgTitle: { type: String, default: '' },
  homeOgDescription: { type: String, default: '' },
  homeOgImage: { type: String, default: '' },
  homeMetaRobots: { type: String, default: 'index,follow' },
  homeCanonicalUrl: { type: String, default: '' },
  homeFrontendHeadSnippet: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const homeSeoRoute = 'content-manager.home-seo.update';

const contentForm = useForm({
  locale: props.contentLocale,
  home_page_content: props.homePageContent,
});

const metaForm = useForm({
  locale: props.contentLocale,
  meta_title: props.homeMetaTitle,
  meta_description: props.homeMetaDescription,
  meta_keywords: props.homeMetaKeywords,
  focus_keyword: props.homeFocusKeyword,
  meta_robots: props.homeMetaRobots || 'index,follow',
  canonical_url: props.homeCanonicalUrl,
  frontend_head_snippet: props.homeFrontendHeadSnippet,
});

const ogForm = useForm({
  locale: props.contentLocale,
  og_title: props.homeOgTitle,
  og_description: props.homeOgDescription,
  og_image: props.homeOgImage,
});

watch(() => props.contentLocale, (val) => {
  const l = val || 'id';
  contentForm.locale = l;
  metaForm.locale = l;
  ogForm.locale = l;
});
watch(() => props.homePageContent, (val) => {
  contentForm.home_page_content = val ?? '';
});
watch(() => props.homeMetaTitle, (val) => { metaForm.meta_title = val ?? ''; });
watch(() => props.homeMetaDescription, (val) => { metaForm.meta_description = val ?? ''; });
watch(() => props.homeMetaKeywords, (val) => { metaForm.meta_keywords = val ?? ''; });
watch(() => props.homeFocusKeyword, (val) => { metaForm.focus_keyword = val ?? ''; });
watch(() => props.homeOgTitle, (val) => { ogForm.og_title = val ?? ''; });
watch(() => props.homeOgDescription, (val) => { ogForm.og_description = val ?? ''; });
watch(() => props.homeOgImage, (val) => { ogForm.og_image = val ?? ''; });
watch(() => props.homeMetaRobots, (val) => { metaForm.meta_robots = val ?? 'index,follow'; });
watch(() => props.homeCanonicalUrl, (val) => { metaForm.canonical_url = val ?? ''; });
watch(() => props.homeFrontendHeadSnippet, (val) => { metaForm.frontend_head_snippet = val ?? ''; });

function submitContent() {
  contentForm.clearErrors();
  contentForm.locale = props.contentLocale;
  contentForm.put(route(homeSeoRoute), { preserveScroll: true });
}

function submitMeta() {
  metaForm.clearErrors();
  metaForm.locale = props.contentLocale;
  metaForm.put(route(homeSeoRoute), { preserveScroll: true });
}

function submitOg() {
  ogForm.clearErrors();
  ogForm.locale = props.contentLocale;
  ogForm.put(route(homeSeoRoute), { preserveScroll: true });
}

function switchContentLocale(loc) {
  router.get(route('content-manager.index'), { content_locale: loc }, { preserveScroll: true });
}
</script>

<template>
  <Head title="Home page – Content manager" />

  <AuthenticatedLayout>
    <template #header>Home page</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Home page</h1>
        <p class="admin-form-page-desc text-muted small">
          Edit the main content of the frontend home page. Use the <strong>Card</strong> button in the toolbar to add card blocks.
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <CmsLocaleSelect
          :model-value="contentLocale"
          id="home-content-locale"
          label="Content language"
          @update:model-value="switchContentLocale"
        />
        <p class="text-muted small mt-2 mb-0">
          You are editing home page body and SEO for this language. Public site uses the same code from the visitor’s language.
        </p>
      </div>

      <!-- Meta tags -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Meta tags</h2>
        <p class="text-muted small mb-3">
          Title, description, keywords, and indexing hints for the frontend home page. Submitting only updates these fields.
        </p>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta title</label>
          <input v-model="metaForm.meta_title" type="text" class="form-control form-control-sm" placeholder="e.g. Compress PDF – Free Online PDF Compressor" maxlength="255" />
          <InputError :message="metaForm.errors.meta_title" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta description</label>
          <textarea v-model="metaForm.meta_description" class="form-control form-control-sm" rows="2" placeholder="Short description for search results" maxlength="500"></textarea>
          <InputError :message="metaForm.errors.meta_description" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta keywords</label>
          <input v-model="metaForm.meta_keywords" type="text" class="form-control form-control-sm" placeholder="keyword1, keyword2, keyword3" maxlength="2000" />
          <InputError :message="metaForm.errors.meta_keywords" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Focus keyword</label>
          <input v-model="metaForm.focus_keyword" type="text" class="form-control form-control-sm" placeholder="Primary keyword for this page" maxlength="255" />
          <InputError :message="metaForm.errors.focus_keyword" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Robots directive</label>
          <select v-model="metaForm.meta_robots" class="form-select form-select-sm" style="max-width: 22rem;">
            <option value="index,follow">index, follow (default)</option>
            <option value="index,nofollow">index, nofollow</option>
            <option value="noindex,follow">noindex, follow</option>
            <option value="noindex,nofollow">noindex, nofollow</option>
          </select>
          <InputError :message="metaForm.errors.meta_robots" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Canonical URL</label>
          <input v-model="metaForm.canonical_url" type="text" class="form-control form-control-sm" placeholder="https://compresspdf.id/ (leave blank to auto-set)" maxlength="500" />
          <InputError :message="metaForm.errors.canonical_url" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Frontend <code>&lt;head&gt;</code> snippet (GSC, gtag, GTM)</label>
          <textarea
            v-model="metaForm.frontend_head_snippet"
            class="form-control form-control-sm font-monospace"
            rows="6"
            spellcheck="false"
            placeholder="Paste verification meta or analytics scripts for the public site"
          ></textarea>
          <div class="form-text small text-muted">Same field as SEO → Analytics → Public site head HTML, and SEO → Home Page SEO.</div>
          <InputError :message="metaForm.errors.frontend_head_snippet" />
        </div>
        <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="metaForm.processing" @click="submitMeta">
          {{ metaForm.processing ? 'Saving…' : 'Save meta tags' }}
        </PrimaryButton>
      </div>

      <!-- Home page content -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Home page content</h2>
        <p class="text-muted small mb-3">Main body HTML shown below the compressor on the landing page.</p>
        <HomePageEditor v-model="contentForm.home_page_content" />
        <InputError :message="contentForm.errors.home_page_content" class="mt-2" />
        <div class="mt-3">
          <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="contentForm.processing" @click="submitContent">
            {{ contentForm.processing ? 'Saving…' : 'Save home page content' }}
          </PrimaryButton>
        </div>
      </div>

      <!-- Open Graph -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Open Graph (social preview)</h2>
        <p class="text-muted small mb-3">Title, description, and image when the home page is shared. Paste a URL from <strong>Media library</strong> if you uploaded there.</p>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Open Graph title</label>
          <input v-model="ogForm.og_title" type="text" class="form-control form-control-sm" placeholder="Defaults to meta title if left blank" maxlength="255" />
          <InputError :message="ogForm.errors.og_title" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Open Graph description</label>
          <textarea v-model="ogForm.og_description" class="form-control form-control-sm" rows="2" placeholder="Defaults to meta description" maxlength="500"></textarea>
          <InputError :message="ogForm.errors.og_description" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Open Graph image URL</label>
          <input v-model="ogForm.og_image" type="url" class="form-control form-control-sm" placeholder="https://… (optional)" />
          <InputError :message="ogForm.errors.og_image" />
        </div>
        <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="ogForm.processing" @click="submitOg">
          {{ ogForm.processing ? 'Saving…' : 'Save Open Graph' }}
        </PrimaryButton>
      </div>

      <p class="text-muted small mb-2">Manage other home page sections:</p>
      <div class="content-manager-home-links">
        <Link :href="route('content-manager.home', { tab: 'faq' })" class="content-manager-home-link admin-box admin-box-smooth">
          <span class="content-manager-home-link-icon" aria-hidden="true">❓</span>
          <div>
            <strong class="content-manager-home-link-title">FAQ</strong>
            <p class="content-manager-home-link-desc text-muted small mb-0">Frequently asked questions shown on the home page.</p>
          </div>
          <span class="content-manager-home-link-arrow" aria-hidden="true">→</span>
        </Link>
        <Link :href="route('content-manager.home', { tab: 'use-cards' })" class="content-manager-home-link admin-box admin-box-smooth">
          <span class="content-manager-home-link-icon" aria-hidden="true">🃏</span>
          <div>
            <strong class="content-manager-home-link-title">Use cards</strong>
            <p class="content-manager-home-link-desc text-muted small mb-0">Feature cards (e.g. “Why use our PDF compressor?”) on the home page.</p>
          </div>
          <span class="content-manager-home-link-arrow" aria-hidden="true">→</span>
        </Link>
        <Link :href="route('content-manager.sections')" class="content-manager-home-link admin-box admin-box-smooth">
          <span class="content-manager-home-link-icon" aria-hidden="true">📚</span>
          <div>
            <strong class="content-manager-home-link-title">Sections</strong>
            <p class="content-manager-home-link-desc text-muted small mb-0">Create unlimited dynamic sections with module lists (paragraphs/cards with icons, numbers, or images).</p>
          </div>
          <span class="content-manager-home-link-arrow" aria-hidden="true">→</span>
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.content-manager-home-links {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 36rem;
}
.content-manager-home-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  text-decoration: none;
  color: inherit;
  border-radius: 8px;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.content-manager-home-link:hover {
  border-color: var(--admin-primary, #4945ff);
  background: rgba(73, 69, 255, 0.04);
  box-shadow: 0 2px 8px rgba(73, 69, 255, 0.08);
}
.content-manager-home-link-icon {
  font-size: 1.75rem;
  line-height: 1;
  flex-shrink: 0;
}
.content-manager-home-link-title {
  display: block;
  margin-bottom: 0.25rem;
}
.content-manager-home-link-arrow {
  margin-left: auto;
  color: var(--admin-text-muted, #666687);
  font-size: 1.25rem;
}
.content-manager-home-link:hover .content-manager-home-link-arrow {
  color: var(--admin-primary, #4945ff);
}
</style>
