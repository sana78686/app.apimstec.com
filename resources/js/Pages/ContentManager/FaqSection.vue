<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  faqItems: { type: Array, default: () => [] },
  flash: { type: Object, default: () => ({}) },
});

const showAddModal = ref(false);
const editingId = ref(null);

const addForm = useForm({
  question: '',
  answer: '',
});

const editForm = useForm({
  question: '',
  answer: '',
});

function openAdd() {
  addForm.reset();
  addForm.clearErrors();
  showAddModal.value = true;
  editingId.value = null;
}

function openEdit(item) {
  editForm.question = item.question;
  editForm.answer = item.answer;
  editForm.clearErrors();
  editingId.value = item.id;
  showAddModal.value = false;
}

function closeEdit() {
  editingId.value = null;
  editForm.reset();
}

function submitAdd() {
  addForm.post(route('content-manager.faq.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false;
      addForm.reset();
    },
  });
}

function submitEdit() {
  if (!editingId.value) return;
  editForm.put(route('content-manager.faq.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => {
      closeEdit();
    },
  });
}

function remove(item) {
  if (!confirm('Remove this FAQ?')) return;
  router.delete(route('content-manager.faq.destroy', item.id), { preserveScroll: true });
}
</script>

<template>
  <Head title="FAQ section – Content manager" />

  <AuthenticatedLayout>
    <template #header>FAQ section</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Frequently asked questions</h1>
        <p class="admin-form-page-desc text-muted small">
          Manage questions and answers shown on the home page FAQ section.
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 mb-0">Questions & answers</h2>
          <PrimaryButton type="button" class="btn btn-primary btn-sm" @click="openAdd">
            Add new FAQ
          </PrimaryButton>
        </div>

        <ul v-if="faqItems.length" class="list-group list-group-flush">
          <li
            v-for="item in faqItems"
            :key="item.id"
            class="list-group-item d-flex align-items-start justify-content-between gap-2"
          >
            <div v-if="editingId !== item.id" class="flex-grow-1">
              <div class="fw-semibold">{{ item.question }}</div>
              <div class="text-muted small mt-1">{{ item.answer }}</div>
            </div>
            <div v-else class="flex-grow-1">
              <div class="mb-2">
                <label class="form-label small">Question</label>
                <TextInput v-model="editForm.question" class="form-control form-control-sm" />
                <InputError :message="editForm.errors.question" />
              </div>
              <div class="mb-2">
                <label class="form-label small">Answer</label>
                <textarea v-model="editForm.answer" class="form-control form-control-sm" rows="2"></textarea>
                <InputError :message="editForm.errors.answer" />
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
            <div v-if="editingId !== item.id" class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary" @click="openEdit(item)">Edit</button>
              <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(item)">Remove</button>
            </div>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">No FAQs yet. Click “Add new FAQ” to create one.</p>
      </div>
    </div>

    <Modal :show="showAddModal" @close="showAddModal = false">
      <div class="p-4">
        <h3 class="h6 mb-3">Add new FAQ</h3>
        <form @submit.prevent="submitAdd">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Question</label>
            <TextInput v-model="addForm.question" class="form-control" placeholder="e.g. Is it free?" />
            <InputError :message="addForm.errors.question" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Answer</label>
            <textarea v-model="addForm.answer" class="form-control" rows="3" placeholder="Your answer…"></textarea>
            <InputError :message="addForm.errors.answer" />
          </div>
          <div class="d-flex justify-content-end gap-2">
            <SecondaryButton type="button" class="btn btn-outline-secondary" @click="showAddModal = false">
              Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="addForm.processing">Add FAQ</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
