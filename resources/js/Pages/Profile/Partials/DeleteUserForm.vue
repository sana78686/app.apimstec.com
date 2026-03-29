<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;
  nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value?.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  form.clearErrors();
  form.reset();
};
</script>

<template>
  <section class="admin-form-page-compact">
    <header class="admin-form-page-header">
      <h2 class="admin-form-page-title admin-form-page-title-sm">Delete Account</h2>
      <p class="admin-form-page-desc admin-text-xs">
        Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting, download any data you wish to retain.
      </p>
    </header>

    <DangerButton @click="confirmUserDeletion" class="admin-btn-xs">Delete Account</DangerButton>

    <Modal :show="confirmingUserDeletion" @close="closeModal">
      <div class="admin-modal-inner">
        <h2 class="admin-form-page-title admin-form-page-title-sm">Are you sure you want to delete your account?</h2>
        <p class="admin-text-xs admin-text-muted mt-1">
          Once deleted, all resources and data are permanently removed. Enter your password to confirm.
        </p>

        <div class="admin-form-group mt-1">
          <LabelWithTooltip for="password" value="Password" tip="Your current password to confirm account deletion." required />
          <TextInput
            id="password"
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="admin-input-xs"
            placeholder="Password"
            @keyup.enter="deleteUser"
          />
          <InputError :message="form.errors.password" />
        </div>

        <div class="admin-form-actions">
          <SecondaryButton @click="closeModal" class="admin-btn admin-btn-secondary admin-btn-xs">Cancel</SecondaryButton>
          <DangerButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="admin-btn-xs ms-1" @click="deleteUser">
            Delete Account
          </DangerButton>
        </div>
      </div>
    </Modal>
  </section>
</template>

<style scoped>
.admin-form-page-title-sm { font-size: 1.125rem; }
.admin-modal-inner { padding: 1rem; }
.mt-1 { margin-top: 0.5rem; }
.ms-1 { margin-left: 0.5rem; }
.admin-form-group { margin-bottom: 0.875rem; }
.admin-form-actions { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; }
.admin-btn-xs { font-size: 0.6875rem; padding: 0.4rem 0.75rem; }
</style>
