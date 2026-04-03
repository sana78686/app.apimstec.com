<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();
const blogs = ref([]);
const loading = ref(true);
const successMessage = ref('');
const searchQuery = ref('');

const filteredBlogs = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return blogs.value;
  return blogs.value.filter(
    (b) =>
      (b.title || '').toLowerCase().includes(q) ||
      (b.slug || '').toLowerCase().includes(q) ||
      (b.excerpt || '').toLowerCase().includes(q)
  );
});

const perPage     = 20;
const currentPage = ref(1);
const pagedBlogs  = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredBlogs.value.slice(start, start + perPage);
});
watch(searchQuery, () => { currentPage.value = 1; });

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const s = params.get('success');
  if (s === 'created') successMessage.value = 'Blog created.';
  else if (s === 'updated') successMessage.value = 'Blog updated.';
  else if (s === 'deleted') successMessage.value = 'Blog deleted.';
  try {
    const { data } = await window.axios.get('/api/blogs');
    blogs.value = data.blogs ?? [];
  } finally {
    loading.value = false;
  }
});

async function destroy(b) {
  if (!confirm('Are you sure you want to delete this blog post?')) return;
  try {
    await window.axios.delete(`/api/blogs/${b.id}`);
    blogs.value = blogs.value.filter((x) => x.id !== b.id);
    successMessage.value = 'Blog deleted.';
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to delete blog.';
    alert(msg);
  }
}

const STATUS_OPTIONS = [
  { value: 'draft',    label: 'Draft'    },
  { value: 'visible',  label: 'Visible'  },
  { value: 'disabled', label: 'Disabled' },
];

async function changeStatus(b, newVisibility) {
  if (b.visibility === newVisibility) return;
  const prev = b.visibility;
  b.visibility = newVisibility;
  try {
    const { data } = await window.axios.patch(`/api/blogs/${b.id}/status`, { visibility: newVisibility });
    b.visibility   = data.visibility;
    b.is_published = data.is_published;
    successMessage.value = data.message || 'Status updated.';
  } catch (e) {
    b.visibility = prev;
    const msg = e.response?.data?.message || 'Failed to update status.';
    alert(msg);
  }
}
</script>

<template>
  <Head title="Blogs" />

  <AuthenticatedLayout>
    <template #header>Blogs</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Blogs</h1>
          <p class="admin-list-page-desc">Blog posts and articles.</p>
        </div>
        <Link :href="route('blogs.create')" class="admin-list-page-cta">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="12" y1="18" x2="12" y2="12" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          Add blog post
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
            placeholder="Search blogs..."
            aria-label="Search blogs"
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
              <th>Author</th>
              <th>Published at</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in pagedBlogs" :key="b.id">
              <td>{{ b.title }}</td>
              <td><code class="admin-list-code">{{ b.slug }}</code></td>
              <td>{{ b.author?.name ?? '—' }}</td>
              <td>{{ b.published_at ? new Date(b.published_at).toLocaleDateString() : '—' }}</td>
              <td>
                <select
                  :value="b.visibility || 'draft'"
                  class="admin-status-select"
                  :class="`admin-status-select--${b.visibility || 'draft'}`"
                  :title="`Status: ${b.visibility || 'draft'}`"
                  @change="changeStatus(b, $event.target.value)"
                >
                  <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
              </td>
              <td>
                <Link :href="route('blogs.edit', { blog: b.id })" class="admin-list-link">Edit</Link>
                <button
                  type="button"
                  class="admin-list-link admin-list-link-danger"
                  @click="destroy(b)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <Pagination
          v-if="!loading"
          :total="filteredBlogs.length"
          :per-page="perPage"
          :current-page="currentPage"
          @update:current-page="currentPage = $event"
        />
        <p v-if="!loading && !filteredBlogs.length" class="admin-text-muted" style="padding: 1.5rem;">No blog posts yet. Add one to get started.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
