<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const ICON_EMOJI = {
  lightning: '⚡', quality: '🎚️', lock: '🔒', star: '✨', document: '📄', shield: '🛡️',
  heart: '❤️', cloud: '☁️', download: '⬇️', upload: '⬆️', check: '✅', image: '🖼️',
  'file-plus': '📎', layers: '📑', sparkle: '✨', zap: '⚡', settings: '⚙️',
  globe: '🌐', mobile: '📱', clock: '⏱️',
};

const props = defineProps({
  faqItems: { type: Array, default: () => [] },
  cards: { type: Array, default: () => [] },
  iconOptions: { type: Object, default: () => ({}) },
  activeTab: { type: String, default: 'faq' }, // from URL: faq | use-cards
  flash: { type: Object, default: () => ({}) },
});

const page = usePage();

/** Prefer path segment so the correct panel shows even if Ziggy/route props were wrong */
const effectiveTab = computed(() => {
  const u = String(page.url || '');
  if (u.includes('/home/use-cards')) return 'use-cards';
  if (u.includes('/home/faq')) return 'faq';
  return props.activeTab === 'use-cards' ? 'use-cards' : 'faq';
});

const iconOptionsList = computed(() =>
  Object.entries(props.iconOptions || {}).map(([value, label]) => ({
    value,
    label,
    emoji: ICON_EMOJI[value] ?? '❓',
  }))
);

function truncate(str, len = 60) {
  if (!str || str.length <= len) return str;
  return str.slice(0, len) + '…';
}

// FAQ
const showFaqModal = ref(false);
const faqEditingId = ref(null);
const faqAddForm = useForm({ question: '', answer: '' });
const faqEditForm = useForm({ question: '', answer: '' });

function openFaqAdd() {
  faqAddForm.reset();
  faqAddForm.clearErrors();
  showFaqModal.value = true;
  faqEditingId.value = null;
}
function openFaqEdit(item) {
  faqEditForm.question = item.question;
  faqEditForm.answer = item.answer;
  faqEditForm.clearErrors();
  faqEditingId.value = item.id;
  showFaqModal.value = false;
}
function closeFaqEdit() {
  faqEditingId.value = null;
  faqEditForm.reset();
}
function submitFaqAdd() {
  faqAddForm.post(route('content-manager.faq.store'), {
    preserveScroll: true,
    onSuccess: () => { showFaqModal.value = false; faqAddForm.reset(); },
  });
}
function submitFaqEdit() {
  if (!faqEditingId.value) return;
  faqEditForm.put(route('content-manager.faq.update', faqEditingId.value), {
    preserveScroll: true,
    onSuccess: () => closeFaqEdit(),
  });
}
function removeFaq(item) {
  if (!confirm('Remove this FAQ?')) return;
  router.delete(route('content-manager.faq.destroy', item.id), { preserveScroll: true });
}

// Cards
const showCardModal = ref(false);
const cardEditingId = ref(null);
const cardAddForm = useForm({ title: '', description: '', icon: '' });
const cardEditForm = useForm({ title: '', description: '', icon: '' });

function openCardAdd() {
  cardAddForm.reset();
  cardAddForm.clearErrors();
  cardAddForm.icon = iconOptionsList.value[0]?.value ?? '';
  showCardModal.value = true;
  cardEditingId.value = null;
}
function openCardEdit(card) {
  cardEditForm.title = card.title;
  cardEditForm.description = card.description ?? '';
  cardEditForm.icon = card.icon ?? (iconOptionsList.value[0]?.value ?? '');
  cardEditForm.clearErrors();
  cardEditingId.value = card.id;
  showCardModal.value = false;
}
function closeCardEdit() {
  cardEditingId.value = null;
  cardEditForm.reset();
}
function submitCardAdd() {
  cardAddForm.post(route('content-manager.cards.store'), {
    preserveScroll: true,
    onSuccess: () => { showCardModal.value = false; cardAddForm.reset(); },
  });
}
function submitCardEdit() {
  if (!cardEditingId.value) return;
  cardEditForm.put(route('content-manager.cards.update', cardEditingId.value), {
    preserveScroll: true,
    onSuccess: () => closeCardEdit(),
  });
}
function removeCard(card) {
  if (!confirm('Remove this card?')) return;
  router.delete(route('content-manager.cards.destroy', card.id), { preserveScroll: true });
}
function iconEmoji(key) {
  return ICON_EMOJI[key] ?? '❓';
}
function iconLabel(key) {
  return props.iconOptions?.[key] ?? key;
}
</script>

<template>
  <Head :title="effectiveTab === 'use-cards' ? 'Use cards – Home' : 'FAQ – Home'" />

  <AuthenticatedLayout>
    <template #header>Home page</template>

    <div class="admin-form-page content-manager-home">
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <!-- Tabs as links: clicking = navigate (like hitting API), URL updates -->
      <ul class="content-manager-home-tabs" role="tablist">
        <li role="presentation">
          <Link
            :href="route('content-manager.home', { tab: 'faq' })"
            class="content-manager-home-tab"
            :class="{ active: effectiveTab === 'faq' }"
            role="tab"
            :aria-selected="effectiveTab === 'faq'"
          >
            FAQ
          </Link>
        </li>
        <li role="presentation">
          <Link
            :href="route('content-manager.home', { tab: 'use-cards' })"
            class="content-manager-home-tab"
            :class="{ active: effectiveTab === 'use-cards' }"
            role="tab"
            :aria-selected="effectiveTab === 'use-cards'"
          >
            Use cards
          </Link>
        </li>
      </ul>

      <div class="content-manager-home-panel">
        <!-- FAQ tab panel -->
        <div v-show="effectiveTab === 'faq'" class="admin-list-page">
          <div class="admin-list-page-header">
            <div>
              <h1 class="admin-list-page-title">FAQ</h1>
              <p class="admin-list-page-desc">Frequently asked questions shown on the home page.</p>
            </div>
            <PrimaryButton type="button" class="admin-list-page-cta btn btn-primary btn-sm" @click="openFaqAdd">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
              Add FAQ
            </PrimaryButton>
          </div>
          <div class="admin-list-table-wrap">
            <table class="admin-list-table" role="grid">
              <thead>
                <tr><th>Question</th><th>Answer</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <tr v-for="item in faqItems" :key="item.id">
                  <template v-if="faqEditingId === item.id">
                    <td colspan="3">
                      <div class="d-flex flex-column gap-2">
                        <TextInput v-model="faqEditForm.question" class="form-control form-control-sm" placeholder="Question" />
                        <textarea v-model="faqEditForm.answer" class="form-control form-control-sm" rows="2" placeholder="Answer"></textarea>
                        <div class="d-flex gap-2">
                          <PrimaryButton type="button" class="btn btn-sm" :disabled="faqEditForm.processing" @click="submitFaqEdit">Save</PrimaryButton>
                          <SecondaryButton type="button" class="btn btn-sm btn-outline-secondary" @click="closeFaqEdit">Cancel</SecondaryButton>
                        </div>
                      </div>
                    </td>
                  </template>
                  <template v-else>
                    <td>{{ item.question }}</td>
                    <td><span class="admin-list-text-muted">{{ truncate(item.answer, 80) }}</span></td>
                    <td>
                      <button type="button" class="admin-list-link" @click="openFaqEdit(item)">Edit</button>
                      <button type="button" class="admin-list-link admin-list-link-danger" @click="removeFaq(item)">Delete</button>
                    </td>
                  </template>
                </tr>
              </tbody>
            </table>
            <p v-if="!faqItems.length" class="admin-text-muted" style="padding: 1.5rem;">No FAQs yet. Click “Add FAQ” to create one.</p>
          </div>
        </div>

        <!-- Use cards tab panel -->
        <div v-show="effectiveTab === 'use-cards'" class="admin-list-page">
          <div class="admin-list-page-header">
            <div>
              <h1 class="admin-list-page-title">Use cards</h1>
              <p class="admin-list-page-desc">Feature cards (e.g. “Why use our PDF compressor?”) on the home page.</p>
            </div>
            <PrimaryButton type="button" class="admin-list-page-cta btn btn-primary btn-sm" @click="openCardAdd">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
              Add card
            </PrimaryButton>
          </div>
          <div class="admin-list-table-wrap">
            <table class="admin-list-table" role="grid">
              <thead>
                <tr><th style="width: 3rem;">Icon</th><th>Title</th><th>Description</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <tr v-for="card in cards" :key="card.id">
                  <td v-if="cardEditingId !== card.id">
                    <span class="card-icon-cell" :title="iconLabel(card.icon)">{{ iconEmoji(card.icon) }}</span>
                  </td>
                  <td v-if="cardEditingId !== card.id">{{ card.title }}</td>
                  <td v-if="cardEditingId !== card.id">
                    <span class="admin-list-text-muted">{{ truncate(card.description, 80) }}</span>
                  </td>
                  <td v-if="cardEditingId !== card.id">
                    <button type="button" class="admin-list-link" @click="openCardEdit(card)">Edit</button>
                    <button type="button" class="admin-list-link admin-list-link-danger" @click="removeCard(card)">Delete</button>
                  </td>
                  <td v-else colspan="4">
                    <div class="d-flex flex-column gap-2">
                      <div><label class="form-label small">Title</label><TextInput v-model="cardEditForm.title" class="form-control form-control-sm" /></div>
                      <div><label class="form-label small">Description</label><textarea v-model="cardEditForm.description" class="form-control form-control-sm" rows="2"></textarea></div>
                      <div><label class="form-label small">Icon</label>
                        <select v-model="cardEditForm.icon" class="form-select form-select-sm" style="max-width: 14rem;">
                          <option v-for="opt in iconOptionsList" :key="opt.value" :value="opt.value">{{ opt.emoji }} {{ opt.label }}</option>
                        </select>
                      </div>
                      <div class="d-flex gap-2">
                        <PrimaryButton type="button" class="btn btn-sm" :disabled="cardEditForm.processing" @click="submitCardEdit">Save</PrimaryButton>
                        <SecondaryButton type="button" class="btn btn-sm btn-outline-secondary" @click="closeCardEdit">Cancel</SecondaryButton>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="!cards.length" class="admin-text-muted" style="padding: 1.5rem;">No cards yet. Click “Add card” to create one.</p>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showFaqModal" @close="showFaqModal = false">
      <div class="p-4">
        <h3 class="h6 mb-3">Add new FAQ</h3>
        <form @submit.prevent="submitFaqAdd">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Question</label>
            <TextInput v-model="faqAddForm.question" class="form-control" placeholder="e.g. Is it free?" />
            <InputError :message="faqAddForm.errors.question" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Answer</label>
            <textarea v-model="faqAddForm.answer" class="form-control" rows="3" placeholder="Your answer…"></textarea>
            <InputError :message="faqAddForm.errors.answer" />
          </div>
          <div class="d-flex justify-content-end gap-2">
            <SecondaryButton type="button" class="btn btn-outline-secondary" @click="showFaqModal = false">Cancel</SecondaryButton>
            <PrimaryButton type="submit" :disabled="faqAddForm.processing">Add FAQ</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <Modal :show="showCardModal" @close="showCardModal = false">
      <div class="p-4">
        <h3 class="h6 mb-3">Add new card</h3>
        <form @submit.prevent="submitCardAdd">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Title</label>
            <TextInput v-model="cardAddForm.title" class="form-control" placeholder="e.g. Fast compression" />
            <InputError :message="cardAddForm.errors.title" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <textarea v-model="cardAddForm.description" class="form-control" rows="2" placeholder="Short description…"></textarea>
            <InputError :message="cardAddForm.errors.description" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Icon</label>
            <select v-model="cardAddForm.icon" class="form-select">
              <option v-for="opt in iconOptionsList" :key="opt.value" :value="opt.value">{{ opt.emoji }} {{ opt.label }}</option>
            </select>
            <InputError :message="cardAddForm.errors.icon" />
          </div>
          <div class="d-flex justify-content-end gap-2">
            <SecondaryButton type="button" class="btn btn-outline-secondary" @click="showCardModal = false">Cancel</SecondaryButton>
            <PrimaryButton type="submit" :disabled="cardAddForm.processing">Add card</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
.content-manager-home-tabs {
  display: flex;
  gap: 0;
  list-style: none;
  margin: 0 0 1.25rem 0;
  padding: 0;
  border-bottom: 1px solid var(--admin-card-border, #eaeaef);
}
.content-manager-home-tab {
  padding: 0.5rem 1.25rem;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  text-decoration: none;
  color: var(--admin-text-muted, #666687);
  font-weight: 500;
  font-size: 0.9375rem;
  border-radius: 0;
  transition: color 0.15s, border-color 0.15s;
}
.content-manager-home-tab:hover {
  color: var(--admin-text, #32324d);
}
.content-manager-home-tab.active {
  color: var(--admin-primary, #4945ff);
  border-bottom-color: var(--admin-primary, #4945ff);
}
.content-manager-home-panel { padding-top: 0.25rem; }
.content-manager-home .admin-list-page-header { margin-bottom: 1rem; }
.card-icon-cell { font-size: 1.25rem; line-height: 1; }
.admin-list-text-muted { color: var(--admin-text-muted, #666687); font-size: 0.875rem; }
</style>
