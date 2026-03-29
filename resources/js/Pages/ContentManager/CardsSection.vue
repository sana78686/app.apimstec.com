<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

/** Icon key → emoji (how it looks on the frontend). Used in CMS to preview. */
const ICON_EMOJI = {
  lightning: '⚡',
  quality: '🎚️',
  lock: '🔒',
  star: '✨',
  document: '📄',
  shield: '🛡️',
  heart: '❤️',
  cloud: '☁️',
  download: '⬇️',
  upload: '⬆️',
  check: '✅',
  image: '🖼️',
  'file-plus': '📎',
  layers: '📑',
  sparkle: '✨',
  zap: '⚡',
  settings: '⚙️',
  globe: '🌐',
  mobile: '📱',
  clock: '⏱️',
};

const props = defineProps({
  cards: { type: Array, default: () => [] },
  iconOptions: { type: Object, default: () => ({}) },
  flash: { type: Object, default: () => ({}) },
});

const iconOptionsList = computed(() =>
  Object.entries(props.iconOptions || {}).map(([value, label]) => ({
    value,
    label,
    emoji: ICON_EMOJI[value] ?? '❓',
  }))
);

const showAddModal = ref(false);
const editingId = ref(null);

const addForm = useForm({
  title: '',
  description: '',
  icon: '',
});

const editForm = useForm({
  title: '',
  description: '',
  icon: '',
});

function openAdd() {
  addForm.reset();
  addForm.clearErrors();
  addForm.icon = iconOptionsList.value[0]?.value ?? '';
  showAddModal.value = true;
  editingId.value = null;
}

function openEdit(card) {
  editForm.title = card.title;
  editForm.description = card.description ?? '';
  editForm.icon = card.icon ?? (iconOptionsList.value[0]?.value ?? '');
  editForm.clearErrors();
  editingId.value = card.id;
  showAddModal.value = false;
}

function closeEdit() {
  editingId.value = null;
  editForm.reset();
}

function submitAdd() {
  addForm.post(route('content-manager.cards.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false;
      addForm.reset();
    },
  });
}

function submitEdit() {
  if (!editingId.value) return;
  editForm.put(route('content-manager.cards.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => {
      closeEdit();
    },
  });
}

function remove(card) {
  if (!confirm('Remove this card?')) return;
  router.delete(route('content-manager.cards.destroy', card.id), { preserveScroll: true });
}

function iconLabel(iconKey) {
  return props.iconOptions?.[iconKey] ?? iconKey;
}

function iconEmoji(iconKey) {
  return ICON_EMOJI[iconKey] ?? '❓';
}
</script>

<template>
  <Head title="Cards – Content manager" />

  <AuthenticatedLayout>
    <template #header>Cards</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Why use our PDF compressor?</h1>
        <p class="admin-form-page-desc text-muted small">
          Manage the feature cards (title, description, icon) shown on the home page.
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 mb-0">Cards list</h2>
          <PrimaryButton type="button" class="btn btn-primary btn-sm" @click="openAdd">
            Add new card
          </PrimaryButton>
        </div>

        <div v-if="cards.length" class="row g-3">
          <div
            v-for="card in cards"
            :key="card.id"
            class="col-12 col-md-6"
          >
            <div class="admin-box admin-box-smooth p-3 h-100">
              <div v-if="editingId !== card.id" class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-start gap-2">
                  <span class="card-icon-preview" :title="iconLabel(card.icon)" aria-hidden="true">{{ iconEmoji(card.icon) }}</span>
                  <div>
                    <strong>{{ card.title }}</strong>
                    <p class="text-muted small mb-0 mt-1">{{ card.description }}</p>
                  </div>
                </div>
                <div class="d-flex gap-1">
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="openEdit(card)">Edit</button>
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(card)">Remove</button>
                </div>
              </div>
              <div v-else>
                <div class="mb-2">
                  <label class="form-label small">Title</label>
                  <TextInput v-model="editForm.title" class="form-control form-control-sm" />
                  <InputError :message="editForm.errors.title" />
                </div>
                <div class="mb-2">
                  <label class="form-label small">Description</label>
                  <textarea v-model="editForm.description" class="form-control form-control-sm" rows="2"></textarea>
                  <InputError :message="editForm.errors.description" />
                </div>
                <div class="mb-2">
                  <label class="form-label small">Icon</label>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <select v-model="editForm.icon" class="form-select form-select-sm" style="max-width: 14rem;">
                      <option v-for="opt in iconOptionsList" :key="opt.value" :value="opt.value">
                        {{ opt.emoji }} {{ opt.label }}
                      </option>
                    </select>
                    <span v-if="editForm.icon" class="card-icon-preview" :title="iconLabel(editForm.icon)">{{ iconEmoji(editForm.icon) }}</span>
                  </div>
                  <div class="card-icon-grid card-icon-grid--sm">
                    <button
                      v-for="opt in iconOptionsList"
                      :key="opt.value"
                      type="button"
                      class="card-icon-btn"
                      :class="{ active: editForm.icon === opt.value }"
                      :title="opt.label"
                      @click="editForm.icon = opt.value"
                    >
                      <span class="card-icon-btn-emoji">{{ opt.emoji }}</span>
                    </button>
                  </div>
                  <InputError :message="editForm.errors.icon" />
                </div>
                <div class="d-flex gap-2">
                  <PrimaryButton type="button" class="btn btn-sm" :disabled="editForm.processing" @click="submitEdit">
                    Save
                  </PrimaryButton>
                  <SecondaryButton type="button" class="btn btn-sm btn-outline-secondary" @click="closeEdit">
                    Cancel
                  </SecondaryButton>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-muted small mb-0">No cards yet. Click “Add new card” to create one.</p>
      </div>
    </div>

    <Modal :show="showAddModal" @close="showAddModal = false">
      <div class="p-4">
        <h3 class="h6 mb-3">Add new card</h3>
        <form @submit.prevent="submitAdd">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Title</label>
            <TextInput v-model="addForm.title" class="form-control" placeholder="e.g. Fast compression" />
            <InputError :message="addForm.errors.title" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <textarea v-model="addForm.description" class="form-control" rows="2" placeholder="Short description…"></textarea>
            <InputError :message="addForm.errors.description" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Icon</label>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
              <select v-model="addForm.icon" class="form-select flex-grow-1" style="max-width: 16rem;">
                <option v-for="opt in iconOptionsList" :key="opt.value" :value="opt.value">
                  {{ opt.emoji }} {{ opt.label }}
                </option>
              </select>
              <span v-if="addForm.icon" class="card-icon-preview" :title="iconLabel(addForm.icon)">{{ iconEmoji(addForm.icon) }}</span>
            </div>
            <div class="card-icon-grid">
              <button
                v-for="opt in iconOptionsList"
                :key="opt.value"
                type="button"
                class="card-icon-btn"
                :class="{ active: addForm.icon === opt.value }"
                :title="opt.label"
                @click="addForm.icon = opt.value"
              >
                <span class="card-icon-btn-emoji">{{ opt.emoji }}</span>
              </button>
            </div>
            <InputError :message="addForm.errors.icon" />
          </div>
          <div class="d-flex justify-content-end gap-2">
            <SecondaryButton type="button" class="btn btn-outline-secondary" @click="showAddModal = false">
              Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="addForm.processing">Add card</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
.card-icon-preview {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  line-height: 1;
  min-width: 2rem;
}
.card-icon-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.card-icon-grid--sm .card-icon-btn { padding: 0.25rem; }
.card-icon-grid--sm .card-icon-btn-emoji { font-size: 1rem; }
.card-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0.35rem;
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.card-icon-btn:hover {
  border-color: var(--admin-primary, #4945ff);
  background: rgba(73, 69, 255, 0.06);
}
.card-icon-btn.active {
  border-color: var(--admin-primary, #4945ff);
  background: rgba(73, 69, 255, 0.12);
}
.card-icon-btn-emoji {
  font-size: 1.25rem;
  line-height: 1;
}
</style>
