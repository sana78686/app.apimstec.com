<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  moduleName: { type: String, required: true },
  purpose: { type: String, default: '' },
});
</script>

<template>
  <Head :title="moduleName" />

  <AuthenticatedLayout>
    <template #header>{{ moduleName }}</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">{{ moduleName }}</h1>
        <p v-if="purpose" class="admin-form-page-desc text-muted small">{{ purpose }}</p>
        <p class="text-muted small mt-2 mb-0">This module will be implemented next. Use the SEO menu in the sidebar to switch between modules.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
