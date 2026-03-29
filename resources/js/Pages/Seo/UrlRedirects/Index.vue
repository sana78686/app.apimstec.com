<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  urls: { type: Array, default: () => [] },
  redirects: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const editing = ref(null);
const editSlug = ref('');
const editError = ref('');
const saving = ref(false);
const successMessage = ref('');

const filteredUrls = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return props.urls;
  return props.urls.filter(
    (u) =>
      (u.title || '').toLowerCase().includes(q) ||
      (u.slug || '').toLowerCase().includes(q) ||
      (u.url_path || '').toLowerCase().includes(q)
  );
});

function startEdit(item) {
  editing.value = item.type + '-' + item.id;
  editSlug.value = item.slug;
  editError.value = '';
}

function cancelEdit() {
  editing.value = null;
  editSlug.value = '';
  editError.value = '';
}

function slugFromTitle(title) {
  if (!title) return;
  window.axios.get('/api/seo/url-redirects/generate-slug', { params: { title } }).then(({ data }) => {
    editSlug.value = data.slug || '';
  });
}

function isEditing(item) {
  return editing.value === item.type + '-' + item.id;
}

async function saveSlug(item) {
  const slug = (editSlug.value || '').trim().toLowerCase().replace(/\s+/g, '-');
  if (!slug) {
    editError.value = 'Slug is required.';
    return;
  }
  if (slug === item.slug) {
    cancelEdit();
    return;
  }
  saving.value = true;
  editError.value = '';
  try {
    const { data } = await window.axios.put('/api/seo/url-redirects/update-slug', {
      type: item.type,
      id: item.id,
      slug,
    });
    successMessage.value = data.message || 'Slug updated.';
    item.slug = data.slug;
    item.url_path = item.type === 'blog' ? 'blog/' + data.slug : data.slug;
    cancelEdit();
  } catch (e) {
    editError.value = e.response?.data?.message || 'Failed to update slug.';
    if (e.response?.status === 422 && e.response?.data?.errors?.slug) {
      editError.value = e.response.data.errors.slug[0] || editError.value;
    }
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <Head title="URL & Redirect Manager" />

  <AuthenticatedLayout>
    <template #header>URL & Redirect Manager</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">URL & Redirect Manager</h1>
          <p class="admin-list-page-desc">
            Manage SEO-friendly URLs (slugs) for pages and blog posts. Edit a slug to create a 301 redirect from the old URL to the new one. Duplicate slugs are prevented.
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
            aria-label="Search URLs"
          />
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Content URLs</h2>
        <p class="text-muted small mb-3">Click <strong>Edit slug</strong> to change the URL. Use <strong>Generate from title</strong> for an SEO-friendly slug. If you change the slug, a 301 redirect is created automatically.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Type</th>
                <th class="admin-url-table-th">Title</th>
                <th class="admin-url-table-th">Slug / URL path</th>
                <th class="admin-url-table-th">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in filteredUrls" :key="u.type + '-' + u.id">
                <td class="admin-url-table-type">
                  <span class="admin-list-badge">{{ u.type === 'page' ? 'Page' : 'Blog' }}</span>
                </td>
                <td class="admin-url-table-title">{{ u.title }}</td>
                <td class="admin-url-table-path">
                  <template v-if="isEditing(u)">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <TextInput
                        v-model="editSlug"
                        class="form-control form-control-sm"
                        style="max-width: 14rem;"
                        placeholder="e.g. about-us"
                        :title="'Enter the URL slug. Use lowercase letters, numbers and hyphens only.'"
                        @keydown.enter="saveSlug(u)"
                      />
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm admin-btn-tooltip"
                        title="Generate an SEO-friendly slug from the title (lowercase, hyphens)"
                        @click="slugFromTitle(u.title)"
                      >
                        Generate from title
                      </button>
                    </div>
                    <InputError v-if="editError" :message="editError" class="mt-1" />
                  </template>
                  <template v-else>
                    <span class="admin-url-path" :title="'URL path: /' + u.url_path + ' — Click Edit slug to change.'">
                      <code class="admin-list-code admin-url-path-code">/{{ u.url_path }}</code>
                    </span>
                  </template>
                </td>
                <td class="admin-url-table-actions">
                  <template v-if="isEditing(u)">
                    <button
                      type="button"
                      class="btn btn-primary btn-sm me-1 admin-btn-focus"
                      :disabled="saving"
                      title="Save the new slug. A 301 redirect will be created from the old URL."
                      @click="saveSlug(u)"
                    >
                      Save
                    </button>
                    <button
                      type="button"
                      class="btn btn-secondary btn-sm admin-btn-focus"
                      :disabled="saving"
                      title="Cancel and keep the current slug"
                      @click="cancelEdit"
                    >
                      Cancel
                    </button>
                  </template>
                  <button
                    v-else
                    type="button"
                    class="btn btn-sm admin-url-edit-btn admin-btn-focus"
                    :title="'Change the URL slug for “‘ + u.title + ‘”. A 301 redirect will be created from the old URL.'"
                    @click="startEdit(u)"
                  >
                    Edit slug
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!urls.length" class="admin-text-muted p-3 mb-0">No pages or blog posts yet. Create content in Content manager first.</p>
          <p v-else-if="!filteredUrls.length" class="admin-text-muted p-3 mb-0">No URLs match your search.</p>
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Active redirects (301)</h2>
        <p class="text-muted small mb-3">When a slug is changed, a redirect is added here so old URLs point to the new one.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th>From path</th>
                <th>To path</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in redirects" :key="r.id">
                <td><code class="admin-list-code admin-url-path-code" :title="'Old URL path: /' + r.from_path">/{{ r.from_path }}</code></td>
                <td><code class="admin-list-code admin-url-path-code" :title="'New URL path: /' + r.to_path">/{{ r.to_path }}</code></td>
                <td><span class="admin-list-badge" :title="'HTTP ' + r.status_code + ' permanent redirect'">{{ r.status_code }}</span></td>
              </tr>
            </tbody>
          </table>
          <p v-if="!redirects.length" class="admin-text-muted p-3 mb-0">No redirects yet. Edit a slug above to create one.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
