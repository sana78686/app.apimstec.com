<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  sitemapUrl: { type: String, default: '' },
  sitemapUrlOnCmsHost: { type: String, default: '' },
  urls: { type: Array, default: () => [] },
  count: { type: Number, default: 0 },
  domainNote: { type: String, default: null },
});

const searchQuery = ref('');
const copied = ref(false);

const filteredUrls = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return props.urls;
  return props.urls.filter(
    (u) =>
      (u.title || '').toLowerCase().includes(q) ||
      (u.path || '').toLowerCase().includes(q) ||
      (u.url || '').toLowerCase().includes(q)
  );
});

function copySitemapUrl() {
  if (!props.sitemapUrl) return;
  navigator.clipboard.writeText(props.sitemapUrl).then(() => {
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  });
}

function openSitemap() {
  if (props.sitemapUrl) window.open(props.sitemapUrl, '_blank');
}
</script>

<template>
  <Head title="Sitemap Manager" />

  <AuthenticatedLayout>
    <template #header>Sitemap Manager</template>

    <div class="admin-list-page">
      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Sitemap Manager</h1>
          <p class="admin-list-page-desc">
            Your XML sitemap updates automatically: it includes only <strong>published</strong> pages and blog posts. When you publish, unpublish, or delete content, the sitemap stays fresh for Google—no manual refresh needed.
          </p>
        </div>
      </div>

      <p v-if="domainNote" class="text-muted small border rounded p-3 mb-3 bg-light">{{ domainNote }}</p>

      <div v-if="sitemapUrl" class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Sitemap URL (live site)</h2>
        <p class="text-muted small mb-3">Use this URL in Google Search Console for your public domain. Paths match your React app (<code class="admin-list-code">/{lang}/page/…</code>, <code class="admin-list-code">/{lang}/blog/…</code>). Only <strong>published</strong> and <strong>visible</strong> pages and posts are included.</p>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <code class="admin-list-code admin-url-path-code flex-grow-1 p-2" style="min-width: 200px;">{{ sitemapUrl }}</code>
          <button
            type="button"
            class="btn btn-outline-primary btn-sm admin-btn-focus"
            :title="'Copy ' + sitemapUrl"
            @click="copySitemapUrl"
          >
            {{ copied ? 'Copied!' : 'Copy URL' }}
          </button>
          <button
            type="button"
            class="btn btn-primary btn-sm admin-btn-focus"
            title="Open sitemap in new tab"
            @click="openSitemap"
          >
            Open sitemap
          </button>
        </div>
        <p v-if="sitemapUrlOnCmsHost" class="text-muted small mb-0 mt-3">
          If <code class="admin-list-code">/sitemap.xml</code> on the live host does not reach this CMS, submit this same sitemap from the CMS server:
          <code class="admin-list-code d-block mt-1">{{ sitemapUrlOnCmsHost }}</code>
        </p>
      </div>

      <div class="admin-list-toolbar mb-3">
        <div class="admin-list-search-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          <input
            v-model="searchQuery"
            type="search"
            class="admin-list-search"
            placeholder="Search URLs..."
            aria-label="Search sitemap URLs"
          />
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">URLs in sitemap ({{ count }} total)</h2>
        <p class="text-muted small mb-3">Only published pages and blog posts appear here. Draft and private content are excluded. Deleted items are removed automatically.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Type</th>
                <th class="admin-url-table-th">Title</th>
                <th class="admin-url-table-th">URL</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(u, i) in filteredUrls" :key="u.type + '-' + (u.id ?? i)">
                <td class="admin-url-table-type">
                  <span class="admin-list-badge">{{ u.type === 'blog' ? 'Blog' : u.type === 'home' ? 'Home' : 'Page' }}</span>
                </td>
                <td class="admin-url-table-title">{{ u.title }}</td>
                <td class="admin-url-table-path">
                  <a :href="u.url" target="_blank" rel="noopener noreferrer" class="admin-list-code admin-url-path-code">{{ u.url }}</a>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!urls.length" class="admin-text-muted p-3 mb-0">No published content yet. Publish pages or blog posts in Content manager—they will appear here and in the sitemap automatically.</p>
          <p v-else-if="!filteredUrls.length" class="admin-text-muted p-3 mb-0">No URLs match your search.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
