<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Media library" />

  <AuthenticatedLayout>
    <template #header>Media library</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Media library</h1>
        <p class="admin-form-page-desc text-muted small">
          Upload and manage images and files.
        </p>
      </div>
      <div class="admin-box admin-box-smooth">
        <p class="admin-text-muted small mb-0">
          Media library area. You can add upload and listing features here later.
        </p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
