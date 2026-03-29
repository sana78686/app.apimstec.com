<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  item: { type: Object, required: true },
});

const form = useForm({
  type: props.item.type,
  id: props.item.id,
  og_title: props.item.og_title || '',
  og_description: props.item.og_description || '',
  og_image: props.item.og_image || '',
});

function submit() {
  form.put(route('seo.social-sharing.update'), {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="'Edit OG: ' + (item.title || 'Social Sharing')" />

  <AuthenticatedLayout>
    <template #header>Social Sharing (Open Graph)</template>

    <div class="admin-list-page">
      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Edit Open Graph</h1>
          <p class="admin-list-page-desc">
            <strong>{{ item.title }}</strong> ({{ item.type }}) — Set OG title, description, and image so this link looks good when shared on Facebook, X, and LinkedIn.
          </p>
        </div>
      </div>

      <form class="admin-box admin-box-smooth" @submit.prevent="submit">
        <p class="text-muted small mb-3">Recommended: OG image 1200×630px. Use absolute URLs for the image (e.g. https://yoursite.com/image.jpg).</p>

        <div class="mb-3">
          <InputLabel for="og_title" value="OG title" />
          <TextInput
            id="og_title"
            v-model="form.og_title"
            type="text"
            class="form-control form-control-sm mt-1"
            placeholder="Falls back to page title if empty"
          />
          <InputError :message="form.errors.og_title?.[0] ?? form.errors.og_title" class="mt-1" />
        </div>

        <div class="mb-3">
          <InputLabel for="og_description" value="OG description" />
          <textarea
            id="og_description"
            v-model="form.og_description"
            class="form-control form-control-sm mt-1"
            rows="3"
            maxlength="500"
            placeholder="Short description for the share card"
          />
          <InputError :message="form.errors.og_description?.[0] ?? form.errors.og_description" class="mt-1" />
        </div>

        <div class="mb-4">
          <InputLabel for="og_image" value="OG image URL" />
          <TextInput
            id="og_image"
            v-model="form.og_image"
            type="url"
            class="form-control form-control-sm mt-1"
            placeholder="https://example.com/images/share.jpg"
          />
          <InputError :message="form.errors.og_image?.[0] ?? form.errors.og_image" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-2">
          <PrimaryButton :disabled="form.processing">Save Open Graph</PrimaryButton>
          <Link :href="route('seo.social-sharing')" class="btn btn-secondary btn-sm">Cancel</Link>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
