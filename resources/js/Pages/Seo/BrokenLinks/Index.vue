<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  brokenLinks: { type: Array, default: () => [] },
  redirects: { type: Array, default: () => [] },
});

const successMessage = ref('');
const errorMessage = ref('');
const creatingId = ref(null);
const toPath = ref('');
const dismissingId = ref(null);

function formatDate(iso) {
  if (!iso) return '—';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, { dateStyle: 'short' }) + ' ' + d.toLocaleTimeString(undefined, { timeStyle: 'short' });
  } catch {
    return iso;
  }
}

function startCreate(log) {
  creatingId.value = log.id;
  toPath.value = '/' + (log.path || '');
}

function cancelCreate() {
  creatingId.value = null;
  toPath.value = '';
}

async function submitRedirect(log) {
  const to = (toPath.value || '').trim().replace(/^\/+/, '');
  if (!to) {
    errorMessage.value = 'Enter the destination path.';
    return;
  }
  if (to === log.path) {
    errorMessage.value = 'Destination must be different from the broken path.';
    return;
  }
  errorMessage.value = '';
  successMessage.value = '';
  try {
    await window.axios.post('/api/seo/broken-links/create-redirect', {
      from_path: log.path,
      to_path: to,
    });
    successMessage.value = '301 redirect created.';
    cancelCreate();
    router.reload();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to create redirect.';
  }
}

async function dismiss(log) {
  dismissingId.value = log.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    await window.axios.post('/api/seo/broken-links/dismiss', { id: log.id });
    successMessage.value = 'Log dismissed.';
    router.reload();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to dismiss.';
  } finally {
    dismissingId.value = null;
  }
}
</script>

<template>
  <Head title="Broken Link & Error Monitor" />

  <AuthenticatedLayout>
    <template #header>Broken Link & Error Monitor</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error">{{ errorMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Redirect & Error Manager</h1>
          <p class="admin-list-page-desc">
            Detects <strong>404 errors</strong>, logs <strong>broken links</strong>, and lets you create <strong>301 redirects</strong> to prevent SEO loss. When visitors hit a missing page, it’s recorded here so you can redirect them to the right URL.
          </p>
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">404 errors (broken links)</h2>
        <p class="text-muted small mb-3">Paths that returned 404. Create a 301 redirect to send traffic to the correct URL, or dismiss if not needed.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Path</th>
                <th class="admin-url-table-th">Hits</th>
                <th class="admin-url-table-th">Last seen</th>
                <th class="admin-url-table-th">Referer</th>
                <th class="admin-url-table-th">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="log in brokenLinks" :key="log.id">
                <tr>
                  <td class="align-middle">
                    <code class="admin-list-code admin-url-path-code">/{{ log.path }}</code>
                  </td>
                  <td class="align-middle">{{ log.hit_count }}</td>
                  <td class="align-middle small">{{ formatDate(log.last_seen_at) }}</td>
                  <td class="align-middle small text-muted" :title="log.referer">{{ (log.referer || '—').slice(0, 40) }}{{ (log.referer || '').length > 40 ? '…' : '' }}</td>
                  <td class="align-middle">
                    <template v-if="creatingId === log.id">
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input
                          v-model="toPath"
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="/new-path or new-path"
                          style="max-width: 220px;"
                          @keydown.enter.prevent="submitRedirect(log)"
                        />
                        <button type="button" class="btn btn-sm btn-primary" @click="submitRedirect(log)">Create 301</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="cancelCreate">Cancel</button>
                      </div>
                    </template>
                    <template v-else>
                      <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="startCreate(log)">Create 301 redirect</button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="dismissingId === log.id" @click="dismiss(log)">{{ dismissingId === log.id ? '…' : 'Dismiss' }}</button>
                    </template>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
          <p v-if="!brokenLinks.length" class="admin-text-muted p-3 mb-0">No 404 errors logged yet. Broken links will appear here when visitors hit missing pages.</p>
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Existing redirects</h2>
        <p class="text-muted small mb-3">All 301 redirects (from this monitor and from URL & Redirect Manager).</p>
        <div class="d-flex align-items-center gap-2 mb-3">
          <Link :href="route('seo.url-redirects')" class="btn btn-outline-primary btn-sm">Open URL & Redirect Manager</Link>
        </div>
        <div class="admin-list-table-wrap" v-if="redirects.length">
          <table class="admin-list-table admin-list-table-sm" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">From</th>
                <th class="admin-url-table-th">To</th>
                <th class="admin-url-table-th">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in redirects" :key="r.id">
                <td><code class="admin-list-code">/{{ r.from_path }}</code></td>
                <td><code class="admin-list-code">/{{ r.to_path }}</code></td>
                <td>{{ r.status_code }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="admin-text-muted p-3 mb-0">No redirects yet.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
