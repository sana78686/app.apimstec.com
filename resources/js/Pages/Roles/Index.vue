<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const roles = ref([]);
const loading = ref(true);
const successMessage = ref('');
const searchQuery = ref('');
const selectedIds = ref(new Set());

const filteredRoles = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return roles.value;
  return roles.value.filter(
    (r) =>
      (r.name || '').toLowerCase().includes(q) ||
      (r.slug || '').toLowerCase().includes(q)
  );
});

const allSelected = computed({
  get() {
    const selectable = filteredRoles.value.filter((r) => !r.is_system);
    return selectable.length > 0 && selectedIds.value.size === selectable.length;
  },
  set(v) {
    if (v) filteredRoles.value.filter((r) => !r.is_system).forEach((r) => selectedIds.value.add(r.id));
    else selectedIds.value.clear();
    selectedIds.value = new Set(selectedIds.value);
  },
});

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === 'created') successMessage.value = 'Role created.';
  else if (params.get('success') === 'updated') successMessage.value = 'Role updated.';
  else if (params.get('success') === 'deleted') successMessage.value = 'Role deleted.';
  try {
    const { data } = await window.axios.get('/api/roles');
    roles.value = data.roles ?? [];
  } finally {
    loading.value = false;
  }
});

function toggleSelect(id, isSystem) {
  if (isSystem) return;
  if (selectedIds.value.has(id)) selectedIds.value.delete(id);
  else selectedIds.value.add(id);
  selectedIds.value = new Set(selectedIds.value);
}

async function destroy(id, isSystem) {
  if (isSystem) return;
  if (!confirm('Are you sure you want to delete this role?')) return;
  try {
    await window.axios.delete(`/api/roles/${id}`);
    roles.value = roles.value.filter((r) => r.id !== id);
    selectedIds.value.delete(id);
    selectedIds.value = new Set(selectedIds.value);
    successMessage.value = 'Role deleted.';
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to delete role.';
    alert(msg);
  }
}

const canCreate = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('roles.create');
});
const canEdit = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('roles.edit');
});
const canDelete = computed(() => {
  const p = page.props.auth?.user?.permissions;
  return p?.includes('*') || p?.includes('roles.delete');
});
</script>

<template>
  <Head title="Roles" />

  <AuthenticatedLayout>
    <template #header>Roles</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Roles</h1>
          <p class="admin-list-page-desc">Manage roles and their permissions for the admin panel.</p>
        </div>
        <Link
          v-if="canCreate"
          :href="route('roles.create')"
          class="admin-list-page-cta"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Add role
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
            placeholder="Search roles..."
            aria-label="Search roles"
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
              <th>Slug</th>
              <th>Users</th>
              <th>Permissions</th>
              <th v-if="canEdit || canDelete">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in filteredRoles" :key="r.id">
              <td class="admin-list-check">
                <input
                  type="checkbox"
                  :checked="selectedIds.has(r.id)"
                  :disabled="r.is_system"
                  :aria-label="`Select ${r.name}`"
                  @change="toggleSelect(r.id, r.is_system)"
                />
              </td>
              <td>
                {{ r.name }}
                <span v-if="r.is_system" class="admin-list-badge admin-list-badge-system">System</span>
              </td>
              <td><code class="admin-list-code">{{ r.slug }}</code></td>
              <td>{{ r.users_count }}</td>
              <td>{{ r.permissions?.length ?? 0 }} permissions</td>
              <td v-if="canEdit || canDelete">
                <Link v-if="!r.is_system && canEdit" :href="route('roles.edit', { role: r.id })" class="admin-list-link">Edit</Link>
                <button
                  v-if="!r.is_system && canDelete"
                  type="button"
                  class="admin-list-link admin-list-link-danger"
                  @click="destroy(r.id, r.is_system)"
                >
                  Delete
                </button>
                <span v-if="r.is_system" class="admin-text-muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
