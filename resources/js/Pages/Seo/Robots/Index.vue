<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  content: { type: String, default: '' },
  robotsUrl: { type: String, default: '' },
  sitemapUrl: { type: String, default: '' },
  robotsUrlOnCmsHost: { type: String, default: '' },
  sitemapUrlOnCmsHost: { type: String, default: '' },
});

const form = useForm({
  content: props.content,
});

const page = usePage();
const successMessage = ref(page.props.flash?.success || '');

function openRobots() {
  if (props.robotsUrl) window.open(props.robotsUrl, '_blank');
}
</script>

<template>
  <Head title="Robots.txt Manager" />

  <AuthenticatedLayout>
    <template #header>Robots.txt Manager</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Robots.txt Manager</h1>
          <p class="admin-list-page-desc">
            Control how search engine crawlers (Google, Bing, etc.) access your site. Use <strong>Allow</strong> and <strong>Disallow</strong> to open or block sections. The sitemap link is added automatically if missing, so bots can discover your pages.
          </p>
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Live URLs</h2>
        <p class="text-muted small mb-3">Crawlers read your robots.txt at the URL below. Submit the sitemap in Google Search Console.</p>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <code class="admin-list-code admin-url-path-code p-2" style="min-width: 200px;">{{ robotsUrl }}</code>
          <button
            type="button"
            class="btn btn-outline-primary btn-sm admin-btn-focus"
            @click="openRobots"
          >
            Open robots.txt
          </button>
        </div>
          <p class="text-muted small mb-0">Sitemap line should point to: <code class="admin-list-code">{{ sitemapUrl }}</code></p>
        <p v-if="robotsUrlOnCmsHost" class="text-muted small mb-0 mt-2">
          Alternate (CMS host, if live domain does not proxy here):<br />
          <code class="admin-list-code">{{ robotsUrlOnCmsHost }}</code>
          · Sitemap: <code class="admin-list-code">{{ sitemapUrlOnCmsHost }}</code>
        </p>
      </div>

      <p class="text-muted small border rounded p-3 mb-3 bg-light">
        Crawlers must receive this file from your <strong>live site</strong> URL above. If a static <code class="admin-list-code">robots.txt</code> file exists on the server or CDN, remove it so requests reach Laravel and your Allow/Disallow rules apply.
      </p>

      <form class="admin-box admin-box-smooth" @submit.prevent="form.put(route('seo.robots.update'))">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Robots.txt content</h2>
        <p class="text-muted small mb-3">
          Use <strong>User-agent: *</strong> for all bots. <strong>Allow: /</strong> to allow a path; <strong>Disallow: /path</strong> to block it. Add <strong>Sitemap: {{ sitemapUrl }}</strong> (it will be added automatically if you leave it out). One directive per line; blank lines separate groups.
        </p>
        <div class="mb-3">
          <textarea
            v-model="form.content"
            class="form-control font-monospace"
            rows="14"
            placeholder="User-agent: *&#10;Allow: /&#10;&#10;Sitemap: https://yoursite.com/sitemap.xml"
            spellcheck="false"
            style="font-size: 0.875rem;"
          />
          <InputError v-if="form.errors.content" :message="form.errors.content" class="mt-1" />
        </div>
        <div class="d-flex align-items-center gap-2">
          <PrimaryButton :disabled="form.processing">Save robots.txt</PrimaryButton>
          <span v-if="form.processing" class="text-muted small">Saving…</span>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
