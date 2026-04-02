<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  metaTitle:       { type: String, default: '' },
  metaDescription: { type: String, default: '' },
  metaKeywords:    { type: String, default: '' },
  focusKeyword:    { type: String, default: '' },
  ogTitle:         { type: String, default: '' },
  ogDescription:   { type: String, default: '' },
  ogImage:         { type: String, default: '' },
  metaRobots:      { type: String, default: 'index,follow' },
  canonicalUrl:    { type: String, default: '' },
  frontendHeadSnippet: { type: String, default: '' },
  flash:           { type: Object, default: () => ({}) },
});

const form = useForm({
  meta_title:       props.metaTitle,
  meta_description: props.metaDescription,
  meta_keywords:    props.metaKeywords,
  focus_keyword:    props.focusKeyword,
  og_title:         props.ogTitle,
  og_description:   props.ogDescription,
  og_image:         props.ogImage,
  meta_robots:      props.metaRobots || 'index,follow',
  canonical_url:    props.canonicalUrl,
  frontend_head_snippet: props.frontendHeadSnippet,
});

watch(() => props.metaTitle,       (v) => { form.meta_title       = v ?? ''; });
watch(() => props.metaDescription, (v) => { form.meta_description = v ?? ''; });
watch(() => props.metaKeywords,    (v) => { form.meta_keywords    = v ?? ''; });
watch(() => props.focusKeyword,    (v) => { form.focus_keyword    = v ?? ''; });
watch(() => props.ogTitle,         (v) => { form.og_title         = v ?? ''; });
watch(() => props.ogDescription,   (v) => { form.og_description   = v ?? ''; });
watch(() => props.ogImage,         (v) => { form.og_image         = v ?? ''; });
watch(() => props.metaRobots,      (v) => { form.meta_robots      = v ?? 'index,follow'; });
watch(() => props.canonicalUrl,    (v) => { form.canonical_url    = v ?? ''; });
watch(() => props.frontendHeadSnippet, (v) => { form.frontend_head_snippet = v ?? ''; });

function submit() {
  form.clearErrors();
  form.put(route('content-manager.home-seo.update'), { preserveScroll: true });
}

const titleLen = () => form.meta_title.length;
const descLen  = () => form.meta_description.length;
</script>

<template>
  <Head title="Home Page SEO" />

  <AuthenticatedLayout>
    <template #header>Home Page SEO</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Home Page SEO</h1>
        <p class="admin-form-page-desc text-muted small">
          Meta tags, Open Graph, robots directive and canonical URL for the frontend home page (<code>/</code>).
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <!-- ── Crawl & Indexing ─────────────────────────────────── -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Crawl &amp; Indexing</h2>
        <p class="text-muted small mb-3">Control how search engines crawl and index the home page.</p>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Robots directive</label>
          <select v-model="form.meta_robots" class="form-select form-select-sm" style="max-width: 22rem;">
            <option value="index,follow">index, follow (default — recommended)</option>
            <option value="index,nofollow">index, nofollow</option>
            <option value="noindex,follow">noindex, follow</option>
            <option value="noindex,nofollow">noindex, nofollow</option>
          </select>
          <InputError :message="form.errors.meta_robots" />
        </div>

        <div class="mb-0">
          <label class="form-label small fw-semibold">Canonical URL</label>
          <input
            v-model="form.canonical_url"
            type="text"
            class="form-control form-control-sm"
            placeholder="https://compresspdf.id/ (leave blank to use current URL)"
            maxlength="500"
          />
          <div class="form-text">Only set if this page has a duplicate at another URL.</div>
          <InputError :message="form.errors.canonical_url" />
        </div>
      </div>

      <!-- Frontend head snippet (GSC, Analytics) -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Frontend &lt;head&gt; snippet</h2>
        <p class="text-muted small mb-3">
          Injected into the public React app’s <code>&lt;head&gt;</code> on every page load. Use for
          Google Search Console verification (<code>meta name="google-site-verification"</code>),
          Google Tag Manager, or gtag. Paste complete tags as provided by Google (one or more lines).
        </p>
        <div class="mb-0">
          <label class="form-label small fw-semibold">HTML / scripts for <code>&lt;head&gt;</code></label>
          <textarea
            v-model="form.frontend_head_snippet"
            class="form-control form-control-sm font-monospace"
            rows="8"
            spellcheck="false"
            placeholder='&lt;meta name="google-site-verification" content="…" /&gt;'
          ></textarea>
          <div class="form-text">Third-party scripts can affect Lighthouse Performance scores.</div>
          <InputError :message="form.errors.frontend_head_snippet" />
        </div>
      </div>

      <!-- ── Meta Tags ────────────────────────────────────────── -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Meta Tags</h2>
        <p class="text-muted small mb-3">Shown in search engine result pages (SERPs).</p>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Meta title</label>
          <input
            v-model="form.meta_title"
            type="text"
            class="form-control form-control-sm"
            placeholder="e.g. Compress PDF – Free Online PDF Compressor"
            maxlength="60"
          />
          <div class="form-text d-flex justify-content-between">
            <span>Recommended: 50–60 characters</span>
            <span :class="titleLen() > 60 ? 'text-danger' : 'text-muted'">{{ titleLen() }}/60</span>
          </div>
          <InputError :message="form.errors.meta_title" />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Meta description</label>
          <textarea
            v-model="form.meta_description"
            class="form-control form-control-sm"
            rows="3"
            placeholder="Short summary for search result snippets (recommended: 120–160 characters)"
            maxlength="160"
          ></textarea>
          <div class="form-text d-flex justify-content-between">
            <span>Recommended: 120–160 characters</span>
            <span :class="descLen() > 160 ? 'text-danger' : 'text-muted'">{{ descLen() }}/160</span>
          </div>
          <InputError :message="form.errors.meta_description" />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Meta keywords</label>
          <input
            v-model="form.meta_keywords"
            type="text"
            class="form-control form-control-sm"
            placeholder="keyword1, keyword2, keyword3"
            maxlength="255"
          />
          <InputError :message="form.errors.meta_keywords" />
        </div>

        <div class="mb-0">
          <label class="form-label small fw-semibold">Focus keyword</label>
          <input
            v-model="form.focus_keyword"
            type="text"
            class="form-control form-control-sm"
            placeholder="Primary keyword for this page"
            maxlength="255"
          />
          <InputError :message="form.errors.focus_keyword" />
        </div>
      </div>

      <!-- ── Open Graph ───────────────────────────────────────── -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-1">Open Graph (Social Sharing)</h2>
        <p class="text-muted small mb-3">Controls how the page appears when shared on Facebook, Twitter/X, LinkedIn, etc.</p>

        <div class="mb-3">
          <label class="form-label small fw-semibold">OG title</label>
          <input
            v-model="form.og_title"
            type="text"
            class="form-control form-control-sm"
            placeholder="Defaults to meta title if left blank"
            maxlength="255"
          />
          <InputError :message="form.errors.og_title" />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">OG description</label>
          <textarea
            v-model="form.og_description"
            class="form-control form-control-sm"
            rows="2"
            placeholder="Defaults to meta description if left blank"
            maxlength="500"
          ></textarea>
          <InputError :message="form.errors.og_description" />
        </div>

        <div class="mb-0">
          <label class="form-label small fw-semibold">OG image URL</label>
          <input
            v-model="form.og_image"
            type="url"
            class="form-control form-control-sm"
            placeholder="https://… (recommended: 1200×630 px)"
          />
          <div class="form-text">Leave blank to use the site default logo.</div>
          <InputError :message="form.errors.og_image" />
        </div>
      </div>

      <PrimaryButton
        type="button"
        class="btn btn-primary btn-sm"
        :disabled="form.processing"
        @click="submit"
      >
        {{ form.processing ? 'Saving…' : 'Save home page SEO' }}
      </PrimaryButton>
    </div>
  </AuthenticatedLayout>
</template>
