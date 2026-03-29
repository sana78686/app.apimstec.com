<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  credential: { type: Object, required: true },
});

const form = useForm({
  domain: props.credential.domain ?? '',
  email_credentials: (props.credential.email_credentials ?? []).length
    ? props.credential.email_credentials
    : [{ username: '', password: '' }],
  plesk_credentials: (props.credential.plesk_credentials ?? []).length
    ? props.credential.plesk_credentials
    : [{ username: '', password: '' }],
  website_credentials: (props.credential.website_credentials ?? []).length
    ? props.credential.website_credentials
    : [{ username: '', password: '' }],
  portal_credentials: (props.credential.portal_credentials ?? []).length
    ? props.credential.portal_credentials
    : [{ url: '', username: '', password: '' }],
  notes: props.credential.notes ?? '',
});

watch(
  () => props.credential,
  (c) => {
    form.domain = c.domain ?? '';
    form.email_credentials = (c.email_credentials ?? []).length ? c.email_credentials : [{ username: '', password: '' }];
    form.plesk_credentials = (c.plesk_credentials ?? []).length ? c.plesk_credentials : [{ username: '', password: '' }];
    form.website_credentials = (c.website_credentials ?? []).length ? c.website_credentials : [{ username: '', password: '' }];
    form.portal_credentials = (c.portal_credentials ?? []).length ? c.portal_credentials : [{ url: '', username: '', password: '' }];
    form.notes = c.notes ?? '';
  },
  { deep: true }
);

function addEmail() {
  form.email_credentials = [...form.email_credentials, { username: '', password: '' }];
}
function removeEmail(i) {
  if (form.email_credentials.length > 1) {
    form.email_credentials = form.email_credentials.filter((_, idx) => idx !== i);
  }
}
function addPlesk() {
  form.plesk_credentials = [...form.plesk_credentials, { username: '', password: '' }];
}
function removePlesk(i) {
  if (form.plesk_credentials.length > 1) {
    form.plesk_credentials = form.plesk_credentials.filter((_, idx) => idx !== i);
  }
}
function addWebsite() {
  form.website_credentials = [...form.website_credentials, { username: '', password: '' }];
}
function removeWebsite(i) {
  if (form.website_credentials.length > 1) {
    form.website_credentials = form.website_credentials.filter((_, idx) => idx !== i);
  }
}
function addPortal() {
  form.portal_credentials = [...form.portal_credentials, { url: '', username: '', password: '' }];
}
function removePortal(i) {
  if (form.portal_credentials.length > 1) {
    form.portal_credentials = form.portal_credentials.filter((_, idx) => idx !== i);
  }
}
</script>

<template>
  <Head :title="'Edit ' + (credential?.domain || 'credentials')" />

  <AuthenticatedLayout>
    <template #header>Edit domain credentials</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Edit: {{ credential.domain }}</h1>
          <p class="admin-form-page-desc text-muted small">
            Update credentials. Add or remove entries with + / −. Leave password as •••••••• to keep existing.
          </p>
        </div>
        <div class="admin-form-page-top-actions">
          <Link :href="route('credentials.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
          <PrimaryButton type="submit" form="edit-credential-form" :disabled="form.processing" class="btn btn-primary btn-sm admin-btn-smooth">Update</PrimaryButton>
        </div>
      </div>

      <form id="edit-credential-form" class="admin-box admin-box-smooth" @submit.prevent="form.put(route('credentials.update', credential.id))">
        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <h2 class="admin-form-page-title admin-form-page-title-sm mb-0" style="font-size: 1rem;">Domain</h2>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="Enter the domain name for this credential set (e.g. compresspdf.id, example.com).">?</span>
            </div>
          </div>
          <div class="credential-section-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="domain" class="form-label small fw-semibold">Domain <span class="text-danger">*</span></label>
              <TextInput
                id="domain"
                v-model="form.domain"
                class="form-control form-control-sm"
                placeholder="e.g. compresspdf.id"
                required
              />
              <InputError :message="form.errors.domain" />
            </div>
          </div>
          </div>
        </div>
        <hr class="credential-section-line" />

        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <h2 class="admin-form-page-title admin-form-page-title-sm mb-0" style="font-size: 1rem;">Email credentials</h2>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="Email account logins (e.g. admin@domain.com, support@domain.com). Add multiple if you have several mailboxes.">?</span>
            </div>
          </div>
          <div class="credential-section-body">
            <div class="credential-row credential-row-header">
              <div class="row g-2">
                <div class="col-md-5"><span class="credential-col-label">Username</span></div>
                <div class="col-md-5"><span class="credential-col-label">Password</span></div>
                <div class="col-md-2"></div>
              </div>
            </div>
            <div v-for="(item, i) in form.email_credentials" :key="'em-' + i" class="credential-row">
              <div class="row g-2 align-items-center">
                <div class="col-md-5">
                  <TextInput v-model="form.email_credentials[i].username" class="form-control form-control-sm" placeholder="e.g. admin@domain.com" />
                </div>
                <div class="col-md-5">
                  <TextInput v-model="form.email_credentials[i].password" type="password" class="form-control form-control-sm" placeholder="•••••••• to keep" autocomplete="new-password" />
                </div>
                <div class="col-md-2 credential-row-actions">
                  <button v-if="i === form.email_credentials.length - 1" type="button" class="btn btn-outline-primary btn-sm credential-add-btn me-1" @click="addEmail">+ Add</button>
                  <button type="button" class="btn btn-outline-danger btn-sm credential-remove-btn" :disabled="form.email_credentials.length <= 1" @click="removeEmail(i)">− Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="credential-section-line" />
        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <h2 class="admin-form-page-title admin-form-page-title-sm mb-0" style="font-size: 1rem;">Plesk credentials</h2>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="Plesk panel login(s) for hosting management. Add one per user or environment (e.g. admin, developer).">?</span>
            </div>
          </div>
          <div class="credential-section-body">
            <div class="credential-row credential-row-header">
              <div class="row g-2">
                <div class="col-md-5"><span class="credential-col-label">Username</span></div>
                <div class="col-md-5"><span class="credential-col-label">Password</span></div>
                <div class="col-md-2"></div>
              </div>
            </div>
            <div v-for="(item, i) in form.plesk_credentials" :key="'pl-' + i" class="credential-row">
              <div class="row g-2 align-items-center">
                <div class="col-md-5">
                  <TextInput v-model="form.plesk_credentials[i].username" class="form-control form-control-sm" placeholder="Plesk login" />
                </div>
                <div class="col-md-5">
                  <TextInput v-model="form.plesk_credentials[i].password" type="password" class="form-control form-control-sm" placeholder="•••••••• to keep" autocomplete="new-password" />
                </div>
                <div class="col-md-2 credential-row-actions">
                  <button v-if="i === form.plesk_credentials.length - 1" type="button" class="btn btn-outline-primary btn-sm credential-add-btn me-1" @click="addPlesk">+ Add</button>
                  <button type="button" class="btn btn-outline-danger btn-sm credential-remove-btn" :disabled="form.plesk_credentials.length <= 1" @click="removePlesk(i)">− Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="credential-section-line" />
        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <h2 class="admin-form-page-title admin-form-page-title-sm mb-0" style="font-size: 1rem;">Website credentials</h2>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="CMS, admin panel, or website login credentials (e.g. WordPress, Drupal). Add multiple for different roles.">?</span>
            </div>
          </div>
          <div class="credential-section-body">
            <div class="credential-row credential-row-header">
              <div class="row g-2">
                <div class="col-md-5"><span class="credential-col-label">Username</span></div>
                <div class="col-md-5"><span class="credential-col-label">Password</span></div>
                <div class="col-md-2"></div>
              </div>
            </div>
            <div v-for="(item, i) in form.website_credentials" :key="'web-' + i" class="credential-row">
              <div class="row g-2 align-items-center">
                <div class="col-md-5">
                  <TextInput v-model="form.website_credentials[i].username" class="form-control form-control-sm" placeholder="e.g. admin" />
                </div>
                <div class="col-md-5">
                  <TextInput v-model="form.website_credentials[i].password" type="password" class="form-control form-control-sm" placeholder="•••••••• to keep" autocomplete="new-password" />
                </div>
                <div class="col-md-2 credential-row-actions">
                  <button v-if="i === form.website_credentials.length - 1" type="button" class="btn btn-outline-primary btn-sm credential-add-btn me-1" @click="addWebsite">+ Add</button>
                  <button type="button" class="btn btn-outline-danger btn-sm credential-remove-btn" :disabled="form.website_credentials.length <= 1" @click="removeWebsite(i)">− Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="credential-section-line" />
        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <h2 class="admin-form-page-title admin-form-page-title-sm mb-0" style="font-size: 1rem;">Portal credentials (with link)</h2>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="Third‑party portals with direct link: registrar, SSL provider, analytics, etc. Enter the full URL and login.">?</span>
            </div>
          </div>
          <div class="credential-section-body">
            <div class="credential-row credential-row-header">
              <div class="row g-2">
                <div class="col-12"><span class="credential-col-label">Portal link (URL)</span></div>
                <div class="col-md-5"><span class="credential-col-label">Username</span></div>
                <div class="col-md-5"><span class="credential-col-label">Password</span></div>
                <div class="col-md-2"></div>
              </div>
            </div>
            <div v-for="(item, i) in form.portal_credentials" :key="'por-' + i" class="credential-row">
              <div class="row g-2 align-items-center">
                <div class="col-12">
                  <TextInput v-model="form.portal_credentials[i].url" type="url" class="form-control form-control-sm" placeholder="https://portal.example.com" />
                </div>
                <div class="col-md-5">
                  <TextInput v-model="form.portal_credentials[i].username" class="form-control form-control-sm" placeholder="Portal login" />
                </div>
                <div class="col-md-5">
                  <TextInput v-model="form.portal_credentials[i].password" type="password" class="form-control form-control-sm" placeholder="•••••••• to keep" autocomplete="new-password" />
                </div>
                <div class="col-md-2 credential-row-actions">
                  <button v-if="i === form.portal_credentials.length - 1" type="button" class="btn btn-outline-primary btn-sm credential-add-btn me-1" @click="addPortal">+ Add</button>
                  <button type="button" class="btn btn-outline-danger btn-sm credential-remove-btn" :disabled="form.portal_credentials.length <= 1" @click="removePortal(i)">− Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="credential-section-line" />
        <div class="credential-section">
          <div class="credential-section-head">
            <div class="d-flex align-items-center gap-2">
              <label for="notes" class="form-label small fw-semibold mb-0">Notes (optional)</label>
              <span class="credential-tooltip" tabindex="0" aria-label="Help" data-tip="Optional remarks: expiry dates, 2FA notes, contact person, etc.">?</span>
            </div>
          </div>
          <div class="credential-section-body">
          <textarea id="notes" v-model="form.notes" class="form-control form-control-sm" rows="3" placeholder="Extra notes about this domain" />
          <InputError :message="form.errors.notes" />
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <Link :href="route('credentials.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
          <PrimaryButton type="submit" :disabled="form.processing" class="btn btn-primary btn-sm admin-btn-smooth">Update credentials</PrimaryButton>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.credential-row {
  padding: 0.25rem 0;
}
.credential-row-header {
  padding-bottom: 0.15rem;
  margin-bottom: 0.15rem;
}
.credential-row-header .credential-col-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--admin-text-muted, #666687);
}
.credential-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}
.credential-remove-btn {
  white-space: nowrap;
}
.credential-remove-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.credential-row-actions {
  padding-bottom: 0.25rem;
}
.credential-section {
  margin-bottom: 0;
  padding: 0.5rem 0;
}
.credential-section-head {
  margin-bottom: 0.5rem;
}
.credential-section-body {
  padding-left: 0;
}
.credential-section-line {
  margin: 1rem 0;
  border: none;
  border-top: 1px solid rgba(0, 0, 0, 0.12);
}
.credential-tooltip {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.125rem;
  height: 1.125rem;
  font-size: 0.625rem;
  font-weight: 600;
  color: var(--admin-text-muted, #666687);
  background: var(--admin-main-bg, #f6f6f9);
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 50%;
  cursor: help;
  transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}
.credential-tooltip:hover,
.credential-tooltip:focus {
  color: var(--admin-text, #32324d);
  border-color: var(--admin-primary, #181826);
  outline: none;
}
.credential-tooltip::before {
  content: attr(data-tip);
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.5rem);
  transform: translateX(-50%) scale(0.96);
  min-width: 10rem;
  max-width: 20rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 400;
  line-height: 1.4;
  color: #fff;
  background: #181826;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: normal;
  text-align: left;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
  z-index: 1000;
}
.credential-tooltip::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.25rem);
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #181826;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s;
  z-index: 1001;
}
.credential-tooltip:hover::before,
.credential-tooltip:focus::before,
.credential-tooltip:hover::after,
.credential-tooltip:focus::after {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) scale(1);
}
.credential-tooltip:focus::after {
  transform: translateX(-50%);
}
</style>
