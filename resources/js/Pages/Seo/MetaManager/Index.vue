<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  pages: { type: Array, default: () => [] },
});

const successMessage = ref('');
const searchQuery = ref('');

const page = usePage();
if (page.props.flash?.success === 'meta-saved') {
  successMessage.value = 'Meta tags saved.';
} else {
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === 'meta-saved') successMessage.value = 'Meta tags saved.';
}

const filteredPages = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return props.pages;
  return props.pages.filter(
    (p) =>
      (p.title || '').toLowerCase().includes(q) ||
      (p.slug || '').toLowerCase().includes(q)
  );
});

const perPage     = 20;
const currentPage = ref(1);
const pagedPages  = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredPages.value.slice(start, start + perPage);
});
watch(searchQuery, () => { currentPage.value = 1; });

function statusTitle(seo_status) {
  if (seo_status === 'ok') return 'SEO requirements met';
  if (seo_status === 'warning') return 'Partially meets SEO requirements';
  return 'Does not meet SEO requirements';
}
</script>

<template>
  <Head title="Meta Manager" />

  <AuthenticatedLayout>
    <template #header>Meta Manager</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Meta Manager</h1>
          <p class="admin-list-page-desc">Pages and their meta tag status. Set or edit meta title, description, canonical and robots per page.</p>
        </div>
        <Link :href="route('seo.meta-manager.create')" class="admin-list-page-cta">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="12" y1="18" x2="12" y2="12" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          Create meta tag
        </Link>
      </div>

      <div class="admin-list-toolbar">
        <div class="admin-list-search-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          <input
            v-model="searchQuery"
            type="search"
            class="admin-list-search"
            placeholder="Search pages..."
            aria-label="Search pages"
          />
        </div>
      </div>

      <div class="admin-list-table-wrap">
        <table class="admin-list-table" role="grid">
          <thead>
            <tr>
              <th style="width: 2.5rem;"></th>
              <th>Title</th>
              <th>Slug</th>
              <th>Meta</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pagedPages" :key="p.id">
              <td class="align-middle">
                <span
                  v-if="p.seo_status"
                  class="admin-seo-status-dot"
                  :class="'admin-seo-status-dot--' + (p.seo_status || 'warning')"
                  :title="statusTitle(p.seo_status)"
                  aria-label="SEO status"
                ></span>
              </td>
              <td>{{ p.title }}</td>
              <td><code class="admin-list-code">{{ p.slug }}</code></td>
              <td>
                <span v-if="p.meta_done" class="admin-list-badge">Done</span>
                <span v-else class="text-muted small">Not set</span>
              </td>
              <td>
                <Link
                  v-if="p.meta_done"
                  :href="route('seo.meta-manager.create', { page_id: p.id })"
                  class="admin-list-link"
                >
                  Edit
                </Link>
                <Link
                  v-else
                  :href="route('seo.meta-manager.create', { page_id: p.id })"
                  class="admin-list-link"
                >
                  Set meta
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
        <Pagination
          :total="filteredPages.length"
          :per-page="perPage"
          :current-page="currentPage"
          @update:current-page="currentPage = $event"
        />
        <p v-if="!pages.length" class="admin-text-muted" style="padding: 1.5rem;">No pages have meta tags set yet. Use <strong>Create meta tag</strong> to select a page and set its meta title, description, canonical and robots.</p>
        <p v-else-if="!filteredPages.length" class="admin-text-muted" style="padding: 1.5rem;">No pages match your search.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
