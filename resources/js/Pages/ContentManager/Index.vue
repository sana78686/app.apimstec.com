<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HomePageEditor from '@/Components/HomePageEditor.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
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
  flash: { type: Object, default: () => ({}) },
});

const form = useForm({
  home_page_content: props.homePageContent,
});

const seoForm = useForm({
  meta_title:       props.homeMetaTitle,
  meta_description: props.homeMetaDescription,
  meta_keywords:    props.homeMetaKeywords,
  focus_keyword:    props.homeFocusKeyword,
  og_title:         props.homeOgTitle,
  og_description:   props.homeOgDescription,
  og_image:         props.homeOgImage,
  meta_robots:      props.homeMetaRobots || 'index,follow',
  canonical_url:    props.homeCanonicalUrl,
});

watch(() => props.homePageContent, (val) => {
  form.home_page_content = val ?? '';
});
watch(() => props.homeMetaTitle, (val) => { seoForm.meta_title = val ?? ''; });
watch(() => props.homeMetaDescription, (val) => { seoForm.meta_description = val ?? ''; });
watch(() => props.homeMetaKeywords, (val) => { seoForm.meta_keywords = val ?? ''; });
watch(() => props.homeFocusKeyword, (val) => { seoForm.focus_keyword = val ?? ''; });
watch(() => props.homeOgTitle, (val) => { seoForm.og_title = val ?? ''; });
watch(() => props.homeOgDescription, (val) => { seoForm.og_description = val ?? ''; });
watch(() => props.homeOgImage, (val) => { seoForm.og_image = val ?? ''; });
watch(() => props.homeMetaRobots, (val) => { seoForm.meta_robots = val ?? 'index,follow'; });
watch(() => props.homeCanonicalUrl, (val) => { seoForm.canonical_url = val ?? ''; });

function submit() {
  form.clearErrors();
  form.put(route('content-manager.update'), { preserveScroll: true });
}
function submitSeo() {
  seoForm.clearErrors();
  seoForm.put(route('content-manager.home-seo.update'), { preserveScroll: true });
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
        <label class="form-label small fw-semibold">Home page content</label>
        <HomePageEditor v-model="form.home_page_content" />
        <InputError :message="form.errors.home_page_content" class="mt-2" />
        <div class="mt-3">
          <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
            Save home page
          </PrimaryButton>
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-3">Meta tags &amp; SEO (home page only)</h2>
        <p class="text-muted small mb-3">These meta tags and Open Graph fields are used only for the frontend home page (landing). Leave blank to use defaults.</p>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta title</label>
          <input v-model="seoForm.meta_title" type="text" class="form-control form-control-sm" placeholder="e.g. Compress PDF – Free Online PDF Compressor" maxlength="255" />
          <InputError :message="seoForm.errors.meta_title" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta description</label>
          <textarea v-model="seoForm.meta_description" class="form-control form-control-sm" rows="2" placeholder="Short description for search results" maxlength="500"></textarea>
          <InputError :message="seoForm.errors.meta_description" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Meta keywords</label>
          <input v-model="seoForm.meta_keywords" type="text" class="form-control form-control-sm" placeholder="keyword1, keyword2, keyword3" maxlength="255" />
          <InputError :message="seoForm.errors.meta_keywords" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Focus keyword</label>
          <input v-model="seoForm.focus_keyword" type="text" class="form-control form-control-sm" placeholder="Primary keyword for this page" maxlength="255" />
          <InputError :message="seoForm.errors.focus_keyword" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Open Graph title</label>
          <input v-model="seoForm.og_title" type="text" class="form-control form-control-sm" placeholder="Defaults to meta title" maxlength="255" />
          <InputError :message="seoForm.errors.og_title" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Open Graph description</label>
          <textarea v-model="seoForm.og_description" class="form-control form-control-sm" rows="2" placeholder="Defaults to meta description" maxlength="500"></textarea>
          <InputError :message="seoForm.errors.og_description" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Open Graph image URL</label>
          <input v-model="seoForm.og_image" type="url" class="form-control form-control-sm" placeholder="https://… (optional)" />
          <InputError :message="seoForm.errors.og_image" />
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Robots directive</label>
          <select v-model="seoForm.meta_robots" class="form-select form-select-sm" style="max-width: 22rem;">
            <option value="index,follow">index, follow (default)</option>
            <option value="index,nofollow">index, nofollow</option>
            <option value="noindex,follow">noindex, follow</option>
            <option value="noindex,nofollow">noindex, nofollow</option>
          </select>
          <InputError :message="seoForm.errors.meta_robots" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Canonical URL</label>
          <input v-model="seoForm.canonical_url" type="text" class="form-control form-control-sm" placeholder="https://compresspdf.id/ (leave blank to auto-set)" maxlength="500" />
          <InputError :message="seoForm.errors.canonical_url" />
        </div>
        <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="seoForm.processing" @click="submitSeo">
          Save meta tags &amp; SEO
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
