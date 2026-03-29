<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  domains:        { type: Array,  default: () => [] },
  activeDomainId: { type: Number, default: null },
  flash:          { type: Object, default: () => ({}) },
});

const switching   = ref(null);
const testResults = ref({});

/* ── Domain switch ── */
function switchDomain(id) {
  switching.value = id ?? 'master';
  router.post(route('domains.switch'), { domain_id: id ?? null }, {
    preserveScroll: true,
    onFinish: () => { switching.value = null; },
  });
}

/* ── Schema actions ── */
function syncSchema(domain) {
  if (!confirm(
    `Run pending migrations on "${domain.name}" database?\n\n` +
    `✓ Safe — only adds new tables/columns, no data is deleted.`
  )) return;
  router.post(route('domains.sync-schema', domain.id), {}, { preserveScroll: true });
}

function migrateFresh(domain) {
  if (!confirm(
    `⚠️ DANGER — Fresh migrate + seed on "${domain.name}"?\n\n` +
    `This will:\n  • DROP all tables\n  • Re-create from scratch\n  • Run all seeders\n\n` +
    `ALL EXISTING DATA WILL BE PERMANENTLY DELETED.\n\nClick OK only if you are 100% sure.`
  )) return;
  router.post(route('domains.migrate-fresh', domain.id), {}, { preserveScroll: true });
}

/* ── Test DB connection ── */
async function testSavedConnection(domain) {
  testResults.value[domain.id] = { testing: true };
  try {
    const res = await fetch(route('domains.test-saved-connection', domain.id), {
      method: 'POST',
      headers: {
        'Accept':       'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
    });
    const data = await res.json();
    testResults.value[domain.id] = { testing: false, ...data };
  } catch (e) {
    testResults.value[domain.id] = { testing: false, success: false, message: e.message };
  }
}

/* ── Delete ── */
function confirmDelete(domain) {
  if (!confirm(
    `Remove "${domain.name}" from this CMS?\n\nThe actual database is NOT deleted — only the connection record is removed.`
  )) return;
  router.delete(route('domains.destroy', domain.id), { preserveScroll: true });
}
</script>

<template>
  <Head title="Domains" />
  <AuthenticatedLayout>
    <template #header>Domains</template>

    <div class="admin-form-page">

      <!-- Header -->
      <div class="admin-form-page-header mb-3 d-flex align-items-center justify-content-between">
        <div>
          <h1 class="admin-form-page-title">Domains</h1>
          <p class="admin-form-page-desc text-muted small">
            Manage websites connected to this CMS. Switch a domain to edit its content in that database.
          </p>
        </div>
        <Link
          :href="route('domains.create')"
          class="btn btn-primary btn-sm"
          title="Connect a new website with its database credentials"
        >+ Add domain</Link>
      </div>

      <!-- Flash -->
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3">
        {{ flash.success }}<button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>
      <div v-if="flash?.error" class="alert alert-danger alert-dismissible fade show mb-3">
        {{ flash.error }}<button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>

      <!-- Active domain banner -->
      <div class="alert alert-info mb-3 d-flex align-items-center gap-2" style="font-size:.875rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm.93 9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
        <span v-if="activeDomainId">
          Managing: <strong>{{ domains.find(d => d.id === activeDomainId)?.name ?? 'Unknown' }}</strong>
          &mdash;
          <button class="btn btn-link btn-sm p-0" style="font-size:.875rem;" title="Go back to master database" @click="switchDomain(null)">Switch to master DB</button>
        </span>
        <span v-else>Managing: <strong>Master database</strong></span>
      </div>

      <!-- Table -->
      <div class="admin-box admin-box-smooth">
        <div class="table-responsive">
          <table class="admin-list-table mb-0" role="grid">
            <thead>
              <tr>
                <th>Name</th>
                <th>Domain</th>
                <th>Database</th>
                <th>Status</th>
                <th style="min-width:120px;">Schema</th>
                <th style="min-width:160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in domains" :key="d.id" :class="{ 'table-primary': d.id === activeDomainId }">

                <!-- Name -->
                <td>
                  <strong>{{ d.name }}</strong>
                  <span v-if="d.is_default" class="badge bg-secondary ms-1" style="font-size:.68rem;">default</span>
                  <span v-if="d.id === activeDomainId" class="badge bg-primary ms-1" style="font-size:.68rem;">active</span>
                </td>

                <!-- Domain URL -->
                <td class="small">
                  <a v-if="d.frontend_url" :href="d.frontend_url" target="_blank" class="text-muted" title="Open live website">
                    {{ d.domain }} ↗
                  </a>
                  <span v-else class="text-muted">{{ d.domain }}</span>
                </td>

                <!-- DB -->
                <td class="small text-muted">{{ d.db_host }} / {{ d.db_name }}</td>

                <!-- Status -->
                <td>
                  <span class="badge" :class="d.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ d.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Schema column — both actions listed clearly -->
                <td>
                  <div class="d-flex flex-column gap-1">
                    <button
                      class="admin-list-link text-start"
                      style="font-size:.8rem;"
                      title="Run pending migrations — safe, adds new tables/columns only, no data deleted"
                      @click="syncSchema(d)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                      Sync schema
                    </button>
                    <button
                      class="admin-list-link admin-list-link-danger text-start"
                      style="font-size:.8rem;"
                      title="⚠️ DESTRUCTIVE: Drops ALL tables, re-migrates and seeds. All data lost."
                      @click="migrateFresh(d)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
                      Fresh + Seed ⚠️
                    </button>
                  </div>
                </td>

                <!-- Actions column -->
                <td>
                  <!-- Test result badge -->
                  <div v-if="testResults[d.id] && !testResults[d.id].testing" class="mb-1">
                    <span
                      class="badge"
                      :class="testResults[d.id].success ? 'bg-success' : 'bg-danger'"
                      style="font-size:.68rem;white-space:normal;max-width:200px;text-align:left;display:inline-block;"
                    >
                      {{ testResults[d.id].success ? '✓ ' : '✗ ' }}{{ testResults[d.id].message }}
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Switch -->
                    <button
                      class="admin-list-link"
                      :disabled="switching === d.id || d.id === activeDomainId"
                      :title="d.id === activeDomainId ? 'Already active' : 'Switch to this domain\'s database'"
                      @click="switchDomain(d.id)"
                    >
                      {{ d.id === activeDomainId ? 'Active' : (switching === d.id ? 'Switching…' : 'Switch') }}
                    </button>

                    <!-- Test DB -->
                    <button
                      class="admin-list-link"
                      :disabled="testResults[d.id]?.testing"
                      title="Test database connection with saved credentials"
                      @click="testSavedConnection(d)"
                    >
                      {{ testResults[d.id]?.testing ? 'Testing…' : 'Test DB' }}
                    </button>

                    <!-- Edit -->
                    <Link :href="route('domains.edit', d.id)" class="admin-list-link" title="Edit credentials and settings">Edit</Link>

                    <!-- Remove -->
                    <button
                      v-if="!d.is_default"
                      class="admin-list-link admin-list-link-danger"
                      title="Remove from CMS (database is NOT deleted)"
                      @click="confirmDelete(d)"
                    >Remove</button>
                  </div>
                </td>

              </tr>
              <tr v-if="!domains.length">
                <td colspan="6" class="text-center text-muted p-4">
                  No domains yet.
                  <Link :href="route('domains.create')" class="ms-1">+ Add your first domain</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Legend -->
      <div class="mt-3 p-3 bg-light rounded border small text-muted">
        <strong>Column guide:</strong>
        <ul class="mb-0 mt-1" style="padding-left:1.2rem;line-height:1.9;">
          <li><strong>Switch</strong> — activates this domain; all CMS edits are saved to its database</li>
          <li><strong>Test DB</strong> — pings the database to verify credentials are correct (green ✓ / red ✗)</li>
          <li><strong>Edit</strong> — update domain name, URL or database credentials</li>
          <li><strong>Schema → Sync schema</strong> — runs pending migrations, safe, no data deleted</li>
          <li><strong>Schema → Fresh + Seed ⚠️</strong> — drops ALL tables, recreates and seeds (use on empty DBs only)</li>
          <li><strong>Remove</strong> — disconnects domain from CMS, actual database is untouched</li>
        </ul>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
