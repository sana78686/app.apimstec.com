<script setup>
import { router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  domains: { type: Array, default: () => [] },
  activeDomainId: { type: Number, default: null },
})

function pick(id) {
  router.post(route('domains.switch'), { domain_id: id, redirect: 'dashboard' })
}

function useMaster() {
  router.post(route('domains.switch'), { domain_id: null, redirect: 'dashboard' })
}
</script>

<template>
  <div class="ds-wrap">
    <!-- Header bar -->
    <div class="ds-topbar">
      <span class="ds-topbar-brand">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Global CMS
      </span>
      <Link :href="route('logout')" method="post" as="button" class="ds-logout-btn">
        Log out
      </Link>
    </div>

    <!-- Main content -->
    <div class="ds-body">
      <div class="ds-card">
        <div class="ds-card-head">
          <h1 class="ds-title">Select a website to manage</h1>
          <p class="ds-sub">Choose a domain to work on. All CMS data will be saved to that website's database.</p>
        </div>

        <!-- Empty state -->
        <div v-if="!domains.length" class="ds-empty">
          <div class="ds-empty-icon">🌐</div>
          <p class="ds-empty-text">No websites added yet.</p>
          <p class="ds-empty-hint">Add your first domain to get started.</p>
        </div>

        <!-- Domain grid -->
        <div v-else class="ds-grid">
          <button
            v-for="d in domains"
            :key="d.id"
            class="ds-domain-card"
            :class="{ 'ds-domain-card--active': d.id === activeDomainId }"
            @click="pick(d.id)"
          >
            <div class="ds-domain-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
            <div class="ds-domain-info">
              <span class="ds-domain-name">{{ d.name }}</span>
              <span class="ds-domain-url">{{ d.domain }}</span>
            </div>
            <span v-if="d.id === activeDomainId" class="ds-domain-badge">Active</span>
            <svg class="ds-domain-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>

        <!-- Footer actions -->
        <div class="ds-footer">
          <Link :href="route('domains.create')" class="ds-btn ds-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add new domain
          </Link>
          <button type="button" class="ds-btn ds-btn--ghost" @click="useMaster">
            Continue with master DB
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Layout ── */
.ds-wrap {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f4f5f7;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ── Top bar ── */
.ds-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1.5rem;
  background: #fff;
  border-bottom: 1px solid #eaeaef;
}
.ds-topbar-brand {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-weight: 700;
  font-size: 1rem;
  color: #4945ff;
  letter-spacing: -.3px;
}
.ds-logout-btn {
  font-size: .8125rem;
  color: #666;
  background: none;
  border: none;
  cursor: pointer;
  padding: .3rem .6rem;
  border-radius: 6px;
  transition: background .15s;
}
.ds-logout-btn:hover { background: #f0f0f5; }

/* ── Body ── */
.ds-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1rem;
}

/* ── Main card ── */
.ds-card {
  background: #fff;
  border: 1px solid #eaeaef;
  border-radius: 14px;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
  width: 100%;
  max-width: 660px;
  overflow: hidden;
}
.ds-card-head {
  padding: 2rem 2rem 1.25rem;
  border-bottom: 1px solid #f0f0f5;
}
.ds-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #181826;
  margin: 0 0 .4rem;
}
.ds-sub {
  font-size: .875rem;
  color: #666;
  margin: 0;
}

/* ── Empty state ── */
.ds-empty {
  padding: 3rem 2rem;
  text-align: center;
}
.ds-empty-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.ds-empty-text { font-size: 1rem; font-weight: 600; color: #333; margin: 0; }
.ds-empty-hint { font-size: .85rem; color: #888; margin: .25rem 0 0; }

/* ── Domain grid ── */
.ds-grid {
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.ds-domain-card {
  display: flex;
  align-items: center;
  gap: .9rem;
  padding: .875rem 1rem;
  border: 1.5px solid #eaeaef;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  width: 100%;
  text-align: left;
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.ds-domain-card:hover {
  border-color: #4945ff;
  background: #f8f8ff;
  box-shadow: 0 2px 10px rgba(73,69,255,.08);
}
.ds-domain-card--active {
  border-color: #4945ff;
  background: #f3f2ff;
}
.ds-domain-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #f0f0fa;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #4945ff;
}
.ds-domain-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .15rem;
}
.ds-domain-name {
  font-size: .9375rem;
  font-weight: 600;
  color: #181826;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ds-domain-url {
  font-size: .78rem;
  color: #888;
}
.ds-domain-badge {
  font-size: .7rem;
  font-weight: 600;
  background: #4945ff;
  color: #fff;
  border-radius: 20px;
  padding: .15rem .5rem;
  white-space: nowrap;
  flex-shrink: 0;
}
.ds-domain-arrow {
  color: #bbb;
  flex-shrink: 0;
}

/* ── Footer ── */
.ds-footer {
  padding: 1.25rem 2rem;
  border-top: 1px solid #f0f0f5;
  display: flex;
  align-items: center;
  gap: .75rem;
  flex-wrap: wrap;
}
.ds-btn {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .5rem 1.1rem;
  border-radius: 8px;
  font-size: .875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  text-decoration: none;
  transition: opacity .15s, background .15s;
}
.ds-btn--primary {
  background: #4945ff;
  color: #fff;
}
.ds-btn--primary:hover { opacity: .88; }
.ds-btn--ghost {
  background: none;
  color: #666;
  border: 1.5px solid #eaeaef;
}
.ds-btn--ghost:hover { background: #f4f5f7; }
</style>
