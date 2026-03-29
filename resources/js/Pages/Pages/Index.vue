<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const pages = ref([]);
const loading = ref(true);
const successMessage = ref('');
const searchQuery = ref('');

/** Flatten tree (parent + children) into rows for table; each row has _level (0 = parent, 1 = child). */
function flattenPages(tree) {
  const out = [];
  function walk(nodes, level = 0) {
    if (!nodes || !nodes.length) return;
    for (const n of nodes) {
      out.push({ ...n, _level: level });
      if (n.children && n.children.length) walk(n.children, level + 1);
    }
  }
  walk(tree);
  return out;
}

const flatPages = computed(() => flattenPages(pages.value));

const filteredPages = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return flatPages.value;
  return flatPages.value.filter(
    (p) =>
      (p.title || '').toLowerCase().includes(q) ||
      (p.slug || '').toLowerCase().includes(q)
  );
});

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const s = params.get('success');
  if (s === 'created') successMessage.value = 'Page created.';
  else if (s === 'updated') successMessage.value = 'Page updated.';
  else if (s === 'deleted') successMessage.value = 'Page deleted.';
  else if (s === 'seo-updated') successMessage.value = 'SEO settings saved.';
  try {
    const { data } = await window.axios.get('/api/pages');
    pages.value = data.pages ?? [];
  } finally {
    loading.value = false;
  }
});

function setPagePublishedInTree(tree, id, value) {
  if (!tree || !tree.length) return false;
  for (const n of tree) {
    if (n.id === id) {
      n.is_published = value;
      return true;
    }
    if (n.children?.length && setPagePublishedInTree(n.children, id, value)) return true;
  }
  return false;
}

async function togglePublish(p) {
  try {
    const { data } = await window.axios.post(`/api/pages/${p.id}/toggle-publish`);
    setPagePublishedInTree(pages.value, p.id, data.is_published);
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to update status.';
    alert(msg);
  }
}

async function destroy(p) {
  if (p.children && p.children.length) {
    alert('Cannot delete a page that has children. Delete or move children first.');
    return;
  }
  if (!confirm('Are you sure you want to delete this page?')) return;
  try {
    await window.axios.delete(`/api/pages/${p.id}`);
    const { data } = await window.axios.get('/api/pages');
    pages.value = data.pages ?? [];
    successMessage.value = 'Page deleted.';
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to delete page.';
    alert(msg);
  }
}
</script>

<template>
  <Head title="Pages" />

  <AuthenticatedLayout>
    <template #header>Pages</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Pages</h1>
          <p class="admin-list-page-desc">Website pages for header/footer (e.g. FAQ, Contact us). Child pages appear under their parent on the frontend.</p>
        </div>
        <Link :href="route('pages.create')" class="admin-list-page-cta">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="12" y1="18" x2="12" y2="12" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          Add page
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
        <p v-if="loading" class="admin-text-muted" style="padding: 1.5rem; font-size: 0.6875rem;">Loading…</p>
        <table v-else class="admin-list-table" role="grid">
          <thead>
            <tr>
              <th>Title</th>
              <th>Slug</th>
              <th>Parent</th>
              <th>Placement</th>
              <th>Published</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in filteredPages" :key="p.id" :class="{ 'admin-list-row-child': p._level > 0 }">
              <td :style="p._level ? { paddingLeft: (p._level * 1.25 + 0.5) + 'rem' } : {}">
                <span v-if="p._level">↳</span>
                {{ p.title }}
              </td>
              <td><code class="admin-list-code">{{ p.slug }}</code></td>
              <td>
                <span v-if="p.parent_id" class="admin-text-muted">Child</span>
                <span v-else class="admin-text-muted">—</span>
              </td>
              <td>
                <span v-if="p.placement" class="admin-list-badge admin-list-badge-system">{{ p.placement }}</span>
                <span v-else class="admin-text-muted">—</span>
              </td>
              <td>
                <button
                  type="button"
                  class="admin-list-link admin-publish-toggle"
                  :class="{ 'is-published': p.is_published }"
                  :title="p.is_published ? 'Click to unpublish' : 'Click to publish'"
                  @click="togglePublish(p)"
                >
                  {{ p.is_published ? 'Published' : 'Unpublished' }}
                </button>
              </td>
              <td>
                <Link :href="route('pages.edit', p.id)" class="admin-list-link">Edit</Link>
                <Link :href="route('seo.meta-manager.create', { page_id: p.id })" class="admin-list-link">Edit SEO</Link>
                <button
                  v-if="!p.children?.length"
                  type="button"
                  class="admin-list-link admin-list-link-danger"
                  @click="destroy(p)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!loading && !filteredPages.length" class="admin-text-muted" style="padding: 1.5rem;">No pages yet. Add a page to get started.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
