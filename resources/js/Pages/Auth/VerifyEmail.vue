<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  status: { type: String },
});

const form = useForm({ code: '' });

const submitOtp = () => {
  form.post(route('verification.verify.otp'), {
    onFinish: () => form.reset('code'),
  });
};

const resendForm = useForm({});

const resend = () => {
  resendForm.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
  <GuestLayout>
    <Head title="Verify email" />

    <h1 class="auth-title">Verify your email</h1>
    <p class="auth-desc">
      We've sent a link and a 6-digit code to your email. Use either to verify.
    </p>
    <p v-if="verificationLinkSent" class="auth-status auth-status--success">
      A new verification email (with a new code) has been sent.
    </p>

    <form @submit.prevent="submitOtp" class="auth-form auth-form--otp">
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
        />
        <InputError :message="form.errors.code" />
      </div>
      <PrimaryButton :loading="form.processing" :disabled="form.processing">
        Verify with code
      </PrimaryButton>
    </form>

    <div class="auth-divider">or</div>

    <form @submit.prevent="resend" class="auth-form">
      <PrimaryButton
        type="submit"
        :loading="resendForm.processing"
        :disabled="resendForm.processing"
      >
        Resend verification email
      </PrimaryButton>
    </form>

    <p class="auth-swap">
      <Link :href="route('logout')" method="post" as="button" class="auth-link auth-link--button">
        Log out
      </Link>
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

.auth-form--otp {
  margin-bottom: 0;
}

.auth-field {
  margin-bottom: 1rem;
}

.input-otp {
  text-align: center;
  letter-spacing: 0.5em;
  font-variant-numeric: tabular-nums;
}

.auth-divider {
  margin: 1.25rem 0;
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

.auth-link--button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-size: inherit;
}

.auth-swap {
  margin-top: 1.5rem;
  font-size: 0.875rem;
  color: #666;
  text-align: center;
}
</style>
