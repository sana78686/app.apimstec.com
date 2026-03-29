<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  email: { type: String, required: true },
  token: { type: String, required: true },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Reset password" />

    <h1 class="auth-title">Set new password</h1>
    <p class="auth-desc">Enter your new password below.</p>

    <form @submit.prevent="submit" class="auth-form">
      <div class="auth-field">
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          type="email"
          v-model="form.email"
          required
          autofocus
          autocomplete="username"
        />
        <InputError :message="form.errors.email" />
      </div>

      <div class="auth-field">
        <InputLabel for="password" value="Password" />
        <TextInput
          id="password"
          type="password"
          v-model="form.password"
          required
          autocomplete="new-password"
        />
        <InputError :message="form.errors.password" />
      </div>

      <div class="auth-field">
        <InputLabel for="password_confirmation" value="Confirm password" />
        <TextInput
          id="password_confirmation"
          type="password"
          v-model="form.password_confirmation"
          required
          autocomplete="new-password"
        />
        <InputError :message="form.errors.password_confirmation" />
      </div>

      <div class="auth-actions auth-actions--single">
        <PrimaryButton :loading="form.processing" :disabled="form.processing">
          Reset password
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
