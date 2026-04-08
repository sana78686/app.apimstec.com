<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  mediaItems: { type: Array, default: () => [] },
  publicSiteBaseUrl: { type: String, default: '' },
  uploadSubdir: { type: String, default: 'cms-uploads' },
});

const page = usePage();
const copyHint = ref('');

const uploadForm = useForm({ file: null });

const fileInputRef = ref(null);

function onFileChange(e) {
  const f = e.target?.files?.[0];
  uploadForm.file = f || null;
  uploadForm.clearErrors('file');
}

function submitUpload() {
  if (!uploadForm.file) return;
  uploadForm.post(route('media.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset('file');
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
    },
  });
}

function absoluteUrl(item) {
  const base = String(props.publicSiteBaseUrl || '').replace(/\/+$/, '');
  const path = item.path.startsWith('/') ? item.path : `/${item.path}`;
  if (base) return `${base}${path}`;
  return path;
}

async function copyLink(item) {
  const url = absoluteUrl(item);
  copyHint.value = '';
  try {
    await navigator.clipboard.writeText(url);
    copyHint.value = 'Copied link to clipboard.';
  } catch {
    copyHint.value = '';
    window.prompt('Copy this URL:', url);
  }
}

const flashSuccess = computed(() => page.props.flash?.success || '');
</script>

<template>
  <Head title="Media library" />

  <AuthenticatedLayout>
    <template #header>Media library</template>

    <div class="admin-form-page">
      <div class="admin-form-page-header mb-3">
        <h1 class="admin-form-page-title">Media library</h1>
        <p class="admin-form-page-desc text-muted small">
          Uploads are stored once in the React app’s <code>public/{{ uploadSubdir }}/</code> folder and served by the marketing site. Use “Copy link” for Open Graph or rich text in the CMS.
        </p>
      </div>

      <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flashSuccess }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <p v-if="copyHint" class="small text-success mb-3" role="status">{{ copyHint }}</p>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-2">Upload image</h2>
        <p class="text-muted small mb-3">JPEG, PNG, GIF, WebP, SVG, or AVIF — max 5&nbsp;MB.</p>
        <div class="d-flex flex-wrap align-items-end gap-2">
          <div>
            <input
              ref="fileInputRef"
              type="file"
              class="form-control form-control-sm"
              accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/avif,.jpg,.jpeg,.png,.gif,.webp,.svg,.avif"
              @change="onFileChange"
            />
            <InputError :message="uploadForm.errors.file" class="mt-1" />
          </div>
          <PrimaryButton
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="uploadForm.processing || !uploadForm.file"
            @click="submitUpload"
          >
            {{ uploadForm.processing ? 'Uploading…' : 'Upload to frontend' }}
          </PrimaryButton>
        </div>
        <p v-if="!publicSiteBaseUrl" class="text-muted small mt-3 mb-0">
          Set the active domain’s <strong>frontend URL</strong> (Domains) so copied links use the full public origin; paths still work as <code>/{{ uploadSubdir }}/…</code> on the live site.
        </p>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="h6 mb-3">Images on the frontend</h2>
        <p v-if="!mediaItems.length" class="text-muted small mb-0">No files yet. Upload an image above.</p>
        <ul v-else class="media-grid list-unstyled mb-0">
          <li v-for="item in mediaItems" :key="item.name" class="media-card">
            <div class="media-card-thumb">
              <img :src="absoluteUrl(item)" :alt="item.name" loading="lazy" />
            </div>
            <div class="media-card-body">
              <div class="media-card-name text-truncate small font-monospace" :title="item.name">{{ item.name }}</div>
              <div class="media-card-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="copyLink(item)">Copy link</button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}
.media-card {
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 8px;
  overflow: hidden;
  background: var(--admin-card-bg, #fff);
}
.media-card-thumb {
  aspect-ratio: 4 / 3;
  background: #f4f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
}
.media-card-thumb img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}
.media-card-body {
  padding: 0.65rem 0.75rem 0.75rem;
}
.media-card-name {
  margin-bottom: 0.5rem;
}
.media-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
</style>
