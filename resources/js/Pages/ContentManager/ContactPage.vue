<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CmsLocaleSelect from '@/Components/CmsLocaleSelect.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  contentLocale: { type: String, default: 'id' },
  contactEmail: { type: String, default: '' },
  contactPhone: { type: String, default: '' },
  contactAddress: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const form = useForm({
  locale: props.contentLocale,
  contact_email: props.contactEmail,
  contact_phone: props.contactPhone,
  contact_address: props.contactAddress,
});

watch(() => props.contentLocale, (val) => {
  form.locale = val || 'id';
});
watch(
  () => [props.contactEmail, props.contactPhone, props.contactAddress],
  ([email, phone, address]) => {
    form.contact_email = email ?? '';
    form.contact_phone = phone ?? '';
    form.contact_address = address ?? '';
  }
);

function switchLocale(loc) {
  router.get(route('content-manager.contact'), { content_locale: loc }, { preserveScroll: true });
}

function submit() {
  form.locale = props.contentLocale;
  form.put(route('content-manager.contact.update'), { preserveScroll: true });
}
</script>

<template>
  <Head title="Contact us page – Content manager" />

  <AuthenticatedLayout>
    <template #header>Contact us page</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Contact us page</h1>
        <p class="admin-form-page-desc text-muted small">
          Contact details shown on the frontend Contact page. Form submissions are sent to the email below.
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth mb-3">
        <CmsLocaleSelect
          :model-value="contentLocale"
          id="contact-content-locale"
          @update:model-value="switchLocale"
        />
        <p class="text-muted small mt-2 mb-0">Contact details can differ per language (e.g. localized address).</p>
      </div>

      <div class="admin-box admin-box-smooth">
        <form @submit.prevent="submit">
          <div class="mb-3">
            <label for="contact_email" class="form-label small fw-semibold">Contact email</label>
            <TextInput
              id="contact_email"
              v-model="form.contact_email"
              type="email"
              class="form-control form-control-sm"
              placeholder="e.g. contact@example.com"
            />
            <InputError :message="form.errors.contact_email" />
          </div>
          <div class="mb-3">
            <label for="contact_phone" class="form-label small fw-semibold">Contact phone</label>
            <TextInput
              id="contact_phone"
              v-model="form.contact_phone"
              type="text"
              class="form-control form-control-sm"
              placeholder="e.g. +1 234 567 8900"
            />
            <InputError :message="form.errors.contact_phone" />
          </div>
          <div class="mb-3">
            <label for="contact_address" class="form-label small fw-semibold">Contact address</label>
            <textarea
              id="contact_address"
              v-model="form.contact_address"
              class="form-control form-control-sm"
              rows="3"
              placeholder="e.g. 123 Main St, City, Country"
            />
            <InputError :message="form.errors.contact_address" />
          </div>
          <PrimaryButton type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
            Save contact details
          </PrimaryButton>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
