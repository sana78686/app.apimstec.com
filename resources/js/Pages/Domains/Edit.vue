<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  domain: { type: Object, required: true },
});

const form = useForm({
  name:         props.domain.name,
  domain:       props.domain.domain,
  frontend_url: props.domain.frontend_url ?? '',
  db_host:      props.domain.db_host,
  db_port:      props.domain.db_port,
  db_name:      props.domain.db_name,
  db_username:  props.domain.db_username,
  db_password:  '',          // leave blank to keep existing
  is_active:    props.domain.is_active,
  is_default:   props.domain.is_default,
});

function submit() {
  form.put(route('domains.update', props.domain.id));
}
</script>

<template>
  <Head title="Edit domain" />
  <AuthenticatedLayout>
    <template #header>Edit domain</template>

    <div class="admin-form-page" style="max-width:600px;">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Edit domain</h1>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-3">Website info</h2>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Display name</label>
          <input v-model="form.name" type="text" class="form-control form-control-sm" />
          <InputError :message="form.errors.name" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Domain</label>
          <input v-model="form.domain" type="text" class="form-control form-control-sm" />
          <InputError :message="form.errors.domain" />
        </div>
        <div class="mb-0">
          <label class="form-label small fw-semibold">Frontend URL</label>
          <input v-model="form.frontend_url" type="text" class="form-control form-control-sm" />
          <InputError :message="form.errors.frontend_url" />
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-3">Database credentials</h2>
        <div class="row g-2 mb-3">
          <div class="col-8">
            <label class="form-label small fw-semibold">DB host</label>
            <input v-model="form.db_host" type="text" class="form-control form-control-sm" />
          </div>
          <div class="col-4">
            <label class="form-label small fw-semibold">Port</label>
            <input v-model="form.db_port" type="number" class="form-control form-control-sm" />
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Database name</label>
          <input v-model="form.db_name" type="text" class="form-control form-control-sm" />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">DB username</label>
          <input v-model="form.db_username" type="text" class="form-control form-control-sm" />
        </div>
        <div class="mb-0">
          <label class="form-label small fw-semibold">New DB password</label>
          <input v-model="form.db_password" type="password" class="form-control form-control-sm" autocomplete="new-password" placeholder="Leave blank to keep current" />
          <InputError :message="form.errors.db_password" />
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <div class="form-check mb-2">
          <input v-model="form.is_active" type="checkbox" class="form-check-input" id="is_active" />
          <label class="form-check-label small" for="is_active">Active</label>
        </div>
        <div class="form-check">
          <input v-model="form.is_default" type="checkbox" class="form-check-input" id="is_default" />
          <label class="form-check-label small" for="is_default">Default domain</label>
        </div>
      </div>

      <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
        {{ form.processing ? 'Saving…' : 'Save changes' }}
      </PrimaryButton>
    </div>
  </AuthenticatedLayout>
</template>
