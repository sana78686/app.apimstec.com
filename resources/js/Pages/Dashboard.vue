<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth?.user ?? {};
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>Dashboard</template>

    <h2 class="admin-welcome">Welcome back, {{ user.name }}</h2>
    <p class="admin-welcome-desc">
      Here’s an overview of your CMS. Use the sidebar to manage content and settings.
    </p>

    <div class="admin-cards">
      <div class="admin-card">
        <div class="admin-card-title">Content</div>
        <div class="admin-card-value">—</div>
      </div>
      <div class="admin-card">
        <div class="admin-card-title">Pages</div>
        <div class="admin-card-value">—</div>
      </div>
      <div class="admin-card">
        <div class="admin-card-title">Users</div>
        <div class="admin-card-value admin-card-accent">1</div>
      </div>
    </div>

    <div class="admin-box">
      <h3 class="admin-box-title">Getting started</h3>
      <div class="admin-box-body">
        You’re logged in to compressedPDF CMS. Add more sections and links in the sidebar as you build out your content management features.
      </div>
    </div>
  </AuthenticatedLayout>
</template>
