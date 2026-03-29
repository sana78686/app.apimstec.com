<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  domains:        { type: Array,  default: () => [] },
  activeDomainId: { type: Number, default: null },
  flash:          { type: Object, default: () => ({}) },
});

const switching = ref(null);

function switchDomain(id) {
  switching.value = id ?? 'master';
  router.post(route('domains.switch'), { domain_id: id ?? null }, {
    preserveScroll: true,
    onFinish: () => { switching.value = null; },
  });
}

function syncSchema(domain) {
  if (!confirm(`Run schema migrations on "${domain.name}" database?\nThis will create/update all tables in that DB.`)) return;
  router.post(route('domains.sync-schema', domain.id), {}, { preserveScroll: true });
}

function confirmDelete(domain) {
  if (!confirm(`Remove domain "${domain.name}"? This only removes it from the CMS — the actual database is untouched.`)) return;
  router.delete(route('domains.destroy', domain.id), { preserveScroll: true });
}
</script>

<template>
  <Head title="Domains" />
  <AuthenticatedLayout>
    <template #header>Domains</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3 d-flex align-items-center justify-content-between">
        <div>
          <h1 class="admin-form-page-title">Domains</h1>
          <p class="admin-form-page-desc text-muted small">
            Manage all websites connected to this CMS. Switch domain to edit its content.
          </p>
        </div>
        <Link :href="route('domains.create')" class="btn btn-primary btn-sm">+ Add domain</Link>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>
      <div v-if="flash?.error" class="alert alert-danger alert-dismissible fade show mb-3">
        {{ flash.error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>

      <!-- Currently active domain banner -->
      <div class="alert alert-info mb-3 d-flex align-items-center gap-2" style="font-size:.9rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm.93 9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
        <span v-if="activeDomainId">
          Managing:
          <strong>{{ domains.find(d => d.id === activeDomainId)?.name ?? 'Unknown' }}</strong>
          &mdash;
          <button class="btn btn-link btn-sm p-0" style="font-size:.9rem;" @click="switchDomain(null)">
            Switch to master
          </button>
        </span>
        <span v-else>Managing: <strong>Master database</strong></span>
      </div>

      <div class="admin-box admin-box-smooth">
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th>Name</th>
                <th>Domain</th>
                <th>Database</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in domains" :key="d.id" :class="{ 'table-primary': d.id === activeDomainId }">
                <td>
                  <strong>{{ d.name }}</strong>
                  <span v-if="d.is_default" class="badge bg-secondary ms-1" style="font-size:.7rem;">default</span>
                  <span v-if="d.id === activeDomainId" class="badge bg-primary ms-1" style="font-size:.7rem;">active</span>
                </td>
                <td>
                  <a v-if="d.frontend_url" :href="d.frontend_url" target="_blank" class="text-muted small">
                    {{ d.domain }}
                  </a>
                  <span v-else class="text-muted small">{{ d.domain }}</span>
                </td>
                <td class="text-muted small">{{ d.db_host }} / {{ d.db_name }}</td>
                <td>
                  <span class="badge" :class="d.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ d.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <button
                    class="admin-list-link"
                    :disabled="switching === d.id || d.id === activeDomainId"
                    @click="switchDomain(d.id)"
                  >
                    {{ d.id === activeDomainId ? 'Active' : (switching === d.id ? 'Switching…' : 'Switch') }}
                  </button>
                  <Link :href="route('domains.edit', d.id)" class="admin-list-link ms-2">Edit</Link>
                  <button class="admin-list-link ms-2" @click="syncSchema(d)">Sync schema</button>
                  <button v-if="!d.is_default" class="admin-list-link admin-list-link-danger ms-2" @click="confirmDelete(d)">Remove</button>
                </td>
              </tr>
              <tr v-if="!domains.length">
                <td colspan="5" class="text-center text-muted p-4">No domains yet. Click "+ Add domain" to connect your first website.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-3 p-3 bg-light rounded border small text-muted">
        <strong>How it works:</strong>
        Click <em>Switch</em> to start managing that domain's content — all pages, blogs and SEO settings you edit will be saved to <em>that</em> database.
        Click <em>Sync schema</em> after adding a new domain to create all required tables in its database.
      </div>
    </div>
  </AuthenticatedLayout>
</template>
