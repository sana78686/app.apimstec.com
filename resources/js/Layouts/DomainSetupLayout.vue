<script setup>
/**
 * Minimal shell for domain onboarding only (no CMS sidebar).
 * Used when no tenant is selected — user may only connect a database or sign out.
 */
import { Link } from '@inertiajs/vue3';

import 'bootstrap/dist/css/bootstrap.min.css';
import '../../css/admin.css';
</script>

<template>
  <div class="setup-wrap">
    <header class="setup-topbar">
      <span class="setup-brand">
        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Global CMS
      </span>
      <div class="setup-topbar-actions">
        <Link :href="route('domains.select')" class="setup-link">Back to website picker</Link>
        <Link :href="route('logout')" method="post" as="button" class="setup-logout">Sign out</Link>
      </div>
    </header>
    <main class="setup-main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.setup-wrap {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f4f5f7;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.setup-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 1.5rem;
  background: #fff;
  border-bottom: 1px solid #eaeaef;
}
.setup-brand {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 700;
  font-size: 0.9375rem;
  color: #4945ff;
  letter-spacing: -0.2px;
}
.setup-topbar-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.setup-link {
  font-size: 0.8125rem;
  color: #4945ff;
  text-decoration: none;
}
.setup-link:hover {
  text-decoration: underline;
}
.setup-logout {
  font-size: 0.8125rem;
  color: #666;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
}
.setup-logout:hover {
  background: #f0f0f5;
}
.setup-main {
  flex: 1;
  padding: 1.5rem 1rem 2rem;
}
</style>
