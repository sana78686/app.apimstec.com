<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CmsLocaleSelect from '@/Components/CmsLocaleSelect.vue';
import HomePageEditor from '@/Components/HomePageEditor.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  contentLocale: { type: String, default: 'id' },
  content: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const form = useForm({ locale: props.contentLocale, content: props.content });
watch(() => props.contentLocale, (val) => { form.locale = val || 'id'; });
watch(() => props.content, (val) => { form.content = val ?? ''; });
function switchLocale(loc) {
  router.get(route('content-manager.privacy-policy'), { content_locale: loc }, { preserveScroll: true });
}
function submit() {
  form.locale = props.contentLocale;
  form.put(route('content-manager.privacy-policy.update'), { preserveScroll: true });
}
</script>

<template>
  <Head title="Privacy policy – Content manager" />
  <AuthenticatedLayout>
    <template #header>Privacy policy</template>
    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Privacy policy</h1>
        <p class="admin-form-page-desc text-muted small">Rich text content for the Privacy policy page on the frontend.</p>
      </div>
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <div class="admin-box admin-box-smooth mb-3">
        <CmsLocaleSelect :model-value="contentLocale" id="privacy-content-locale" @update:model-value="switchLocale" />
      </div>
      <div class="admin-box admin-box-smooth">
        <label class="form-label small fw-semibold">Content</label>
        <HomePageEditor v-model="form.content" />
        <InputError :message="form.errors.content" class="mt-2" />
        <div class="mt-3">
          <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">Save privacy policy</PrimaryButton>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
