<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  credentials: { type: Object, default: () => ({ data: [], links: [] }) },
  search: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.search ?? '');
watch(() => props.search, (v) => { searchQuery.value = v ?? ''; }, { immediate: true });

const visiblePasswords = reactive({});

function visibleKey(domainId, type, index) {
  return `${domainId}-${type}-${index}`;
}

function togglePassword(id, type, index) {
  const key = visibleKey(id, type, index);
  visiblePasswords[key] = !visiblePasswords[key];
}

function isPasswordVisible(id, type, index) {
  return !!visiblePasswords[visibleKey(id, type, index)];
}

async function copyToClipboard(text, label) {
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: label ? `${label} copied` : 'Copied to clipboard',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true,
    });
  } catch {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Copy failed', showConfirmButton: false, timer: 2000 });
  }
}

function destroy(id) {
  if (!confirm('Delete this credential?')) return;
  router.delete(route('credentials.destroy', { credential: id }), { preserveScroll: true });
}

const credentialList = () => props.credentials?.data ?? [];
const hasPaginator = () => (props.credentials?.last_page ?? 1) > 1;
</script>

<template>
  <Head title="Credential management" />

  <AuthenticatedLayout>
    <template #header>Credential management</template>

    <div class="admin-list-page">
      <p v-if="flash?.success" class="admin-flash admin-flash-success">{{ flash.success }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Credential management</h1>
          <p class="admin-list-page-desc">
            Store and manage domain credentials: email, Plesk, website, and portal access. Each domain can have its own set of credentials. Passwords are encrypted.
          </p>
        </div>
        <Link :href="route('credentials.create')" class="admin-list-page-cta">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="12" y1="18" x2="12" y2="12" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          Add domain
        </Link>
      </div>

      <form class="admin-list-toolbar" @submit.prevent="router.get(route('credentials.index'), searchQuery ? { search: searchQuery } : {}, { preserveState: true })">
        <div class="admin-list-search-wrap">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          <input
            v-model="searchQuery"
            type="search"
            class="admin-list-search"
            placeholder="Search by domain..."
            aria-label="Search domains"
          />
        </div>
        <button type="submit" class="btn btn-sm btn-outline-secondary admin-list-search-btn">Search</button>
      </form>

      <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th>Domain</th>
                <th>Email</th>
                <th>Plesk</th>
                <th>Website</th>
                <th>Portal</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in credentialList()" :key="c.id">
                <td class="admin-cred-domain">
                  <strong>{{ c.domain }}</strong>
                </td>
                <td class="admin-cred-cell">
                  <template v-if="c.email_credentials?.length">
                    <div v-for="(em, ei) in c.email_credentials" :key="ei" class="admin-cred-item">
                      <span class="admin-cred-val">
                        <span>{{ em.username || '—' }}</span>
                        <button v-if="em.username" type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy username" aria-label="Copy username" @click="copyToClipboard(em.username, 'Username')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                      <span v-if="em.password != null" class="admin-cred-val admin-cred-pwd-row">
                        <span>{{ isPasswordVisible(c.id, 'em', ei) ? em.password : '••••••••' }}</span>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" :data-tip="isPasswordVisible(c.id, 'em', ei) ? 'Hide password' : 'Show password'" @click="togglePassword(c.id, 'em', ei)">
                          <svg v-if="!isPasswordVisible(c.id, 'em', ei)" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                          <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy password" aria-label="Copy password" @click="copyToClipboard(em.password, 'Password')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                    </div>
                  </template>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="admin-cred-cell">
                  <template v-if="c.plesk_credentials?.length">
                    <div v-for="(pl, pi) in c.plesk_credentials" :key="pi" class="admin-cred-item">
                      <span class="admin-cred-val">
                        <span>{{ pl.username || '—' }}</span>
                        <button v-if="pl.username" type="button" class="admin-cred-btn admin-cred-tooltip" data-tip="Copy username" @click="copyToClipboard(pl.username, 'Username')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                      <span v-if="pl.password != null" class="admin-cred-val admin-cred-pwd-row">
                        <span>{{ isPasswordVisible(c.id, 'pl', pi) ? pl.password : '••••••••' }}</span>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" :data-tip="isPasswordVisible(c.id, 'pl', pi) ? 'Hide password' : 'Show password'" :aria-label="isPasswordVisible(c.id, 'pl', pi) ? 'Hide password' : 'Show password'" @click="togglePassword(c.id, 'pl', pi)">
                          <svg v-if="!isPasswordVisible(c.id, 'pl', pi)" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                          <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy password" aria-label="Copy password" @click="copyToClipboard(pl.password, 'Password')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                    </div>
                  </template>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="admin-cred-cell">
                  <template v-if="c.website_credentials?.length">
                    <div v-for="(wb, wi) in c.website_credentials" :key="wi" class="admin-cred-item">
                      <span class="admin-cred-val">
                        <span>{{ wb.username || '—' }}</span>
                        <button v-if="wb.username" type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy username" aria-label="Copy username" @click="copyToClipboard(wb.username, 'Username')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                      <span v-if="wb.password != null" class="admin-cred-val admin-cred-pwd-row">
                        <span>{{ isPasswordVisible(c.id, 'web', wi) ? wb.password : '••••••••' }}</span>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" :data-tip="isPasswordVisible(c.id, 'web', wi) ? 'Hide password' : 'Show password'" :aria-label="isPasswordVisible(c.id, 'web', wi) ? 'Hide password' : 'Show password'" @click="togglePassword(c.id, 'web', wi)">
                          <svg v-if="!isPasswordVisible(c.id, 'web', wi)" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                          <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy password" aria-label="Copy password" @click="copyToClipboard(wb.password, 'Password')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                    </div>
                  </template>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="admin-cred-cell">
                  <template v-if="c.portal_credentials?.length">
                    <div v-for="(por, poi) in c.portal_credentials" :key="poi" class="admin-cred-item">
                      <span class="admin-cred-val">
                        <a v-if="por.url" :href="por.url" target="_blank" rel="noopener noreferrer" class="admin-cred-link">{{ por.url }}</a>
                        <span v-else>—</span>
                      </span>
                      <span class="admin-cred-val">
                        <span>{{ por.username || '—' }}</span>
                        <button v-if="por.username" type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy username" aria-label="Copy username" @click="copyToClipboard(por.username, 'Username')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                      <span v-if="por.password != null" class="admin-cred-val admin-cred-pwd-row">
                        <span>{{ isPasswordVisible(c.id, 'por', poi) ? por.password : '••••••••' }}</span>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" :data-tip="isPasswordVisible(c.id, 'por', poi) ? 'Hide password' : 'Show password'" :aria-label="isPasswordVisible(c.id, 'por', poi) ? 'Hide password' : 'Show password'" @click="togglePassword(c.id, 'por', poi)">
                          <svg v-if="!isPasswordVisible(c.id, 'por', poi)" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                          <svg v-else xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                        <button type="button" class="admin-cred-btn admin-cred-tooltip" tabindex="0" data-tip="Copy password" aria-label="Copy password" @click="copyToClipboard(por.password, 'Password')">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                      </span>
                    </div>
                  </template>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="admin-cred-actions">
                  <Link :href="route('credentials.edit', { credential: c.id })" class="admin-list-link me-2">Edit</Link>
                  <button type="button" class="admin-list-link admin-list-link-danger" @click="destroy(c.id)">
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!credentialList().length" class="admin-text-muted" style="padding: 1.5rem;">
            {{ props.search ? 'No domains match your search.' : 'No credentials yet. Add a domain to get started.' }}
          </p>
        <nav v-if="hasPaginator()" class="admin-pagination" aria-label="Credential pages">
          <div class="admin-pagination-info">
            Showing {{ credentials.from ?? 0 }}–{{ credentials.to ?? 0 }} of {{ credentials.total ?? 0 }}
          </div>
          <ul class="admin-pagination-links">
            <li v-for="(link, idx) in credentials.links" :key="idx">
              <Link
                v-if="link.url"
                :href="link.url"
                class="admin-pagination-link"
                :class="{ 'admin-pagination-link-active': link.active }"
                v-html="link.label"
              />
              <span v-else class="admin-pagination-link admin-pagination-link-disabled" v-html="link.label" />
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.admin-cred-domain { font-weight: 500; }
.admin-cred-cell { font-size: 0.875rem; }
.admin-cred-item { margin-bottom: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem 0.75rem; align-items: center; }
.admin-cred-item:last-child { margin-bottom: 0; }
.admin-cred-val { display: inline-flex; align-items: center; gap: 0.25rem; }
.admin-cred-val.admin-cred-pwd-row { color: var(--bs-secondary); font-size: 0.8rem; font-family: ui-monospace, monospace; }
.admin-cred-btn {
  display: inline-flex; align-items: center; justify-content: center; padding: 0.2rem;
  background: transparent; border: none; border-radius: 4px; color: var(--bs-secondary);
  cursor: pointer; transition: color 0.15s, background 0.15s;
}
.admin-cred-btn:hover { color: var(--admin-primary, #4945ff); background: rgba(0,0,0,0.06); }
/* Styled tooltips for copy/eye buttons (match admin-tooltip-trigger design) */
.admin-cred-tooltip { position: relative; }
.admin-cred-tooltip::before {
  content: attr(data-tip);
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.5rem);
  transform: translateX(-50%) scale(0.96);
  min-width: 6rem;
  max-width: 14rem;
  padding: 0.4rem 0.6rem;
  font-size: 0.6875rem;
  font-weight: 500;
  line-height: 1.3;
  color: #fff;
  background: #181826;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  white-space: nowrap;
  text-align: center;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
  z-index: 1000;
}
.admin-cred-tooltip::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.2rem);
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: #181826;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s;
  z-index: 1001;
}
.admin-cred-tooltip:hover::before,
.admin-cred-tooltip:focus::before,
.admin-cred-tooltip:hover::after,
.admin-cred-tooltip:focus::after {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) scale(1);
}
.admin-cred-tooltip:focus::after { transform: translateX(-50%); }
.admin-cred-tooltip:focus { outline: none; }
.admin-cred-link { color: var(--admin-primary, #4945ff); text-decoration: none; }
.admin-cred-link:hover { text-decoration: underline; }
.admin-cred-actions .admin-list-link-danger { color: var(--bs-danger); }
.admin-cred-actions .admin-list-link-danger:hover { color: var(--bs-danger); text-decoration: underline; }

.admin-list-search-btn { margin-left: 0.25rem; }

/* Pagination */
.admin-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--admin-card-border, #eaeaef);
}
.admin-pagination-info {
  font-size: 0.75rem;
  color: var(--admin-text-muted, #666687);
}
.admin-pagination-links {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
.admin-pagination-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  padding: 0.35rem 0.5rem;
  font-size: 0.75rem;
  color: var(--admin-primary, #4945ff);
  text-decoration: none;
  border-radius: 4px;
  transition: background 0.15s, color 0.15s;
}
.admin-pagination-link:hover { background: rgba(73, 69, 255, 0.1); color: var(--admin-primary); }
.admin-pagination-link-active {
  background: var(--admin-primary, #4945ff);
  color: #fff !important;
}
.admin-pagination-link-active:hover { background: var(--admin-primary-hover, #3232a8); color: #fff !important; }
.admin-pagination-link-disabled {
  color: var(--admin-text-muted) !important;
  cursor: default;
  pointer-events: none;
}
</style>
