<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  status: { type: String },
});

const form = useForm({ email: '' });

const submitLink = () => {
  form.post(route('password.email'));
};

const submitOtp = () => {
  form.post(route('password.otp'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Forgot password" />

    <h1 class="auth-title">Forgot password</h1>
    <p class="auth-desc">
      Enter your email and we'll send you a reset link or a verification code.
    </p>
    <p v-if="status" class="auth-status auth-status--success">{{ status }}</p>

    <form @submit.prevent="submitLink" class="auth-form">
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

      <div class="auth-actions auth-actions--single">
        <PrimaryButton :loading="form.processing" :disabled="form.processing">
          Send reset link
        </PrimaryButton>
      </div>
    </form>

    <p class="auth-divider">or</p>

    <form @submit.prevent="submitOtp" class="auth-form">
      <PrimaryButton
        type="submit"
        :loading="form.processing"
        :disabled="form.processing || !form.email"
      >
        Send verification code
      </PrimaryButton>
    </form>

    <p class="auth-swap">
      <Link :href="route('login')" class="auth-link">Back to login</Link>
    </p>
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

.auth-actions {
  margin-top: 1.5rem;
}

.auth-actions--single {
  display: block;
}

.auth-divider {
  margin: 1rem 0;
  font-size: 0.8125rem;
  color: #999;
  text-align: center;
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
