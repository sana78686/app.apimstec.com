<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const page = usePage();
const successMessage = ref(page.props.flash?.success || '');

const filteredItems = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return props.items;
  return props.items.filter(
    (i) =>
      (i.title || '').toLowerCase().includes(q) ||
      (i.slug || '').toLowerCase().includes(q)
  );
});

function editOgUrl(item) {
  if (item.type === 'page') {
    return route('seo.meta-manager.create', { page_id: item.id });
  }
  return route('seo.social-sharing.edit') + '?type=' + encodeURIComponent(item.type) + '&id=' + item.id;
}
</script>

<template>
  <Head title="Social Sharing (Open Graph)" />

  <AuthenticatedLayout>
    <template #header>Social Sharing (Open Graph)</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Social Sharing (Open Graph)</h1>
          <p class="admin-list-page-desc">
            Set <strong>OG title</strong>, <strong>description</strong>, and <strong>image</strong> so links look great when shared on <strong>Facebook</strong>, <strong>X (Twitter)</strong>, and <strong>LinkedIn</strong>. These tags control the preview card.
          </p>
        </div>
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
            placeholder="Search by title or slug..."
            aria-label="Search content"
          />
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Open Graph by content</h2>
        <p class="text-muted small mb-3">Edit OG fields per page or blog post. Use an image around 1200×630px for best results on Facebook and LinkedIn.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Type</th>
                <th class="admin-url-table-th">Title</th>
                <th class="admin-url-table-th">URL</th>
                <th class="admin-url-table-th">OG preview</th>
                <th class="admin-url-table-th">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.type + '-' + item.id">
                <td class="admin-url-table-type">
                  <span class="admin-list-badge">{{ item.type === 'blog' ? 'Blog' : 'Page' }}</span>
                </td>
                <td class="admin-url-table-title">{{ item.title }}</td>
                <td class="admin-url-table-path">
                  <code class="admin-list-code admin-url-path-code">/{{ item.url_path }}</code>
                </td>
                <td>
                  <div v-if="item.has_og" class="d-flex align-items-center gap-2 flex-wrap">
                    <img
                      v-if="item.og_image_absolute"
                      :src="item.og_image_absolute"
                      alt=""
                      class="rounded"
                      style="width: 60px; height: 60px; object-fit: cover;"
                      @error="($event.target).style.display = 'none'"
                    />
                    <div class="small">
                      <span v-if="item.og_title" class="d-block text-truncate" style="max-width: 200px;" :title="item.og_title">{{ item.og_title }}</span>
                      <span v-else class="text-muted">No OG title</span>
                      <span v-if="item.og_description" class="d-block text-muted text-truncate" style="max-width: 200px;" :title="item.og_description">{{ item.og_description }}</span>
                    </div>
                  </div>
                  <span v-else class="admin-list-badge">Not set</span>
                </td>
                <td>
                  <Link :href="editOgUrl(item)" class="btn btn-sm btn-outline-primary admin-btn-focus">{{ item.type === 'page' ? 'Edit SEO & OG' : 'Edit OG' }}</Link>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!items.length" class="admin-text-muted p-3 mb-0">No pages or blog posts yet. Create content in Content manager first.</p>
          <p v-else-if="!filteredItems.length" class="admin-text-muted p-3 mb-0">No content matches your search.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
