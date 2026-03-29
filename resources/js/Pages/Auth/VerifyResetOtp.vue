<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  email: { type: String, required: true },
});

const form = useForm({
  email: props.email,
  code: '',
});

const submit = () => {
  form.post(route('password.verify-otp.store'), {
    onFinish: () => form.reset('code'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Enter verification code" />

    <h1 class="auth-title">Enter code</h1>
    <p class="auth-desc">
      We sent a 6-digit code to <strong>{{ email }}</strong>. Enter it below.
    </p>

    <form @submit.prevent="submit" class="auth-form">
      <div class="auth-field">
        <InputLabel for="code" value="Verification code" />
        <TextInput
          id="code"
          type="text"
          inputmode="numeric"
          maxlength="6"
          placeholder="000000"
          v-model="form.code"
          @input="form.code = ($event.target.value.replace(/\D/g, '').slice(0, 6))"
          autocomplete="one-time-code"
          class="input-otp"
          required
        />
        <InputError :message="form.errors.code" />
      </div>

      <div class="auth-actions auth-actions--single">
        <PrimaryButton :loading="form.processing" :disabled="form.processing">
          Continue
        </PrimaryButton>
      </div>
    </form>

    <p class="auth-swap">
      <Link :href="route('password.request')" class="auth-link">Use reset link instead</Link>
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

.auth-desc strong {
  color: #333;
}

.auth-form {
  margin-top: 0.5rem;
}

.auth-field {
  margin-bottom: 1.25rem;
}

.input-otp {
  text-align: center;
  letter-spacing: 0.5em;
  font-variant-numeric: tabular-nums;
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
