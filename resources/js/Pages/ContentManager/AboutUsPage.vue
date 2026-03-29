<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HomePageEditor from '@/Components/HomePageEditor.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  content: { type: String, default: '' },
  flash: { type: Object, default: () => ({}) },
});

const form = useForm({ content: props.content });
watch(() => props.content, (val) => { form.content = val ?? ''; });
function submit() {
  form.put(route('content-manager.about-us.update'), { preserveScroll: true });
}
</script>

<template>
  <Head title="About us – Content manager" />
  <AuthenticatedLayout>
    <template #header>About us</template>
    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">About us</h1>
        <p class="admin-form-page-desc text-muted small">Rich text content for the About us page on the frontend.</p>
      </div>
      <div v-if="flash?.success" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flash.success }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <div class="admin-box admin-box-smooth">
        <label class="form-label small fw-semibold">Content</label>
        <HomePageEditor v-model="form.content" />
        <InputError :message="form.errors.content" class="mt-2" />
        <div class="mt-3">
          <PrimaryButton type="button" class="btn btn-primary btn-sm" :disabled="form.processing" @click="submit">Save about us</PrimaryButton>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
