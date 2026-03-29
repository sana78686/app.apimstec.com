<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HomePageEditor from '@/Components/HomePageEditor.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  termsContent: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const form = useForm({
  terms_content: props.termsContent,
});

watch(() => props.termsContent, (val) => {
  form.terms_content = val ?? '';
});

function submit() {
  form.put(route('content-manager.terms.update'), { preserveScroll: true });
}
</script>

<template>
  <Head title="Terms and conditions – Content manager" />

  <AuthenticatedLayout>
    <template #header>Terms and conditions</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Terms and conditions</h1>
        <p class="admin-form-page-desc text-muted small">
          Rich text content for the Terms and conditions page on the frontend.
        </p>
      </div>

      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <div class="admin-box admin-box-smooth">
        <label class="form-label small fw-semibold">Content</label>
        <HomePageEditor v-model="form.terms_content" />
        <InputError :message="form.errors.terms_content" class="mt-2" />
        <div class="mt-3">
          <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">
            Save terms and conditions
          </PrimaryButton>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
