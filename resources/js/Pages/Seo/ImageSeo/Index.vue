<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  media: { type: Array, default: () => [] },
  baseUrl: { type: String, default: '' },
});

const successMessage = ref('');
const errorMessage = ref('');
const discovering = ref(false);
const savingAltId = ref(null);
const compressingId = ref(null);
const webpId = ref(null);

function formatFileSize(bytes) {
  if (bytes == null) return '—';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

async function discover() {
  discovering.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.post('/api/seo/image-seo/discover');
    successMessage.value = data.message || 'Discovery complete.';
    router.reload();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Discovery failed.';
  } finally {
    discovering.value = false;
  }
}

async function saveAlt(item) {
  savingAltId.value = item.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.put('/api/seo/image-seo/update-alt', {
      id: item.id,
      alt_text: item.alt_text ?? '',
    });
    item.alt_text = data.alt_text;
    successMessage.value = data.message || 'ALT text updated.';
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to update ALT.';
  } finally {
    savingAltId.value = null;
  }
}

async function compress(item) {
  compressingId.value = item.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.post('/api/seo/image-seo/compress', { id: item.id });
    if (data.file_size != null) item.file_size = data.file_size;
    successMessage.value = data.message || 'Image compressed.';
    router.reload();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Compression failed.';
  } finally {
    compressingId.value = null;
  }
}

async function toWebP(item) {
  webpId.value = item.id;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const { data } = await window.axios.post('/api/seo/image-seo/to-webp', { id: item.id });
    if (data.webp_path != null) item.webp_path = data.webp_path;
    successMessage.value = data.message || 'WebP created.';
    router.reload();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'WebP conversion failed.';
  } finally {
    webpId.value = null;
  }
}

function sourceLabel(item) {
  const s = item.sources || [];
  if (!s.length) return '—';
  return s.map((x) => `${x.source_type} #${x.source_id}`).join(', ');
}
</script>

<template>
  <Head title="Image SEO Manager" />

  <AuthenticatedLayout>
    <template #header>Image SEO Manager</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error">{{ errorMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Image SEO Manager</h1>
          <p class="admin-list-page-desc">
            <strong>Compress images</strong>, <strong>convert to WebP</strong>, and add <strong>ALT text</strong> to improve SEO and loading speed. Use "Discover" to import images from your pages and blog OG images.
          </p>
        </div>
      </div>

      <div class="admin-list-toolbar mb-3">
        <button
          type="button"
          class="btn btn-outline-primary btn-sm"
          :disabled="discovering"
          @click="discover"
        >
          <span v-if="discovering">Discovering…</span>
          <span v-else>Discover images from site</span>
        </button>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Images</h2>
        <p class="text-muted small mb-3">Edit ALT text for accessibility and SEO. For local images you can compress and create WebP versions to improve loading speed.</p>
        <div class="admin-list-table-wrap">
          <table class="admin-list-table" role="grid">
            <thead>
              <tr>
                <th class="admin-url-table-th">Preview</th>
                <th class="admin-url-table-th">Path / URL</th>
                <th class="admin-url-table-th">ALT text</th>
                <th class="admin-url-table-th">Size</th>
                <th class="admin-url-table-th">Used in</th>
                <th class="admin-url-table-th">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in media" :key="m.id">
                <td class="align-middle">
                  <img
                    v-if="m.url"
                    :src="m.url"
                    :alt="m.alt_text || 'Image'"
                    class="img-thumbnail"
                    style="max-width: 80px; max-height: 60px; object-fit: cover;"
                    loading="lazy"
                    @error="($e) => { $e.target.style.display = 'none'; $e.target.nextElementSibling?.classList.remove('d-none'); }"
                  />
                  <span v-if="m.url" class="d-none text-muted small" style="max-width: 80px; display: block;">No preview</span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="align-middle">
                  <code class="admin-list-code small">{{ m.path }}</code>
                </td>
                <td class="align-middle">
                  <div class="d-flex align-items-center gap-1">
                    <input
                      v-model="m.alt_text"
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Describe the image…"
                      maxlength="500"
                      style="max-width: 220px;"
                    />
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary"
                      :disabled="savingAltId === m.id"
                      @click="saveAlt(m)"
                    >
                      {{ savingAltId === m.id ? 'Saving…' : 'Save' }}
                    </button>
                  </div>
                </td>
                <td class="align-middle">
                  {{ formatFileSize(m.file_size) }}
                  <span v-if="m.webp_path" class="badge bg-success ms-1">WebP</span>
                </td>
                <td class="align-middle small text-muted">
                  {{ sourceLabel(m) }}
                </td>
                <td class="align-middle">
                  <template v-if="m.is_local">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary me-1"
                      :disabled="compressingId === m.id"
                      title="Compress image to reduce file size"
                      @click="compress(m)"
                    >
                      {{ compressingId === m.id ? '…' : 'Compress' }}
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary"
                      :disabled="webpId === m.id || !!m.webp_path"
                      :title="m.webp_path ? 'WebP already created' : 'Create WebP version'"
                      @click="toWebP(m)"
                    >
                      {{ webpId === m.id ? '…' : (m.webp_path ? 'WebP ✓' : 'To WebP') }}
                    </button>
                  </template>
                  <span v-else class="text-muted small">External (no compress/WebP)</span>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!media.length" class="admin-text-muted p-3 mb-0">
            No images yet. Click "Discover images from site" to import OG images from pages and blogs, or add images via the Media library and link them in content.
          </p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
