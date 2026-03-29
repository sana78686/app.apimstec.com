<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  visibilityOptions: { type: Object, default: () => ({}) },
});

const searchQuery = ref('');
const savingId = ref(null);
const successMessage = ref('');
const errorMessage = ref('');

const filteredItems = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return props.items;
  return props.items.filter(
    (i) =>
      (i.title || '').toLowerCase().includes(q) ||
      (i.slug || '').toLowerCase().includes(q)
  );
});

function urlPath(item) {
  return item.type === 'blog' ? 'blog/' + item.slug : item.slug;
}

async function changeVisibility(item, visibility) {
  if (item.visibility === visibility) return;
  savingId.value = item.type + '-' + item.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.put('/api/seo/indexing/update-visibility', {
      type: item.type,
      id: item.id,
      visibility,
    });
    item.visibility = data.visibility;
    item.meta_robots = data.meta_robots;
    item.is_published = data.is_published;
    item.indexing_summary = data.indexing_summary;
    successMessage.value = data.message || 'Visibility updated.';
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to update visibility.';
  } finally {
    savingId.value = null;
  }
}

function isSaving(item) {
  return savingId.value === item.type + '-' + item.id;
}

function summaryBadgeClass(summary) {
  if (summary === 'visible & indexed') return 'admin-list-badge admin-list-badge-success';
  if (summary === 'disabled')          return 'admin-list-badge admin-list-badge-warning';
  return 'admin-list-badge'; // draft (noindex)
}
</script>

<template>
  <Head title="Indexing Controls" />

  <AuthenticatedLayout>
    <template #header>Indexing Controls</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error">{{ errorMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Indexing Controls</h1>
          <p class="admin-list-page-desc">
            Control how search engines see your content. <strong>Draft</strong> → noindex; <strong>Private</strong> → hidden; <strong>Published</strong> → index allowed. Changing visibility updates meta robots and publish state.
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

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Content visibility & indexing</h2>
          <p class="text-muted small mb-3">Set the status per page or blog. <strong>Draft</strong> = noindex (working copy); <strong>Visible</strong> = live on frontend, indexed; <strong>Disabled</strong> = hidden from frontend, noindex.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Type</th>
                <th class="admin-url-table-th">Title</th>
                <th class="admin-url-table-th">URL path</th>
                <th class="admin-url-table-th">Visibility</th>
                <th class="admin-url-table-th">Indexing</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.type + '-' + item.id">
                <td class="admin-url-table-type">
                  <span class="admin-list-badge">{{ item.type === 'page' ? 'Page' : 'Blog' }}</span>
                </td>
                <td class="admin-url-table-title">{{ item.title }}</td>
                <td class="admin-url-table-path">
                  <span class="admin-url-path" :title="'URL path: /' + urlPath(item)">
                    <code class="admin-list-code admin-url-path-code">/{{ urlPath(item) }}</code>
                  </span>
                </td>
                <td class="admin-url-table-visibility">
                  <select
                    :value="item.visibility"
                    class="form-select form-select-sm"
                    :disabled="isSaving(item)"
                    :title="'Set visibility for “‘ + item.title + ‘”'"
                    @change="changeVisibility(item, $event.target.value)"
                  >
                    <option
                      v-for="(label, value) in visibilityOptions"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </option>
                  </select>
                  <span v-if="isSaving(item)" class="ms-1 text-muted small">Saving…</span>
                </td>
                <td>
                  <span :class="summaryBadgeClass(item.indexing_summary)" :title="'meta: ' + (item.meta_robots || '')">
                    {{ item.indexing_summary }}
                  </span>
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
