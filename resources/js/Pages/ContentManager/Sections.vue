<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CmsLocaleSelect from '@/Components/CmsLocaleSelect.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  sections: { type: Array, default: () => [] },
  cmsLocale: { type: String, default: 'en' },
  flash: { type: Object, default: () => ({}) },
});

const showSectionModal = ref(false);
const editingSectionId = ref(null);
const editingItemId = ref(null);

const sectionForm = useForm({
  locale: props.cmsLocale || 'en',
  title: '',
  description: '',
  layout: 'cards',
  is_active: true,
});

const itemForm = useForm({
  section_id: null,
  title: '',
  body: '',
  item_type: 'card',
  media_type: 'none',
  media_value: '',
  is_active: true,
});

const ICON_OPTIONS = [
  { value: 'fa-solid fa-upload', label: 'Upload' },
  { value: 'fa-solid fa-download', label: 'Download' },
  { value: 'fa-solid fa-file-pdf', label: 'PDF file' },
  { value: 'fa-solid fa-file-arrow-down', label: 'File download' },
  { value: 'fa-solid fa-file-arrow-up', label: 'File upload' },
  { value: 'fa-solid fa-bolt', label: 'Fast / lightning' },
  { value: 'fa-solid fa-shield-halved', label: 'Security shield' },
  { value: 'fa-solid fa-lock', label: 'Lock' },
  { value: 'fa-solid fa-globe', label: 'Global / world' },
  { value: 'fa-solid fa-clock', label: 'Clock / time' },
  { value: 'fa-solid fa-gears', label: 'Settings' },
  { value: 'fa-solid fa-star', label: 'Star' },
  { value: 'fa-solid fa-check', label: 'Check' },
  { value: 'fa-solid fa-circle-info', label: 'Info' },
  { value: 'fa-solid fa-rocket', label: 'Rocket' },
  { value: 'fa-solid fa-mobile-screen', label: 'Mobile' },
  { value: 'fa-solid fa-cloud-arrow-up', label: 'Cloud upload' },
  { value: 'fa-solid fa-cloud-arrow-down', label: 'Cloud download' },
  { value: 'fa-solid fa-heart', label: 'Heart' },
  { value: 'fa-solid fa-image', label: 'Image' },
];

function openSectionAdd() {
  editingSectionId.value = null;
  sectionForm.reset();
  sectionForm.clearErrors();
  sectionForm.locale = props.cmsLocale || 'en';
  sectionForm.layout = 'cards';
  sectionForm.is_active = true;
  showSectionModal.value = true;
}

function openSectionEdit(section) {
  editingSectionId.value = section.id;
  sectionForm.locale = section.locale || props.cmsLocale || 'en';
  sectionForm.title = section.title || '';
  sectionForm.description = section.description || '';
  sectionForm.layout = section.layout || 'cards';
  sectionForm.is_active = !!section.is_active;
  sectionForm.clearErrors();
  showSectionModal.value = true;
}

function saveSection() {
  if (editingSectionId.value) {
    sectionForm.put(route('content-manager.sections.update', { section: editingSectionId.value }), {
      preserveScroll: true,
      onSuccess: () => { showSectionModal.value = false; },
    });
    return;
  }
  sectionForm.post(route('content-manager.sections.store'), {
    preserveScroll: true,
    onSuccess: () => { showSectionModal.value = false; },
  });
}

function removeSection(section) {
  if (!confirm('Remove this section and all modules inside it?')) return;
  router.delete(route('content-manager.sections.destroy', { section: section.id }), { preserveScroll: true });
}

function openItemAdd(section) {
  editingItemId.value = null;
  itemForm.reset();
  itemForm.clearErrors();
  itemForm.section_id = section.id;
  itemForm.item_type = section.layout === 'paragraphs' ? 'paragraph' : 'card';
  itemForm.media_type = 'none';
  itemForm.is_active = true;
}

function openItemEdit(section, item) {
  editingItemId.value = item.id;
  itemForm.section_id = section.id;
  itemForm.title = item.title || '';
  itemForm.body = item.body || '';
  itemForm.item_type = item.item_type || 'card';
  itemForm.media_type = item.media_type || 'none';
  itemForm.media_value = item.media_value || '';
  itemForm.is_active = !!item.is_active;
  itemForm.clearErrors();
}

function cancelItemEdit() {
  editingItemId.value = null;
  itemForm.reset();
}

function saveItem(section) {
  if (editingItemId.value) {
    itemForm.put(route('content-manager.sections.items.update', { item: editingItemId.value }), {
      preserveScroll: true,
      onSuccess: () => cancelItemEdit(),
    });
    return;
  }
  itemForm.post(route('content-manager.sections.items.store', { section: section.id }), {
    preserveScroll: true,
    onSuccess: () => cancelItemEdit(),
  });
}

function removeItem(item) {
  if (!confirm('Remove this module item?')) return;
  router.delete(route('content-manager.sections.items.destroy', { item: item.id }), { preserveScroll: true });
}
</script>

<template>
  <Head title="Sections" />

  <AuthenticatedLayout>
    <template #header>Sections</template>

    <div class="admin-form-page">
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <h1 class="h5 mb-1">Sections module</h1>
            <p class="text-muted mb-0">Create unlimited sections, each with heading/description and module items (cards or paragraphs).</p>
          </div>
          <PrimaryButton type="button" class="btn btn-sm btn-primary" @click="openSectionAdd">Add section</PrimaryButton>
        </div>
      </div>

      <div class="d-flex flex-column gap-3">
        <article v-for="section in sections" :key="section.id" class="admin-box admin-box-smooth p-3">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <h2 class="h6 mb-1">{{ section.title }}</h2>
              <p class="text-muted mb-1">{{ section.description || '—' }}</p>
              <div class="d-flex gap-2 align-items-center">
                <span class="badge text-bg-light text-uppercase">{{ section.locale }}</span>
                <span class="badge text-bg-light">{{ section.layout || 'cards' }}</span>
                <span class="badge" :class="section.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                  {{ section.is_active ? 'Active' : 'Disabled' }}
                </span>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="admin-list-link" @click="openSectionEdit(section)">Edit</button>
              <button type="button" class="admin-list-link admin-list-link-danger" @click="removeSection(section)">Delete</button>
            </div>
          </div>

          <div class="admin-box p-2 mb-2" style="border:1px dashed #d9dce8; border-radius:8px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong style="font-size:0.85rem;">Module list</strong>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="openItemAdd(section)">Add module item</button>
            </div>

            <div v-for="item in (section.items || [])" :key="item.id" class="section-item-row">
              <template v-if="editingItemId === item.id">
                <div class="d-flex flex-column gap-2">
                  <TextInput v-model="itemForm.title" class="form-control form-control-sm" placeholder="Heading/title" />
                  <textarea v-model="itemForm.body" class="form-control form-control-sm" rows="2" placeholder="Paragraph or card detail"></textarea>
                  <div class="d-flex gap-2">
                    <select v-model="itemForm.item_type" class="form-select form-select-sm">
                      <option value="card">Card</option>
                      <option value="paragraph">Paragraph</option>
                    </select>
                    <select v-model="itemForm.media_type" class="form-select form-select-sm">
                      <option value="none">No media</option>
                      <option value="number">Number</option>
                      <option value="icon">Icon / FA class</option>
                      <option value="image">Image URL/path</option>
                    </select>
                    <select
                      v-if="itemForm.media_type === 'icon'"
                      v-model="itemForm.media_value"
                      class="form-select form-select-sm"
                    >
                      <option value="">Select icon…</option>
                      <option v-for="icon in ICON_OPTIONS" :key="icon.value" :value="icon.value">{{ icon.label }}</option>
                    </select>
                    <TextInput
                      v-else
                      v-model="itemForm.media_value"
                      class="form-control form-control-sm"
                      placeholder="e.g. 1, fa-solid fa-upload, /uploads/icon.png"
                    />
                  </div>
                  <label class="form-check-label small"><input v-model="itemForm.is_active" type="checkbox" class="form-check-input me-1" /> Active</label>
                  <div class="d-flex gap-2">
                    <PrimaryButton type="button" class="btn btn-sm" :disabled="itemForm.processing" @click="saveItem(section)">Save</PrimaryButton>
                    <SecondaryButton type="button" class="btn btn-sm btn-outline-secondary" @click="cancelItemEdit">Cancel</SecondaryButton>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <strong>{{ item.title || 'Untitled' }}</strong>
                    <p class="mb-1 text-muted small">{{ item.body || '—' }}</p>
                    <small class="text-muted text-uppercase">{{ item.item_type }} · {{ item.media_type }} {{ item.media_value ? `(${item.media_value})` : '' }}</small>
                  </div>
                  <div class="d-flex gap-2">
                    <button type="button" class="admin-list-link" @click="openItemEdit(section, item)">Edit</button>
                    <button type="button" class="admin-list-link admin-list-link-danger" @click="removeItem(item)">Delete</button>
                  </div>
                </div>
              </template>
            </div>

            <div v-if="editingItemId === null && itemForm.section_id === section.id" class="section-item-row section-item-row--new">
              <div class="d-flex flex-column gap-2">
                <TextInput v-model="itemForm.title" class="form-control form-control-sm" placeholder="Heading/title" />
                <textarea v-model="itemForm.body" class="form-control form-control-sm" rows="2" placeholder="Paragraph or card detail"></textarea>
                <div class="d-flex gap-2">
                  <select v-model="itemForm.item_type" class="form-select form-select-sm">
                    <option value="card">Card</option>
                    <option value="paragraph">Paragraph</option>
                  </select>
                  <select v-model="itemForm.media_type" class="form-select form-select-sm">
                    <option value="none">No media</option>
                    <option value="number">Number</option>
                    <option value="icon">Icon / FA class</option>
                    <option value="image">Image URL/path</option>
                  </select>
                  <select
                    v-if="itemForm.media_type === 'icon'"
                    v-model="itemForm.media_value"
                    class="form-select form-select-sm"
                  >
                    <option value="">Select icon…</option>
                    <option v-for="icon in ICON_OPTIONS" :key="icon.value" :value="icon.value">{{ icon.label }}</option>
                  </select>
                  <TextInput
                    v-else
                    v-model="itemForm.media_value"
                    class="form-control form-control-sm"
                    placeholder="e.g. 1, fa-solid fa-upload, /uploads/icon.png"
                  />
                </div>
                <label class="form-check-label small"><input v-model="itemForm.is_active" type="checkbox" class="form-check-input me-1" /> Active</label>
                <div class="d-flex gap-2">
                  <PrimaryButton type="button" class="btn btn-sm" :disabled="itemForm.processing" @click="saveItem(section)">Save item</PrimaryButton>
                  <SecondaryButton type="button" class="btn btn-sm btn-outline-secondary" @click="cancelItemEdit">Cancel</SecondaryButton>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>

      <p v-if="!sections.length" class="admin-text-muted mt-3">No sections yet. Add your first section to start building dynamic frontend blocks.</p>
    </div>

    <Modal :show="showSectionModal" @close="showSectionModal = false">
      <div class="p-4">
        <h3 class="h6 mb-3">{{ editingSectionId ? 'Edit section' : 'Add section' }}</h3>
        <form @submit.prevent="saveSection" class="d-flex flex-column gap-2">
          <CmsLocaleSelect v-model="sectionForm.locale" id="section-locale" :error="sectionForm.errors.locale" />
          <div>
            <label class="form-label small fw-semibold">Section title</label>
            <TextInput v-model="sectionForm.title" class="form-control" placeholder="e.g. How it works" />
            <InputError :message="sectionForm.errors.title" />
          </div>
          <div>
            <label class="form-label small fw-semibold">Section description</label>
            <textarea v-model="sectionForm.description" class="form-control" rows="2" placeholder="Optional section description"></textarea>
            <InputError :message="sectionForm.errors.description" />
          </div>
          <div>
            <label class="form-label small fw-semibold">Layout style</label>
            <select v-model="sectionForm.layout" class="form-select">
              <option value="cards">Cards</option>
              <option value="paragraphs">Paragraphs</option>
              <option value="mixed">Mixed</option>
            </select>
            <InputError :message="sectionForm.errors.layout" />
          </div>
          <label class="form-check-label small"><input v-model="sectionForm.is_active" type="checkbox" class="form-check-input me-1" /> Active</label>
          <div class="d-flex justify-content-end gap-2">
            <SecondaryButton type="button" class="btn btn-outline-secondary" @click="showSectionModal = false">Cancel</SecondaryButton>
            <PrimaryButton type="submit" :disabled="sectionForm.processing">Save section</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
.section-item-row {
  border-top: 1px solid #eceef5;
  padding-top: 0.65rem;
  margin-top: 0.65rem;
}
.section-item-row--new {
  background: #fafbff;
  border: 1px solid #eceef5;
  border-radius: 8px;
  padding: 0.75rem;
}
</style>

