<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const STATUS_OPTIONS = [
  { value: 'draft',    label: 'Draft — not visible on site'     },
  { value: 'visible',  label: 'Visible — live on frontend'      },
  { value: 'disabled', label: 'Disabled — hidden from frontend' },
];

const processing = ref(false);
const form = reactive({
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  published_at: '',
  visibility: 'draft',
  meta_title: '',
  meta_description: '',
  canonical_url: '',
  meta_robots: 'index,follow',
  og_title: '',
  og_description: '',
  og_image: '',
});
const metaRobotsOptions = [
  { value: 'index,follow', label: 'Index, Follow' },
  { value: 'index,nofollow', label: 'Index, No Follow' },
  { value: 'noindex,follow', label: 'No Index, Follow' },
  { value: 'noindex,nofollow', label: 'No Index, No Follow' },
];
const errors = reactive({});

function slugFromTitle() {
  if (!form.title) return;
  form.slug = form.title.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
}

async function submit() {
  processing.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  try {
    await window.axios.post('/api/blogs', form);
    router.visit(route('blogs.index') + '?success=created');
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      Object.assign(errors, e.response.data.errors);
    } else {
      errors.form = e.response?.data?.message || 'Something went wrong.';
    }
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <Head title="Add blog post" />

  <AuthenticatedLayout>
    <template #header>Add blog post</template>

    <div class="admin-form-page">
      <div class="admin-form-page-top">
        <div class="admin-form-page-header">
          <h1 class="admin-form-page-title">Add blog post</h1>
          <p class="admin-form-page-desc text-muted small">Create a new blog post or article.</p>
        </div>
        <div class="admin-form-page-top-actions">
          <PrimaryButton type="submit" form="create-blog-form" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Save</PrimaryButton>
        </div>
      </div>
      <div class="admin-box admin-box-smooth">
        <form id="create-blog-form" @submit.prevent="submit" class="admin-form-smooth">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <LabelWithTooltip for="title" value="Title" required />
              <TextInput id="title" v-model="form.title" required class="form-control form-control-sm" @blur="slugFromTitle" />
              <InputError :message="errors.title?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="slug" value="Slug" required />
              <TextInput id="slug" v-model="form.slug" required class="form-control form-control-sm" />
              <InputError :message="errors.slug?.[0]" />
            </div>
          </div>
          <div class="mb-3">
            <LabelWithTooltip for="excerpt" value="Excerpt" optional />
            <textarea id="excerpt" v-model="form.excerpt" class="form-control form-control-sm" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <LabelWithTooltip for="content" value="Content" tip="Blog post body. Use the toolbar for headings, lists, links and formatting." optional />
            <RichTextEditor v-model="form.content" />
            <InputError :message="errors.content?.[0]" />
          </div>
            <div class="row g-3 mb-3">
            <div class="col-md-6">
              <LabelWithTooltip for="published_at" value="Published at" optional />
              <TextInput id="published_at" v-model="form.published_at" type="datetime-local" class="form-control form-control-sm" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="visibility" value="Status" tip="Draft = not shown. Visible = live on site. Disabled = hidden." />
              <select id="visibility" v-model="form.visibility" class="form-select form-select-sm">
                <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
              </select>
            </div>
          </div>

          <hr class="my-4" />
          <h2 class="h6 mb-3">SEO / Meta tags (articles &amp; blogs)</h2>
          <div class="row g-3 mb-3">
            <div class="col-12">
              <LabelWithTooltip for="meta_title" value="Meta title" tip="Title in search results. Falls back to post title if empty." optional />
              <TextInput id="meta_title" v-model="form.meta_title" class="form-control form-control-sm" placeholder="e.g. Article Title | Site Name" />
              <InputError :message="errors.meta_title?.[0]" />
            </div>
            <div class="col-12">
              <LabelWithTooltip for="meta_description" value="Meta description" tip="Short description in search results. Recommended 150–160 characters." optional />
              <textarea id="meta_description" v-model="form.meta_description" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Brief description for search snippets"></textarea>
              <InputError :message="errors.meta_description?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="canonical_url" value="Canonical URL" tip="Preferred URL if this article exists elsewhere. Leave blank to use current URL." optional />
              <TextInput id="canonical_url" v-model="form.canonical_url" type="url" class="form-control form-control-sm" placeholder="https://example.com/blog/slug" />
              <InputError :message="errors.canonical_url?.[0]" />
            </div>
            <div class="col-md-6">
              <LabelWithTooltip for="meta_robots" value="Meta robots" tip="Tell search engines whether to index this article." optional />
              <select id="meta_robots" v-model="form.meta_robots" class="form-select form-select-sm">
                <option v-for="opt in metaRobotsOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
            <div class="col-12">
              <LabelWithTooltip for="og_title" value="OG title" tip="Title when shared on social. Falls back to meta title." optional />
              <TextInput id="og_title" v-model="form.og_title" class="form-control form-control-sm" />
            </div>
            <div class="col-12">
              <LabelWithTooltip for="og_description" value="OG description" tip="Description when shared on social." optional />
              <textarea id="og_description" v-model="form.og_description" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
            </div>
            <div class="col-12">
              <LabelWithTooltip for="og_image" value="OG image URL" tip="Image URL when the article is shared (e.g. 1200×630px). Also used as the article cover on the blog list." optional />
              <TextInput id="og_image" v-model="form.og_image" type="url" class="form-control form-control-sm" placeholder="https://example.com/image.jpg" />
              <InputError :message="errors.og_image?.[0]" />
            </div>
          </div>

          <InputError v-if="errors.form" :message="errors.form" />
          <div class="d-flex gap-2">
            <Link :href="route('blogs.index')" class="btn btn-secondary btn-sm admin-btn-smooth">Cancel</Link>
            <PrimaryButton type="submit" :loading="processing" :disabled="processing" class="btn btn-primary btn-sm admin-btn-smooth">Create blog post</PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
