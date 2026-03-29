<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

function selectAllPermissions() {
  form.permissions = [...allPermissionIds.value];
}

function unselectAllPermissions() {
  form.permissions = [];
}

onMounted(async () => {
  try {
    const { data } = await window.axios.get('/api/roles/create');
    permissions.value = data.permissions ?? [];
  } finally {
    loading.value = false;
  }
});

function slugFromName() {
  if (!form.name) return;
  form.slug = form.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
}

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    await window.axios.post('/api/roles', form);
    router.visit(route('roles.index') + '?success=created');
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
  <Head title="Add role" />

  <AuthenticatedLayout>
    <template #header>Add role</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Add role</h1>
          <p class="admin-form-page-desc text-muted small">Create a new role and assign permissions.</p>
        </div>
        <div v-if="!loading" class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="create-role-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <Transition name="admin-fade" mode="out-in">
          <div v-if="loading" key="loading" class="admin-loading-placeholder">
            <span class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></span>
            <span class="ms-2 text-muted small">Loading…</span>
          </div>
          <form id="create-role-form" v-else key="form" @submit.prevent="submit" class="admin-form-smooth">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <LabelWithTooltip for="name" value="Name" tip="Display name for this role (e.g. Content Editor)." required />
                <TextInput id="name" v-model="form.name" required @blur="slugFromName" class="form-control form-control-sm" />
                <InputError :message="errors.name?.[0]" />
              </div>
              <div class="col-md-6">
                <LabelWithTooltip for="slug" value="Slug" tip="Unique identifier used in code (lowercase, hyphens)." required />
                <TextInput id="slug" v-model="form.slug" required class="form-control form-control-sm" />
                <InputError :message="errors.slug?.[0]" />
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <LabelWithTooltip for="description" value="Description" tip="Optional short description of what this role is for." optional />
                <textarea id="description" v-model="form.description" class="form-control form-control-sm" rows="3"></textarea>
                <InputError :message="errors.description?.[0]" />
              </div>
            </div>
          <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <LabelWithTooltip value="Permissions" tip="Choose what this role can do." required />
              <div class="small">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" style="color: var(--admin-primary);" @click="selectAllPermissions">Select all</button>
                <span class="text-muted">|</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none ms-2" style="color: var(--admin-primary);" @click="unselectAllPermissions">Unselect all</button>
              </div>
            </div>
            <div class="row g-2">
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
            <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Create role</PrimaryButton>
          </div>
        </form>
        </Transition>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
