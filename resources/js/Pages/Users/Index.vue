<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();

const users = ref([]);
const loading = ref(true);
const successMessage = ref('');
const searchQuery = ref('');
const selectedIds = ref(new Set());

const filteredUsers = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return users.value;
  return users.value.filter(
    (u) =>
      (u.name || '').toLowerCase().includes(q) ||
      (u.email || '').toLowerCase().includes(q) ||
      (u.roles?.some((r) => (r.name || '').toLowerCase().includes(q)))
  );
});

const allSelected = computed({
  get() {
    return filteredUsers.value.length > 0 && selectedIds.value.size === filteredUsers.value.length;
  },
  set(v) {
    if (v) filteredUsers.value.forEach((u) => selectedIds.value.add(u.id));
    else selectedIds.value.clear();
  },
});

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === 'created') successMessage.value = 'User created.';
  else if (params.get('success') === 'updated') successMessage.value = 'User updated.';
  else if (params.get('success') === 'deleted') successMessage.value = 'User deleted.';
  try {
    const { data } = await window.axios.get('/api/users');
    users.value = data.users ?? [];
  } finally {
    loading.value = false;
  }
});

function toggleSelect(id) {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id);
  else selectedIds.value.add(id);
  selectedIds.value = new Set(selectedIds.value);
}

async function destroy(id) {
  if (!confirm('Are you sure you want to delete this user?')) return;
  try {
    await window.axios.delete(`/api/users/${id}`);
    users.value = users.value.filter((u) => u.id !== id);
    selectedIds.value.delete(id);
    selectedIds.value = new Set(selectedIds.value);
    successMessage.value = 'User deleted.';
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to delete user.';
    alert(msg);
  }
}

const canCreate = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('users.create');
});
const canEdit = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('users.edit');
});
const canDelete = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('users.delete');
});
const currentUserId = computed(() => page.props.auth?.user?.id);
</script>

<template>
  <Head title="Users" />

  <AuthenticatedLayout>
    <template #header>Users</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Users</h1>
          <p class="admin-list-page-desc">All the users who have access to the admin panel.</p>
        </div>
        <Link
          v-if="canCreate"
          :href="route('users.create')"
          class="admin-list-page-cta"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
            <polyline points="22,6 12,13 2,6" />
          </svg>
          Invite new user
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
            placeholder="Search users..."
            aria-label="Search users"
          />
        </div>
        <button type="button" class="admin-list-filters-btn" aria-label="Filters">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
          </svg>
          Filters
        </button>
      </div>

      <div class="admin-list-table-wrap">
        <p v-if="loading" class="admin-text-muted" style="padding: 1.5rem; font-size: 0.6875rem;">Loading…</p>
        <table v-else class="admin-list-table" role="grid">
          <thead>
            <tr>
              <th class="admin-list-check">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate="selectedIds.size > 0 && !allSelected"
                  aria-label="Select all"
                  @change="allSelected = $event.target.checked"
                />
              </th>
              <th>Name</th>
              <th>Email</th>
              <th>Roles</th>
              <th v-if="canEdit || canDelete">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td class="admin-list-check">
                <input
                  type="checkbox"
                  :checked="selectedIds.has(u.id)"
                  :aria-label="`Select ${u.name}`"
                  @change="toggleSelect(u.id)"
                />
              </td>
              <td>{{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td>
                <span v-for="r in u.roles" :key="r.id" class="admin-list-badge">{{ r.name }}</span>
                <span v-if="!u.roles?.length" class="admin-text-muted">—</span>
              </td>
              <td v-if="canEdit || canDelete">
                <Link v-if="canEdit" :href="route('users.edit', u.id)" class="admin-list-link">Edit</Link>
                <button
                  v-if="u.id !== currentUserId && canDelete"
                  type="button"
                  class="admin-list-link admin-list-link-danger"
                  @click="destroy(u.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
