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
const schemaMenu  = ref(null); // domain id whose schema dropdown is open
const testResults = ref({});   // { [domainId]: { testing, success, message } }

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

function switchDomain(id) {
  switching.value = id ?? 'master';
  router.post(route('domains.switch'), { domain_id: id ?? null }, {
    preserveScroll: true,
    onFinish: () => { switching.value = null; },
  });
}

function toggleSchemaMenu(id) {
  schemaMenu.value = schemaMenu.value === id ? null : id;
}

function syncSchema(domain) {
  schemaMenu.value = null;
  if (!confirm(
    `Run pending migrations on "${domain.name}" database?\n\n` +
    `✓ Safe — only adds new tables/columns, no data is deleted.\n\n` +
    `Click OK to continue.`
  )) return;
  router.post(route('domains.sync-schema', domain.id), {}, { preserveScroll: true });
}

function migrateFresh(domain) {
  schemaMenu.value = null;
  if (!confirm(
    `⚠️  DANGER — Fresh migrate + seed on "${domain.name}"?\n\n` +
    `This will:\n` +
    `  • DROP all tables in that database\n` +
    `  • Re-create them from scratch\n` +
    `  • Run all seeders\n\n` +
    `ALL EXISTING DATA WILL BE PERMANENTLY DELETED.\n\n` +
    `Type OK only if you are 100% sure.`
  )) return;
  router.post(route('domains.migrate-fresh', domain.id), {}, { preserveScroll: true });
}

function confirmDelete(domain) {
  if (!confirm(
    `Remove "${domain.name}" from this CMS?\n\n` +
    `The actual database is NOT deleted — only the connection record is removed.`
  )) return;
  router.delete(route('domains.destroy', domain.id), { preserveScroll: true });
}

// close schema menu when clicking outside
function onClickOutside(e) {
  if (!e.target.closest('.schema-menu-wrap')) schemaMenu.value = null;
}
</script>

<template>
  <Head title="Domains" />
  <AuthenticatedLayout @click="onClickOutside">
    <template #header>Domains</template>

    <div class="admin-form-page">

      <!-- Page header -->
      <div class="admin-form-page-header mb-3 d-flex align-items-center justify-content-between">
        <div>
          <h1 class="admin-form-page-title">Domains</h1>
          <p class="admin-form-page-desc text-muted small">
            Manage all websites connected to this CMS. Switch domain to edit its content.
          </p>
        </div>
        <Link
          :href="route('domains.create')"
          class="btn btn-primary btn-sm"
          title="Add a new website domain with its database credentials"
        >
          + Add domain
        </Link>
      </div>

      <!-- Flash messages -->
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>
      <div v-if="flash?.error" class="alert alert-danger alert-dismissible fade show mb-3">
        {{ flash.error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" />
      </div>

      <!-- Active domain banner -->
      <div class="alert alert-info mb-3 d-flex align-items-center gap-2" style="font-size:.9rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm.93 9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
        </svg>
        <span v-if="activeDomainId">
          Currently managing:
          <strong>{{ domains.find(d => d.id === activeDomainId)?.name ?? 'Unknown' }}</strong>
          &mdash;
          <button
            class="btn btn-link btn-sm p-0"
            style="font-size:.9rem;"
            title="Go back to the master (CMS) database"
            @click="switchDomain(null)"
          >
            Switch to master DB
          </button>
        </span>
        <span v-else>Currently managing: <strong>Master database</strong></span>
      </div>

      <!-- Domains table -->
      <div class="admin-box admin-box-smooth">
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th>Name</th>
                <th>Domain</th>
                <th>Database</th>
                <th>Status</th>
                <th style="min-width:260px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in domains" :key="d.id" :class="{ 'table-primary': d.id === activeDomainId }">

                <!-- Name -->
                <td>
                  <strong>{{ d.name }}</strong>
                  <span v-if="d.is_default" class="badge bg-secondary ms-1" style="font-size:.7rem;">default</span>
                  <span v-if="d.id === activeDomainId" class="badge bg-primary ms-1" style="font-size:.7rem;">active</span>
                </td>

                <!-- Domain URL -->
                <td>
                  <a v-if="d.frontend_url" :href="d.frontend_url" target="_blank" class="text-muted small" title="Open the live website in a new tab">
                    {{ d.domain }} ↗
                  </a>
                  <span v-else class="text-muted small">{{ d.domain }}</span>
                </td>

                <!-- DB info -->
                <td class="text-muted small">{{ d.db_host }} / {{ d.db_name }}</td>

                <!-- Status badge -->
                <td>
                  <span class="badge" :class="d.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ d.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Actions -->
                <td>
                  <!-- DB test result badge (shows below actions) -->
                  <div v-if="testResults[d.id] && !testResults[d.id].testing" class="mb-1">
                    <span
                      class="badge"
                      :class="testResults[d.id].success ? 'bg-success' : 'bg-danger'"
                      style="font-size:.7rem;white-space:normal;max-width:280px;text-align:left;"
                    >
                      {{ testResults[d.id].success ? '✓ ' : '✗ ' }}{{ testResults[d.id].message }}
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-1 flex-wrap">

                    <!-- Switch -->
                    <button
                      class="admin-list-link"
                      :disabled="switching === d.id || d.id === activeDomainId"
                      :title="d.id === activeDomainId
                        ? 'This domain is already active'
                        : 'Switch to this domain\'s database — all CMS edits will be saved here'"
                      @click="switchDomain(d.id)"
                    >
                      {{ d.id === activeDomainId ? 'Active' : (switching === d.id ? 'Switching…' : 'Switch') }}
                    </button>

                    <!-- Test DB connection -->
                    <button
                      class="admin-list-link ms-1"
                      :disabled="testResults[d.id]?.testing"
                      title="Ping the database with saved credentials to verify they are correct"
                      @click="testSavedConnection(d)"
                    >
                      {{ testResults[d.id]?.testing ? 'Testing…' : 'Test DB' }}
                    </button>

                    <!-- Edit -->
                    <Link
                      :href="route('domains.edit', d.id)"
                      class="admin-list-link ms-1"
                      title="Edit domain name, URL and database credentials"
                    >
                      Edit
                    </Link>

                    <!-- Schema actions dropdown -->
                    <div class="schema-menu-wrap ms-1" style="position:relative;display:inline-block;">
                      <button
                        class="admin-list-link"
                        :title="'Database schema tools for ' + d.name"
                        @click.stop="toggleSchemaMenu(d.id)"
                      >
                        Schema
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-left:2px;"><polyline points="6 9 12 15 18 9"/></svg>
                      </button>

                      <!-- Dropdown -->
                      <div
                        v-show="schemaMenu === d.id"
                        class="schema-dropdown"
                      >
                        <!-- Sync schema -->
                        <button
                          class="schema-dropdown-item"
                          title="Run pending migrations — safely adds new tables and columns without deleting any data"
                          @click="syncSchema(d)"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                          <div>
                            <span class="schema-dropdown-label">Sync schema</span>
                            <span class="schema-dropdown-hint">Add new tables / columns — safe, no data loss</span>
                          </div>
                        </button>

                        <div class="schema-dropdown-divider" />

                        <!-- Migrate fresh + seed -->
                        <button
                          class="schema-dropdown-item schema-dropdown-item--danger"
                          title="⚠️ DESTRUCTIVE — Drops ALL tables, recreates from scratch and seeds. All data will be lost."
                          @click="migrateFresh(d)"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
                          <div>
                            <span class="schema-dropdown-label">Fresh + Seed</span>
                            <span class="schema-dropdown-hint">⚠️ Drops all tables, re-migrates &amp; seeds</span>
                          </div>
                        </button>
                      </div>
                    </div>

                    <!-- Remove -->
                    <button
                      v-if="!d.is_default"
                      class="admin-list-link admin-list-link-danger ms-1"
                      title="Remove this domain from the CMS — the actual database is NOT deleted"
                      @click="confirmDelete(d)"
                    >
                      Remove
                    </button>
                    <span
                      v-else
                      class="text-muted ms-1"
                      style="font-size:.75rem;cursor:default;"
                      title="Default domain cannot be removed"
                    >
                      —
                    </span>

                  </div>
                </td>
              </tr>

              <tr v-if="!domains.length">
                <td colspan="5" class="text-center text-muted p-4">
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
        <strong>Button guide:</strong>
        <ul class="mb-0 mt-1" style="padding-left:1.2rem;line-height:1.8;">
          <li><strong>Switch</strong> — sets this domain as active; all CMS edits go to its database</li>
          <li><strong>Test DB</strong> — pings the database with saved credentials; shows ✓ green or ✗ red with the error</li>
          <li><strong>Edit</strong> — update the domain name, URL or database credentials</li>
          <li><strong>Schema → Sync schema</strong> — runs pending migrations (safe, no data deleted)</li>
          <li><strong>Schema → Fresh + Seed</strong> — <span style="color:#c00;">DESTRUCTIVE</span>: drops all tables, recreates and seeds (use only on empty/new databases)</li>
          <li><strong>Remove</strong> — disconnects the domain from this CMS (database itself is untouched)</li>
        </ul>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Schema dropdown */
.schema-menu-wrap { position: relative; }

.schema-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  z-index: 200;
  background: #fff;
  border: 1px solid #e0e0ea;
  border-radius: 10px;
  box-shadow: 0 6px 24px rgba(0,0,0,.12);
  min-width: 240px;
  overflow: hidden;
}

.schema-dropdown-item {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  width: 100%;
  padding: .65rem .9rem;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background .12s;
  color: #222;
}
.schema-dropdown-item:hover { background: #f5f5fb; }
.schema-dropdown-item svg { flex-shrink: 0; margin-top: 2px; color: #4945ff; }

.schema-dropdown-item--danger svg { color: #c00; }
.schema-dropdown-item--danger:hover { background: #fff5f5; }
.schema-dropdown-item--danger .schema-dropdown-label { color: #c00; }

.schema-dropdown-label {
  display: block;
  font-size: .8125rem;
  font-weight: 600;
  line-height: 1.2;
}
.schema-dropdown-hint {
  display: block;
  font-size: .72rem;
  color: #888;
  margin-top: .1rem;
  line-height: 1.3;
}

.schema-dropdown-divider {
  height: 1px;
  background: #f0f0f5;
  margin: 0;
}
</style>
