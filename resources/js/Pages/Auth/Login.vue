<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: { type: Boolean },
  status: { type: String },
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
  <GuestLayout>
    <Head title="Log in" />

    <h1 class="auth-title">Log in</h1>
    <p v-if="status" class="auth-status auth-status--success">{{ status }}</p>

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
          autocomplete="current-password"
        />
        <InputError :message="form.errors.password" />
      </div>

      <label class="auth-checkbox">
        <Checkbox name="remember" v-model:checked="form.remember" />
        <span>Remember me</span>
      </label>

      <div class="auth-actions">
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          class="auth-link"
        >
          Forgot password?
        </Link>
        <PrimaryButton :loading="form.processing" :disabled="form.processing">
          Log in
        </PrimaryButton>
      </div>
    </form>

    <p class="auth-swap">
      Don't have an account?
      <Link :href="route('register')" class="auth-link">Register</Link>
    </p>
  </GuestLayout>
</template>

<style scoped>
.auth-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1.5rem;
  text-align: center;
}

.auth-status {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  text-align: center;
}

.auth-status--success {
  color: #16a34a;
}

.auth-form {
  margin-top: 0.5rem;
}

.auth-field {
  margin-bottom: 1.25rem;
}

.auth-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #555;
  margin-bottom: 1.25rem;
}

.auth-checkbox input {
  width: 1rem;
  height: 1rem;
  accent-color: #181826;
}

.auth-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.auth-link {
  color: #e03d3d;
  text-decoration: none;
  font-weight: 500;
}

.auth-link:hover {
  text-decoration: underline;
}

.auth-swap {
  margin-top: 1.5rem;
  font-size: 0.875rem;
  color: #666;
  text-align: center;
}
</style>
