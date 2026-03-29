<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  schemaTypeOptions: { type: Object, default: () => ({}) },
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

async function changeSchemaType(item, schemaType) {
  if (item.schema_type === schemaType) return;
  savingId.value = item.type + '-' + item.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.put('/api/seo/schema-markup/update-schema', {
      type: item.type,
      id: item.id,
      schema_type: schemaType || '',
      schema_data: item.schema_data || null,
    });
    item.schema_type = data.schema_type;
    item.schema_data = data.schema_data;
    successMessage.value = data.message || 'Schema updated.';
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to update schema.';
  } finally {
    savingId.value = null;
  }
}

function isSaving(item) {
  return savingId.value === item.type + '-' + item.id;
}

function schemaLabel(value) {
  return (props.schemaTypeOptions[value] ?? value) || 'None';
}
</script>

<template>
  <Head title="Schema Markup Manager" />

  <AuthenticatedLayout>
    <template #header>Schema Markup Manager</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error">{{ errorMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Schema Markup Manager</h1>
          <p class="admin-list-page-desc">
            Add <strong>structured data</strong> (JSON-LD) to improve how your pages appear in search. Choose <strong>Article</strong>, <strong>FAQ</strong>, <strong>Product</strong>, or <strong>Breadcrumb</strong> to enable rich results in Google and other search engines.
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
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Structured data by content</h2>
        <p class="text-muted small mb-3">Set the schema type for each page or blog post. Your frontend can output this as JSON-LD in the page head for rich results (e.g. FAQ snippets, article cards, breadcrumb trail).</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Type</th>
                <th class="admin-url-table-th">Title</th>
                <th class="admin-url-table-th">URL path</th>
                <th class="admin-url-table-th">Schema</th>
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
                    :value="item.schema_type || ''"
                    class="form-select form-select-sm"
                    :disabled="isSaving(item)"
                    :title="'Set schema for “‘ + item.title + ‘”'"
                    @change="changeSchemaType(item, $event.target.value)"
                  >
                    <option
                      v-for="(label, value) in schemaTypeOptions"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </option>
                  </select>
                  <span v-if="isSaving(item)" class="ms-1 text-muted small">Saving…</span>
                  <span v-else-if="item.schema_type" class="ms-1 admin-list-badge admin-list-badge-success">
                    {{ schemaLabel(item.schema_type) }}
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
