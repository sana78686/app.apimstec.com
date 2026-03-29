<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  roleId: { type: [Number, String], required: true },
});

const permissions = ref([]);
const loading = ref(true);
const processing = ref(false);
const form = reactive({
  name: '',
  slug: '',
  description: '',
  permissions: [],
});
const errors = reactive({});
const roleData = ref(null);

const allPermissionIds = computed(() => permissions.value.map((p) => p.id));
const permissionsByGroup = computed(() => {
  const map = {};
  (permissions.value || []).forEach((p) => {
    const g = p.group || 'general';
    if (!map[g]) map[g] = [];
    map[g].push(p);
  });
  return map;
});
const isSystemRole = computed(() => roleData.value?.is_system === true);

function selectAllPermissions() {
  form.permissions = [...allPermissionIds.value];
}

function unselectAllPermissions() {
  form.permissions = [];
}

onMounted(async () => {
  try {
    const { data } = await window.axios.get(`/api/roles/${props.roleId}/edit`);
    roleData.value = data.role;
    form.name = data.role?.name ?? '';
    form.slug = data.role?.slug ?? '';
    form.description = data.role?.description ?? '';
    form.permissions = (data.role?.permissions || []).map((p) => p.id);
    permissions.value = data.permissions ?? [];
  } catch (e) {
    if (e.response?.status === 403) {
      roleData.value = {};
      form.name = '';
      form.slug = '';
      form.description = '';
      form.permissions = [];
      permissions.value = [];
    }
  } finally {
    loading.value = false;
  }
});

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    await window.axios.put(`/api/roles/${props.roleId}`, form);
    router.visit(route('roles.index') + '?success=updated');
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
  <Head title="Edit role" />

  <AuthenticatedLayout>
    <template #header>Edit role</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Edit role</h1>
          <p class="admin-form-page-desc text-muted small">Update role name, slug, and permissions.</p>
        </div>
        <div v-if="!loading && !isSystemRole" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="edit-role-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <form id="edit-role-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="name" value="Name" tip="Display name for this role." required />
                <TextInput id="name" v-model="form.name" required :disabled="isSystemRole" class="form-control form-control-sm" />
                <InputError :message="errors.name?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="slug" value="Slug" tip="Unique identifier. Cannot be changed for system roles." required />
                <TextInput id="slug" v-model="form.slug" required :disabled="isSystemRole" class="form-control form-control-sm" />
                <InputError :message="errors.slug?.[0]" />
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="description" value="Description" tip="Optional short description of this role." optional />
                <textarea id="description" v-model="form.description" class="form-control form-control-sm" rows="3" :disabled="isSystemRole"></textarea>
                <InputError :message="errors.description?.[0]" />
              </div>
            </div>
          <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <LabelWithTooltip value="Permissions" tip="What this role can do. System Admin role has all permissions and cannot be changed." required />
              <div v-if="!isSystemRole" class="small">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" style="color: var(--admin-primary);" @click="selectAllPermissions">Select all</button>
                <span class="text-muted">|</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none ms-2" style="color: var(--admin-primary);" @click="unselectAllPermissions">Unselect all</button>
              </div>
            </div>
            <p v-if="isSystemRole" class="small text-muted">System role (Admin) has all permissions and cannot be changed.</p>
            <div v-else class="row g-2">
              <div v-for="(perms, group) in permissionsByGroup" :key="group" class="col-md-6 col-lg-4">
                <div class="small fw-semibold text-uppercase text-muted mb-1">{{ group }}</div>
                <div class="form-check form-check-inline d-block mb-1" v-for="p in perms" :key="p.id">
                  <input :id="`perm-${p.id}`" type="checkbox" :value="p.id" v-model="form.permissions" class="form-check-input" />
                  <label :for="`perm-${p.id}`" class="form-check-label small">{{ p.name }}</label>
                </div>
              </div>
            </div>
            <InputError :message="errors.permissions?.[0]" />
          </div>
          <InputError v-if="errors.form" :message="errors.form" />
          <div class="d-flex gap-2">
            <Link :href="route('roles.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
            <PrimaryButton v-if="!isSystemRole" type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Update role</PrimaryButton>
          </div>
        </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
