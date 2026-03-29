<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
  form.post(route('password.confirm'), { onFinish: () => form.reset() });
};
</script>

<template>
  <GuestLayout>
    <Head title="Confirm password" />

    <h1 class="auth-title">Confirm password</h1>
    <p class="auth-desc">Please confirm your password to continue.</p>

    <form @submit.prevent="submit" class="auth-form">
      <div class="auth-field">
        <InputLabel for="password" value="Password" />
        <TextInput
          id="password"
          type="password"
          v-model="form.password"
          required
          autocomplete="current-password"
          autofocus
        />
        <InputError :message="form.errors.password" />
      </div>

      <div class="auth-actions auth-actions--single">
        <PrimaryButton :loading="form.processing" :disabled="form.processing">
          Confirm
        </PrimaryButton>
      </div>
    </form>
  </GuestLayout>
</template>

<style scoped>
.auth-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 0.5rem;
  text-align: center;
}

.auth-desc {
  font-size: 0.875rem;
  color: #666;
  margin: 0 0 1rem;
  text-align: center;
}

.auth-form {
  margin-top: 0.5rem;
}

.auth-field {
  margin-bottom: 1.25rem;
}

.auth-actions {
  margin-top: 1.5rem;
}

.auth-actions--single {
  display: block;
}
</style>
