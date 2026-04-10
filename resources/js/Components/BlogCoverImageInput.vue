<script setup>
/**
 * Upload a cover image for a blog post (stored as og_image — used on React list + detail).
 */
import { ref, computed } from 'vue';
import LabelWithTooltip from '@/Components/LabelWithTooltip.vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  inputId: { type: String, default: 'og_image' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const uploading = ref(false);
const uploadError = ref('');

const previewSrc = computed(() => {
  const v = String(props.modelValue || '').trim();
  if (!v) return '';
  // cms-uploads: use authenticated preview route (file is on CMS server, not frontend)
  const m = v.match(/\/cms-uploads\/[^/]+\/([^/?#]+)$/i);
  if (m && m[1]) {
    try {
      return route('media.preview', { filename: m[1] });
    } catch {
      return v;
    }
  }
  // uploads/editor/ (blog form upload): file lives in CMS storage, not on the frontend domain.
  // Convert to /storage/ path so the CMS admin can preview it.
  const upl = v.match(/\/uploads\/((?:editor|blog|images)\/[^/?#]+)$/i);
  if (upl && upl[1]) {
    return '/storage/uploads/' + upl[1];
  }
  return v;
});

async function onFileChange(event) {
  const input = event.target;
  const file = input.files?.[0];
  if (!file) return;
  uploadError.value = '';
  uploading.value = true;
  try {
    const fd = new FormData();
    fd.append('image', file);
    const { data } = await window.axios.post('/api/media/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    const url = (data && (data.url || data.absolute_url)) ? String(data.url || data.absolute_url).trim() : '';
    if (!url) {
      uploadError.value = 'Upload did not return a URL.';
      return;
    }
    emit('update:modelValue', url);
  } catch (e) {
    uploadError.value = e.response?.data?.message || e.message || 'Upload failed.';
  } finally {
    uploading.value = false;
    input.value = '';
  }
}

function clearImage() {
  emit('update:modelValue', '');
}
</script>

<template>
  <div class="blog-cover-image-input">
    <LabelWithTooltip
      :for="inputId"
      value="Cover image"
      tip="Shown on the blog list card and at the top of the post. Also used when the post is shared (Open Graph)."
    />
    <div class="d-flex flex-wrap align-items-start gap-2 mb-2">
      <label class="btn btn-outline-secondary btn-sm mb-0" :class="{ disabled: disabled || uploading }">
        <span v-if="uploading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" />
        {{ uploading ? 'Uploading…' : 'Upload image' }}
        <input
          type="file"
          class="d-none"
          accept="image/*"
          :disabled="disabled || uploading"
          @change="onFileChange"
        />
      </label>
      <button
        v-if="previewSrc"
        type="button"
        class="btn btn-outline-danger btn-sm"
        :disabled="disabled || uploading"
        @click="clearImage"
      >
        Remove
      </button>
    </div>
    <input
      :id="inputId"
      type="text"
      class="form-control form-control-sm"
      :value="modelValue"
      placeholder="Or paste image URL (https://… or /storage/…)"
      :disabled="disabled"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <p v-if="uploadError" class="text-danger small mt-1 mb-0">{{ uploadError }}</p>
    <div v-if="previewSrc" class="blog-cover-preview mt-2">
      <img :src="previewSrc" alt="Cover preview" class="img-thumbnail" style="max-height: 160px; max-width: 100%; object-fit: contain;" />
    </div>
    <p class="text-muted small mt-1 mb-0">This value is saved as the SEO “OG image” and powers the React frontend card + hero.</p>
  </div>
</template>
