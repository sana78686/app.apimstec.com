<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <Link href="/" class="auth-logo">compressedPDF CMS</Link>
      <div class="auth-slot">
        <slot />
      </div>
    </div>
    <p class="auth-footer">
      Content management for compressedPDF
    </p>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: #f5f5f7;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  padding: 2rem;
}

.auth-logo {
  display: block;
  text-align: center;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a1a1a;
  text-decoration: none;
  margin-bottom: 1.5rem;
}

.auth-logo:hover {
  color: #e03d3d;
}

.auth-slot {
  margin-top: 0.5rem;
}

.auth-footer {
  margin-top: 1.5rem;
  font-size: 0.8125rem;
  color: #888;
}
</style>
