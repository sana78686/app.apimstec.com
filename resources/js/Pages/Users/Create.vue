<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const roles = ref([]);
const loading = ref(true);
const processing = ref(false);
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [],
});
const errors = reactive({});

onMounted(async () => {
  try {
    const { data } = await window.axios.get('/api/users/create');
    roles.value = data.roles ?? [];
  } finally {
    loading.value = false;
  }
});

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  if (!form.roles?.length) {
    errors.roles = ['The user must have at least one role.'];
    processing.value = false;
    return;
  }
  try {
    await window.axios.post('/api/users', form);
    router.visit(route('users.index') + '?success=created');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.form = e.response?.data?.message || 'Something went wrong.';
    }
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <Head title="Invite new user" />

  <AuthenticatedLayout>
    <template #header>Invite new user</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Invite new user</h1>
          <p class="admin-form-page-desc text-muted small">Send an invitation to add a new user to the admin panel.</p>
        </div>
        <div v-if="!loading" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="create-user-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <form id="create-user-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <LabelWithTooltip for="name" value="Name" tip="Full display name for this user." required />
              <TextInput id="name" v-model="form.name" required class="form-control form-control-sm" />
              <InputError :message="errors.name?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="email" value="Email" tip="Login email. Must be unique." required />
              <TextInput id="email" type="email" v-model="form.email" required class="form-control form-control-sm" />
              <InputError :message="errors.email?.[0]" />
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <LabelWithTooltip for="password" value="Password" tip="Secure password. Must meet complexity rules." />
              <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password" class="form-control form-control-sm" />
              <InputError :message="errors.password?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="password_confirmation" value="Confirm password" tip="Re-enter the password to confirm." required />
              <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password" class="form-control form-control-sm" />
              <InputError :message="errors.password_confirmation?.[0]" />
            </div>
          </div>
          <div class="mb-3">
            <LabelWithTooltip value="Roles" tip="Assign at least one role. Users cannot have no role." required />
            <div class="row g-2">
              <div v-for="r in roles" :key="r.id" class="col-md-6 col-lg-4">
                <div class="form-check">
                  <input :id="`role-${r.id}`" type="checkbox" :value="r.id" v-model="form.roles" class="form-check-input" />
                  <label :for="`role-${r.id}`" class="form-check-label small">{{ r.name }} <span v-if="r.is_system" class="text-muted">(System)</span></label>
                </div>
              </div>
            </div>
            <InputError :message="errors.roles?.[0]" />
          </div>
          <InputError v-if="errors.form" :message="errors.form" />
          <div class="d-flex gap-2">
            <Link :href="route('users.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
            <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Invite user</PrimaryButton>
          </div>
        </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
