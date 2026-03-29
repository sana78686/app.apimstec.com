<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
  userId: { type: [Number, String], required: true },
});

const roles = ref([]);
const isDesignatedAdmin = ref(false);
const loading = ref(true);
const processing = ref(false);
const loadError = ref('');
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [],
});
const errors = reactive({});

const assignableRoles = computed(() => roles.value.filter((r) => r.slug !== 'admin'));
const adminRole = computed(() => roles.value.find((r) => r.slug === 'admin'));
const userIdNum = computed(() => Number(props.userId) || 0);

onMounted(async () => {
  const id = userIdNum.value;
  if (!id) {
    loadError.value = 'Invalid user.';
    loading.value = false;
    return;
  }
  try {
    const { data } = await window.axios.get(`/api/users/${id}/edit`);
    form.name = data.user?.name ?? '';
    form.email = data.user?.email ?? '';
    form.roles = Array.isArray(data.user?.roles) ? [...data.user.roles] : [];
    roles.value = data.roles ?? [];
    isDesignatedAdmin.value = !!data.is_designated_admin;
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Failed to load user.';
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
    await window.axios.put(`/api/users/${userIdNum.value}`, form);
    router.visit(route('users.index') + '?success=updated');
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
  <Head title="Edit user" />

  <AuthenticatedLayout>
    <template #header>Edit user</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Edit user</h1>
          <p class="admin-form-page-desc text-muted small">Update user details and roles. At least one role is required.</p>
        </div>
        <div v-if="!loading && !loadError" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="edit-user-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <div v-else-if="loadError" key="error" class="text-danger small mb-0">
            {{ loadError }}
            <Link :href="route('users.index')" class="ms-2">Back to users</Link>
          </div>
          <form id="edit-user-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
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
              <LabelWithTooltip for="password" value="New password" tip="Leave blank to keep current password." optional />
              <TextInput id="password" type="password" v-model="form.password" autocomplete="new-password" class="form-control form-control-sm" />
              <InputError :message="errors.password?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="password_confirmation" value="Confirm new password" tip="Re-enter the new password to confirm." optional />
              <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" autocomplete="new-password" class="form-control form-control-sm" />
              <InputError :message="errors.password_confirmation?.[0]" />
            </div>
          </div>
          <div class="mb-3">
            <LabelWithTooltip value="Roles" tip="Assign at least one role. Users cannot have no role." required />
            <template v-if="isDesignatedAdmin && adminRole">
              <div class="form-check mb-1">
                <input type="checkbox" class="form-check-input" checked disabled />
                <label class="form-check-label small text-muted">{{ adminRole.name }} (only this account)</label>
              </div>
            </template>
            <div class="row g-2">
              <div v-for="r in assignableRoles" :key="r.id" class="col-md-6 col-lg-4">
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
            <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Update user</PrimaryButton>
          </div>
        </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
