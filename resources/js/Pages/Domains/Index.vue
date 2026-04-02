<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({
  domains:        { type: Array,  default: () => [] },
  activeDomainId: { type: Number, default: null },
});

const switching    = ref(null);
const testResults  = ref({});
const schemaOpen   = ref(null); // which domain's schema dropdown is open

/* ── Close schema dropdown on outside click ── */
function onOutsideClick() { schemaOpen.value = null; }
onMounted(()    => document.addEventListener('click', onOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', onOutsideClick));

function toggleSchema(id, e) {
  e.stopPropagation();
  schemaOpen.value = schemaOpen.value === id ? null : id;
}

/* ── Domain switch ── */
function switchDomain(id) {
  switching.value = id;
  router.post(route('domains.switch'), { domain_id: id }, {
    preserveScroll: true,
    onFinish: () => { switching.value = null; },
  });
}

function openDomainPicker() {
  switching.value = 'picker';
  router.post(route('domains.switch'), { domain_id: null }, {
    onFinish: () => { switching.value = null; },
  });
}

function schemaDbLabel(d) {
  return `${d.db_host} / ${d.db_name}`;
}

/* ── Schema actions (each runs only on that row’s database, never the CMS master) ── */
function syncSchema(domain) {
  schemaOpen.value = null;
  if (!confirm(
    `Run pending migrations on this website’s database only?\n\n` +
    `Target: ${schemaDbLabel(domain)}\n` +
    `Site: "${domain.name}"\n\n` +
    `✓ Safe — only adds new tables/columns, no data is deleted.\n` +
    `(The CMS master database is never modified here.)`
  )) return;
  router.post(route('domains.sync-schema', domain.id), {}, { preserveScroll: true });
}

function rollbackSchema(domain) {
  schemaOpen.value = null;
  if (!confirm(
    `Roll back the last migration batch on this database?\n\n` +
    `Target: ${schemaDbLabel(domain)}\n` +
    `Site: "${domain.name}"\n\n` +
    `This will undo the most recent migration. Data in removed columns/tables may be lost.`
  )) return;
  router.post(route('domains.rollback-schema', domain.id), {}, { preserveScroll: true });
}

function migrateFresh(domain) {
  schemaOpen.value = null;
  if (!confirm(
    `⚠️ DANGER — Fresh migrate on this database only?\n\n` +
    `Target: ${schemaDbLabel(domain)}\n` +
    `Site: "${domain.name}"\n\n` +
    `This will:\n  • DROP all tables in that database\n  • Re-create from scratch\n\n` +
    `ALL DATA IN THAT DATABASE WILL BE PERMANENTLY DELETED.\n` +
    `(The CMS master database is not affected.)\n\n` +
    `Click OK only if you are 100% sure.`
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

      <!-- Active domain banner -->
      <div class="alert alert-info mb-3 d-flex align-items-center gap-2" style="font-size:.875rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm.93 9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
        <span v-if="activeDomainId">
          Active for CMS: <strong>{{ domains.find(d => d.id === activeDomainId)?.name ?? 'Unknown' }}</strong>
          &mdash;
          <button type="button" class="btn btn-link btn-sm p-0" style="font-size:.875rem;" title="Pick another website" @click="openDomainPicker">Change website</button>
        </span>
        <span v-else>
          No website selected for content/SEO tools.
          <button type="button" class="btn btn-link btn-sm p-0" style="font-size:.875rem;" @click="openDomainPicker">Open website picker</button>
        </span>
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
                <th style="min-width:110px;">Schema</th>
                <th style="min-width:180px;">Actions</th>
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

                <!-- Schema: only when domain has its own DB (not same as CMS master) -->
                <td>
                  <span
                    v-if="d.can_run_schema === false"
                    class="text-muted small"
                    title="This domain points at the CMS master database. Use a separate database for the website, then schema tools apply only there."
                  >—</span>
                  <div
                    v-else
                    class="domain-schema-wrap"
                    style="position:relative;display:inline-block;"
                  >
                    <button
                      class="btn btn-sm btn-outline-secondary domain-schema-btn"
                      title="Run migrations on this row’s database only (host / name shown in Database column)"
                      @click="toggleSchema(d.id, $event)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;vertical-align:middle;"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4.03 3-9 3S3 13.66 3 12"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/></svg>
                      Schema
                      <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:2px;vertical-align:middle;"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>

                    <!-- Dropdown menu -->
                    <div v-if="schemaOpen === d.id" class="domain-schema-menu" @click.stop>
                      <!-- Sync -->
                      <button
                        class="domain-schema-item"
                        title="Run pending migrations — safe, adds new tables/columns only"
                        @click="syncSchema(d)"
                      >
                        <span class="domain-schema-icon text-primary">
                          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        </span>
                        <span>
                          <strong>Sync schema</strong>
                          <small class="d-block text-muted" style="font-size:.72rem;">Run pending migrations (safe)</small>
                        </span>
                      </button>

                      <!-- Rollback -->
                      <button
                        class="domain-schema-item"
                        title="Roll back the last migration batch"
                        @click="rollbackSchema(d)"
                      >
                        <span class="domain-schema-icon text-warning">
                          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/></svg>
                        </span>
                        <span>
                          <strong>Rollback last</strong>
                          <small class="d-block text-muted" style="font-size:.72rem;">Undo last migration batch</small>
                        </span>
                      </button>

                      <div class="domain-schema-divider"></div>

                      <!-- Fresh -->
                      <button
                        class="domain-schema-item domain-schema-item-danger"
                        title="⚠️ DESTRUCTIVE: Drop ALL tables, re-migrate from scratch. All data lost."
                        @click="migrateFresh(d)"
                      >
                        <span class="domain-schema-icon text-danger">
                          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </span>
                        <span>
                          <strong>Fresh migrate ⚠️</strong>
                          <small class="d-block" style="font-size:.72rem;color:#dc3545;">Drops ALL tables — data lost!</small>
                        </span>
                      </button>
                    </div>
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
                    <!-- Switch / Active -->
                    <button
                      class="admin-list-link"
                      :disabled="switching === d.id || d.id === activeDomainId"
                      :title="d.id === activeDomainId ? 'Already active — currently managing this domain' : 'Switch to this domain\'s database'"
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
                    <Link
                      :href="route('domains.edit', d.id)"
                      class="admin-list-link"
                      title="Edit credentials and domain settings"
                    >Edit</Link>

                    <!-- Remove -->
                    <button
                      v-if="!d.is_default"
                      class="admin-list-link admin-list-link-danger"
                      title="Remove from CMS — actual database is NOT deleted"
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

      <!-- Column guide legend -->
      <div class="mt-3 p-3 bg-light rounded border small text-muted">
        <strong>Column guide:</strong>
        <ul class="mb-0 mt-1" style="padding-left:1.2rem;line-height:1.9;">
          <li><strong>Switch</strong> — activates this domain; all CMS edits are saved to its database</li>
          <li><strong>Test DB</strong> — pings the database to verify credentials are correct (green ✓ / red ✗)</li>
          <li><strong>Edit</strong> — update domain name, URL or database credentials</li>
          <li><strong>Schema</strong> — runs only on that row’s <strong>Database</strong> (host / DB name), never on the CMS master. Hidden if the domain uses the same DB as master.</li>
          <li><strong>Schema → Sync schema</strong> — pending migrations on that site DB only, safe, no data deleted</li>
          <li><strong>Schema → Rollback last</strong> — undoes the last migration batch on that site DB</li>
          <li><strong>Schema → Fresh migrate ⚠️</strong> — drops ALL tables in that site DB, recreates from scratch</li>
          <li><strong>Remove</strong> — disconnects domain from CMS, actual database is untouched</li>
        </ul>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Schema dropdown */
.domain-schema-btn {
  font-size: .8rem;
  padding: .25rem .6rem;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  white-space: nowrap;
}

.domain-schema-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  z-index: 1000;
  min-width: 210px;
  background: #fff;
  border: 1px solid #e2e2ee;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  padding: .35rem;
  animation: fadeInDown .1s ease;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.domain-schema-item {
  display: flex;
  align-items: flex-start;
  gap: .55rem;
  width: 100%;
  padding: .5rem .6rem;
  border: none;
  background: transparent;
  border-radius: 7px;
  text-align: left;
  cursor: pointer;
  font-size: .8125rem;
  color: #222;
  transition: background .1s;
}

.domain-schema-item:hover {
  background: #f4f4fb;
}

.domain-schema-item-danger:hover {
  background: #fff5f5;
}

.domain-schema-icon {
  margin-top: .15rem;
  flex-shrink: 0;
}

.domain-schema-divider {
  height: 1px;
  background: #eee;
  margin: .3rem .2rem;
}
</style>
