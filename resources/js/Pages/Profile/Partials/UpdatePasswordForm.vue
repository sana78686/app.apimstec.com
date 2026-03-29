<script setup>
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
        passwordInput.value?.focus();
      }
      if (form.errors.current_password) {
        form.reset('current_password');
        currentPasswordInput.value?.focus();
      }
    },
  });
};
</script>

<template>
  <section class="admin-form-page-compact">
    <header class="admin-form-page-header">
      <h2 class="admin-form-page-title admin-form-page-title-sm">Update Password</h2>
      <p class="admin-form-page-desc admin-text-xs">
        Ensure your account is using a long, random password to stay secure.
      </p>
    </header>

    <form @submit.prevent="updatePassword" class="admin-form admin-form-xs admin-form-compact">
      <div class="admin-form-group">
        <LabelWithTooltip for="current_password" value="Current password" tip="Your current login password (required to set a new one)." required />
        <TextInput
          id="current_password"
          ref="currentPasswordInput"
          v-model="form.current_password"
          type="password"
          class="admin-input-xs"
          autocomplete="current-password"
          :invalid="!!form.errors.current_password"
        />
        <InputError :message="form.errors.current_password" />
      </div>

      <div class="admin-form-group">
        <LabelWithTooltip for="password" value="New password" tip="New password. Must meet complexity rules." required />
        <TextInput
          id="password"
          ref="passwordInput"
          v-model="form.password"
          type="password"
          class="admin-input-xs"
          autocomplete="new-password"
          :invalid="!!form.errors.password"
        />
        <InputError :message="form.errors.password" />
      </div>

      <div class="admin-form-group">
        <LabelWithTooltip for="password_confirmation" value="Confirm password" tip="Re-enter the new password to confirm." required />
        <TextInput
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="admin-input-xs"
          autocomplete="new-password"
          :invalid="!!form.errors.password_confirmation"
        />
        <InputError :message="form.errors.password_confirmation" />
      </div>

      <div class="admin-form-actions">
        <PrimaryButton :disabled="form.processing" class="admin-btn admin-btn-primary admin-btn-xs">Save</PrimaryButton>
        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
          <p v-if="form.recentlySuccessful" class="admin-text-xs admin-text-muted">Saved.</p>
        </Transition>
      </div>
    </form>
  </section>
</template>

<style scoped>
.admin-form-page-title-sm { font-size: 1.125rem; }
.admin-form-compact .admin-form-group { margin-bottom: 0.875rem; }
.admin-form-actions { display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; }
.admin-btn-xs { font-size: 0.6875rem; padding: 0.4rem 0.75rem; }
</style>
