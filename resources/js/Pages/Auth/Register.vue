<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Register" />

    <h1 class="auth-title">Create account</h1>

    <form @submit.prevent="submit" class="auth-form">
      <div class="auth-field">
        <InputLabel for="name" value="Name" />
        <TextInput
          id="name"
          type="text"
          v-model="form.name"
          required
          autofocus
          autocomplete="name"
        />
        <InputError :message="form.errors.name" />
      </div>

      <div class="auth-field">
        <InputLabel for="email" value="Email" />
        <TextInput
          id="email"
          type="email"
          v-model="form.email"
          required
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
          Register
        </PrimaryButton>
      </div>
    </form>

    <p class="auth-swap">
      Already have an account?
      <Link :href="route('login')" class="auth-link">Log in</Link>
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
