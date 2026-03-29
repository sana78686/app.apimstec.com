<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  domains:        { type: Array,  default: () => [] },
  activeDomainId: { type: Number, default: null },
})

/* ── Domain selection ── */
function pick(id) {
  router.post(route('domains.switch'), { domain_id: id, redirect: 'dashboard' })
}

/* ── Add-domain modal ── */
const showModal = ref(false)

const form = useForm({
  name:         '',
  domain:       '',
  frontend_url: '',
  db_host:      '127.0.0.1',
  db_port:      3306,
  db_name:      '',
  db_username:  '',
  db_password:  '',
  auto_select:  true,
})

function openModal()  { showModal.value = true }
function closeModal() { showModal.value = false; form.reset(); form.clearErrors() }

function submit() {
  form.post(route('domains.store'), {
    onSuccess: () => closeModal(),
  })
}
</script>

<template>
  <div class="sel-wrap">

    <!-- ── Top bar ── -->
    <header class="sel-topbar">
      <span class="sel-brand">
        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Global CMS
      </span>
      <Link :href="route('logout')" method="post" as="button" class="sel-logout">Sign out</Link>
    </header>

    <!-- ── Body ── -->
    <main class="sel-body">

      <!-- ══ NO DOMAINS ══ -->
      <div v-if="!domains.length" class="sel-empty-wrap">
        <div class="sel-empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <h1 class="sel-empty-title">No websites connected yet</h1>
        <p class="sel-empty-sub">Add your first website to start managing its SEO content through this CMS.</p>
        <button class="sel-btn sel-btn--primary" @click="openModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add your first domain
        </button>
      </div>

      <!-- ══ HAS DOMAINS ══ -->
      <div v-else class="sel-panel">
        <div class="sel-panel-head">
          <div>
            <h1 class="sel-title">Select a website</h1>
            <p class="sel-sub">Choose which website you want to manage. All edits will be saved to that website's database.</p>
          </div>
          <button class="sel-btn sel-btn--primary" @click="openModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add domain
          </button>
        </div>

        <div class="sel-grid">
          <button
            v-for="d in domains"
            :key="d.id"
            class="sel-card"
            :class="{ 'sel-card--active': d.id === activeDomainId }"
            @click="pick(d.id)"
          >
            <div class="sel-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
            <div class="sel-card-info">
              <span class="sel-card-name">{{ d.name }}</span>
              <span class="sel-card-url">{{ d.domain }}</span>
            </div>
            <span v-if="d.id === activeDomainId" class="sel-card-badge">Last used</span>
            <svg class="sel-card-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

    </main>

    <!-- ══ ADD DOMAIN MODAL ══ -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @mousedown.self="closeModal">
        <div class="modal-box" role="dialog" aria-modal="true">

          <div class="modal-head">
            <h2 class="modal-title">Add a new domain</h2>
            <button type="button" class="modal-close" @click="closeModal" aria-label="Close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <form class="modal-form" @submit.prevent="submit">

            <!-- Website info -->
            <p class="modal-section-label">Website</p>
            <div class="modal-row modal-row--2">
              <div class="modal-field">
                <label class="modal-label">Site name <span class="req">*</span></label>
                <input v-model="form.name" type="text" class="modal-input" :class="{ 'is-err': form.errors.name }" placeholder="e.g. Compress PDF" />
                <p v-if="form.errors.name" class="modal-err">{{ form.errors.name }}</p>
              </div>
              <div class="modal-field">
                <label class="modal-label">Domain <span class="req">*</span></label>
                <input v-model="form.domain" type="text" class="modal-input" :class="{ 'is-err': form.errors.domain }" placeholder="e.g. compresspdf.id" />
                <p v-if="form.errors.domain" class="modal-err">{{ form.errors.domain }}</p>
              </div>
            </div>
            <div class="modal-field">
              <label class="modal-label">Frontend URL <span class="modal-optional">(optional)</span></label>
              <input v-model="form.frontend_url" type="text" class="modal-input" placeholder="https://compresspdf.id" />
            </div>

            <!-- DB credentials -->
            <p class="modal-section-label" style="margin-top:1.25rem;">Database credentials (Plesk)</p>
            <div class="modal-row modal-row--3">
              <div class="modal-field" style="flex:2;">
                <label class="modal-label">DB Host <span class="req">*</span></label>
                <input v-model="form.db_host" type="text" class="modal-input" :class="{ 'is-err': form.errors.db_host }" placeholder="127.0.0.1" />
                <p v-if="form.errors.db_host" class="modal-err">{{ form.errors.db_host }}</p>
              </div>
              <div class="modal-field" style="flex:1;">
                <label class="modal-label">Port <span class="req">*</span></label>
                <input v-model="form.db_port" type="number" class="modal-input" :class="{ 'is-err': form.errors.db_port }" />
                <p v-if="form.errors.db_port" class="modal-err">{{ form.errors.db_port }}</p>
              </div>
            </div>
            <div class="modal-field">
              <label class="modal-label">Database name <span class="req">*</span></label>
              <input v-model="form.db_name" type="text" class="modal-input" :class="{ 'is-err': form.errors.db_name }" placeholder="compresspdf_db" />
              <p v-if="form.errors.db_name" class="modal-err">{{ form.errors.db_name }}</p>
            </div>
            <div class="modal-row modal-row--2">
              <div class="modal-field">
                <label class="modal-label">DB Username <span class="req">*</span></label>
                <input v-model="form.db_username" type="text" class="modal-input" :class="{ 'is-err': form.errors.db_username }" placeholder="db_user" />
                <p v-if="form.errors.db_username" class="modal-err">{{ form.errors.db_username }}</p>
              </div>
              <div class="modal-field">
                <label class="modal-label">DB Password <span class="req">*</span></label>
                <input v-model="form.db_password" type="password" class="modal-input" :class="{ 'is-err': form.errors.db_password }" />
                <p v-if="form.errors.db_password" class="modal-err">{{ form.errors.db_password }}</p>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="sel-btn sel-btn--ghost" @click="closeModal">Cancel</button>
              <button type="submit" class="sel-btn sel-btn--primary" :disabled="form.processing">
                <span v-if="form.processing">Saving…</span>
                <span v-else>Add &amp; select domain</span>
              </button>
            </div>

          </form>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<style scoped>
/* ── Outer wrap ── */
.sel-wrap {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f4f5f7;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ── Top bar ── */
.sel-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .7rem 1.5rem;
  background: #fff;
  border-bottom: 1px solid #eaeaef;
}
.sel-brand {
  display: flex;
  align-items: center;
  gap: .4rem;
  font-weight: 700;
  font-size: .9375rem;
  color: #4945ff;
  letter-spacing: -.2px;
}
.sel-logout {
  font-size: .8125rem;
  color: #666;
  background: none;
  border: none;
  cursor: pointer;
  padding: .3rem .6rem;
  border-radius: 6px;
  transition: background .15s;
  text-decoration: none;
}
.sel-logout:hover { background: #f0f0f5; }

/* ── Body ── */
.sel-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

/* ── Empty state ── */
.sel-empty-wrap {
  text-align: center;
  max-width: 400px;
}
.sel-empty-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #4945ff;
  opacity: .6;
}
.sel-empty-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #181826;
  margin: 0 0 .5rem;
}
.sel-empty-sub {
  font-size: .875rem;
  color: #666;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

/* ── Panel (when domains exist) ── */
.sel-panel {
  background: #fff;
  border: 1px solid #eaeaef;
  border-radius: 14px;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
  width: 100%;
  max-width: 680px;
  overflow: hidden;
}
.sel-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.75rem 2rem 1.25rem;
  border-bottom: 1px solid #f0f0f5;
}
.sel-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #181826;
  margin: 0 0 .3rem;
}
.sel-sub {
  font-size: .8125rem;
  color: #666;
  margin: 0;
  line-height: 1.5;
  max-width: 380px;
}

/* ── Domain grid ── */
.sel-grid {
  padding: 1rem 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}

/* ── Domain card ── */
.sel-card {
  display: flex;
  align-items: center;
  gap: .85rem;
  padding: .875rem 1rem;
  border: 1.5px solid #eaeaef;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  width: 100%;
  text-align: left;
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.sel-card:hover {
  border-color: #4945ff;
  background: #f8f8ff;
  box-shadow: 0 2px 10px rgba(73,69,255,.08);
}
.sel-card--active {
  border-color: #4945ff;
  background: #f3f2ff;
}
.sel-card-icon {
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
.sel-card-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .12rem;
}
.sel-card-name {
  font-size: .9375rem;
  font-weight: 600;
  color: #181826;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sel-card-url {
  font-size: .78rem;
  color: #888;
}
.sel-card-badge {
  font-size: .68rem;
  font-weight: 600;
  background: #4945ff;
  color: #fff;
  border-radius: 20px;
  padding: .15rem .5rem;
  white-space: nowrap;
  flex-shrink: 0;
}
.sel-card-arrow {
  color: #ccc;
  flex-shrink: 0;
}

/* ── Buttons ── */
.sel-btn {
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
  white-space: nowrap;
  flex-shrink: 0;
}
.sel-btn--primary { background: #4945ff; color: #fff; }
.sel-btn--primary:hover:not(:disabled) { opacity: .87; }
.sel-btn--primary:disabled { opacity: .5; cursor: not-allowed; }
.sel-btn--ghost { background: none; color: #555; border: 1.5px solid #ddd; }
.sel-btn--ghost:hover { background: #f4f5f7; }

/* ────────────────────────────────────────────
   MODAL
──────────────────────────────────────────── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}
.modal-box {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
  display: flex;
  flex-direction: column;
}
.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem 1rem;
  border-bottom: 1px solid #f0f0f5;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 1;
}
.modal-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #181826;
  margin: 0;
}
.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  color: #888;
  padding: .25rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  transition: background .15s;
}
.modal-close:hover { background: #f0f0f5; color: #333; }

.modal-form {
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: .75rem;
}
.modal-section-label {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #999;
  margin: 0;
}
.modal-row { display: flex; gap: .75rem; }
.modal-row--2 > * { flex: 1; }
.modal-row--3 > * { flex: 1; }
.modal-field {
  display: flex;
  flex-direction: column;
  gap: .3rem;
}
.modal-label {
  font-size: .8125rem;
  font-weight: 500;
  color: #444;
}
.req { color: #e05; }
.modal-optional { color: #aaa; font-weight: 400; }
.modal-input {
  padding: .5rem .75rem;
  border: 1.5px solid #e0e0ea;
  border-radius: 8px;
  font-size: .875rem;
  outline: none;
  transition: border-color .15s;
  width: 100%;
  box-sizing: border-box;
}
.modal-input:focus { border-color: #4945ff; }
.modal-input.is-err { border-color: #e05; }
.modal-err {
  font-size: .75rem;
  color: #e05;
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  margin-top: .5rem;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f5;
}
</style>
