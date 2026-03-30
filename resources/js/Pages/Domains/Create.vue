<script setup>
import DomainSetupLayout from '@/Layouts/DomainSetupLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
  name:         '',
  domain:       '',
  frontend_url: '',
  db_host:      '127.0.0.1',
  db_port:      3306,
  db_name:      '',
  db_username:  '',
  db_password:  '',
  is_default:   false,
});

function submit() {
  form.post(route('domains.store'));
}

/* ── Password visibility ── */
const showPass = ref(false);

/* ── Test connection ── */
const testing    = ref(false);
const testResult = ref(null); // { success, message }

async function testConnection() {
  testing.value    = true;
  testResult.value = null;
  try {
    const res = await fetch(route('domains.test-connection'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept':       'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
      body: JSON.stringify({
        db_host:     form.db_host,
        db_port:     form.db_port,
        db_name:     form.db_name,
        db_username: form.db_username,
        db_password: form.db_password,
      }),
    });
    testResult.value = await res.json();
  } catch (e) {
    testResult.value = { success: false, message: 'Request failed: ' + e.message };
  } finally {
    testing.value = false;
  }
}
</script>

<template>
  <Head title="Add domain" />
  <DomainSetupLayout>
    <div class="admin-form-page" style="max-width:600px;margin:0 auto;">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Add domain</h1>
        <p class="admin-form-page-desc text-muted small">
          Connect a new website to this CMS. Each domain uses its own Plesk database.
        </p>
      </div>

      <!-- Website info -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-3">Website info</h2>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Display name <span class="text-danger">*</span></label>
          <input v-model="form.name" type="text" class="form-control form-control-sm" placeholder="e.g. CompressPDF" />
          <InputError :message="form.errors.name" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Domain <span class="text-danger">*</span></label>
          <input v-model="form.domain" type="text" class="form-control form-control-sm" placeholder="e.g. compresspdf.id" />
          <div class="form-text">Used as the <code>X-Domain</code> header by the React frontend.</div>
          <InputError :message="form.errors.domain" />
        </div>
        <div class="mb-0">
          <label class="form-label small fw-semibold">Frontend URL</label>
          <input v-model="form.frontend_url" type="text" class="form-control form-control-sm" placeholder="https://compresspdf.id" />
          <InputError :message="form.errors.frontend_url" />
        </div>
      </div>

      <!-- DB credentials -->
      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-3">Database credentials (Plesk)</h2>

        <div class="row g-2 mb-3">
          <div class="col-8">
            <label class="form-label small fw-semibold">DB host <span class="text-danger">*</span></label>
            <input v-model="form.db_host" type="text" class="form-control form-control-sm" placeholder="127.0.0.1" />
            <InputError :message="form.errors.db_host" />
          </div>
          <div class="col-4">
            <label class="form-label small fw-semibold">Port</label>
            <input v-model="form.db_port" type="number" class="form-control form-control-sm" />
            <InputError :message="form.errors.db_port" />
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Database name <span class="text-danger">*</span></label>
          <input v-model="form.db_name" type="text" class="form-control form-control-sm" placeholder="e.g. compresspdf_db" />
          <InputError :message="form.errors.db_name" />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">DB username <span class="text-danger">*</span></label>
          <input v-model="form.db_username" type="text" class="form-control form-control-sm" />
          <InputError :message="form.errors.db_username" />
        </div>

        <!-- Password with eye toggle -->
        <div class="mb-3">
          <label class="form-label small fw-semibold">DB password <span class="text-danger">*</span></label>
          <div class="input-group input-group-sm">
            <input
              v-model="form.db_password"
              :type="showPass ? 'text' : 'password'"
              class="form-control form-control-sm"
              autocomplete="new-password"
            />
            <button
              type="button"
              class="btn btn-outline-secondary"
              :title="showPass ? 'Hide password' : 'Show password'"
              @click="showPass = !showPass"
            >
              <!-- Eye open -->
              <svg v-if="!showPass" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <!-- Eye off -->
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="form-text">Stored encrypted.</div>
          <InputError :message="form.errors.db_password" />
        </div>

        <!-- Test connection -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button
            type="button"
            class="btn btn-sm btn-outline-primary"
            :disabled="testing || !form.db_host || !form.db_name || !form.db_username || !form.db_password"
            title="Test if these database credentials can actually connect"
            @click="testConnection"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ testing ? 'Testing…' : 'Test connection' }}
          </button>

          <!-- Result badge -->
          <span
            v-if="testResult"
            class="badge"
            :class="testResult.success ? 'bg-success' : 'bg-danger'"
            style="font-size:.75rem;max-width:400px;white-space:normal;text-align:left;"
          >
            {{ testResult.success ? '✓' : '✗' }} {{ testResult.message }}
          </span>
        </div>

        <InputError :message="form.errors.db_connection" class="mt-2" />
      </div>

      <!-- Default flag -->
      <div class="admin-box admin-box-smooth mb-4">
        <div class="form-check">
          <input v-model="form.is_default" type="checkbox" class="form-check-input" id="is_default" />
          <label class="form-check-label small" for="is_default">Set as default domain for this CMS</label>
        </div>
      </div>

      <PrimaryButton
        type="button"
        class="btn btn-primary btn-sm"
        :disabled="form.processing"
        @click="submit"
      >
        {{ form.processing ? 'Saving…' : 'Add domain' }}
      </PrimaryButton>
    </div>
  </DomainSetupLayout>
</template>
