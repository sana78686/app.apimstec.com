<script setup>
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
  mustVerifyEmail: { type: Boolean },
  status: { type: String },
});

const user = usePage().props.auth.user;
const form = useForm({
  name: user.name,
  email: user.email,
});
</script>

<template>
  <section class="admin-form-page-compact">
    <header class="admin-form-page-header">
      <h2 class="admin-form-page-title admin-form-page-title-sm">Profile Information</h2>
      <p class="admin-form-page-desc admin-text-xs">
        Update your account's profile information and email address.
      </p>
    </header>

    <form @submit.prevent="form.patch(route('profile.update'))" class="admin-form admin-form-xs admin-form-compact">
      <div class="admin-form-group">
        <LabelWithTooltip for="name" value="Name" tip="Your full display name in the admin panel." required />
        <TextInput id="name" type="text" v-model="form.name" required autofocus autocomplete="name" class="admin-input-xs" :invalid="!!form.errors.name" />
        <InputError :message="form.errors.name" />
      </div>

      <div class="admin-form-group">
        <LabelWithTooltip for="email" value="Email" tip="Login email. Must be unique. Verification may be required." required />
        <TextInput id="email" type="email" v-model="form.email" required autocomplete="username" class="admin-input-xs" :invalid="!!form.errors.email" />
        <InputError :message="form.errors.email" />
      </div>

      <div v-if="mustVerifyEmail && user.email_verified_at === null" class="admin-form-help">
        <p class="admin-text-xs">
          Your email address is unverified.
          <Link :href="route('verification.send')" method="post" as="button" class="admin-link-inline">
            Click here to re-send the verification email.
          </Link>
        </p>
        <div v-show="status === 'verification-link-sent'" class="admin-text-xs admin-text-success">
          A new verification link has been sent to your email address.
        </div>
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
.admin-form-help { margin-bottom: 0.875rem; }
.admin-link-inline { background: none; border: none; padding: 0; color: var(--admin-primary, #e03d3d); cursor: pointer; text-decoration: underline; font-size: inherit; }
.admin-text-success { color: #166534; }
.admin-form-actions { display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; }
.admin-btn-xs { font-size: 0.6875rem; padding: 0.4rem 0.75rem; }
</style>
