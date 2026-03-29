<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

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
</script>

<template>
  <Head title="Add domain" />
  <AuthenticatedLayout>
    <template #header>Add domain</template>

    <div class="admin-form-page" style="max-width:600px;">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Add domain</h1>
        <p class="admin-form-page-desc text-muted small">Connect a new website to this CMS. Each domain uses its own Plesk database.</p>
      </div>

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
        <div class="mb-0">
          <label class="form-label small fw-semibold">DB password <span class="text-danger">*</span></label>
          <input v-model="form.db_password" type="password" class="form-control form-control-sm" autocomplete="new-password" />
          <div class="form-text">Stored encrypted. Never shown again.</div>
          <InputError :message="form.errors.db_password" />
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <div class="form-check">
          <input v-model="form.is_default" type="checkbox" class="form-check-input" id="is_default" />
          <label class="form-check-label small" for="is_default">Set as default domain for this CMS</label>
        </div>
      </div>

      <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
        {{ form.processing ? 'Saving…' : 'Add domain' }}
      </PrimaryButton>
    </div>
  </AuthenticatedLayout>
</template>
