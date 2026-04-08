<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  mediaItems: { type: Array, default: () => [] },
  publicSiteBaseUrl: { type: String, default: '' },
  uploadSubdir: { type: String, default: 'cms-uploads' },
  domainSegment: { type: String, default: '' },
  tenantDomain: { type: String, default: '' },
  requiresDomain: { type: Boolean, default: false },
});

const page = usePage();
const copyHint = ref('');

const uploadForm = useForm({ file: null, label: '' });
const fileInputRef = ref(null);

const editOpen = ref(false);
const editingItem = ref(null);
const replaceFile = ref(null);
const replaceLabel = ref('');
const replaceInputRef = ref(null);

const renameOpen = ref(false);
const renamingItem = ref(null);
const renameLabel = ref('');

function thumbSrc(item) {
  return route('media.preview', { filename: item.name });
}

function onFileChange(e) {
  const f = e.target?.files?.[0];
  uploadForm.file = f || null;
  uploadForm.clearErrors('file');
}

function submitUpload() {
  if (!uploadForm.file || props.requiresDomain) return;
  uploadForm.post(route('media.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      uploadForm.reset();
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

function openEdit(item) {
  editingItem.value = item;
  replaceFile.value = null;
  replaceLabel.value = '';
  editOpen.value = true;
  if (replaceInputRef.value) {
    replaceInputRef.value.value = '';
  }
}

function closeEdit() {
  editOpen.value = false;
  editingItem.value = null;
  replaceFile.value = null;
  replaceLabel.value = '';
}

function onReplaceFile(e) {
  replaceFile.value = e.target?.files?.[0] || null;
}

function submitReplace() {
  if (!editingItem.value || !replaceFile.value) return;
  const fd = new FormData();
  fd.append('_method', 'PUT');
  fd.append('file', replaceFile.value);
  if (replaceLabel.value.trim()) {
    fd.append('label', replaceLabel.value.trim());
  }
  router.post(route('media.update', editingItem.value.name), fd, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => closeEdit(),
  });
}

function escapeRegExp(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function openRename(item) {
  renamingItem.value = item;
  const base = item.name.replace(/\.[^.]+$/, '');
  const seg = props.domainSegment;
  if (seg) {
    const re = new RegExp(`^${escapeRegExp(seg)}-(.+)-[a-z0-9]{6}$`, 'i');
    const m = base.match(re);
    if (m) {
      renameLabel.value = m[1];
      renameOpen.value = true;
      return;
    }
    if (base.startsWith(`${seg}-`)) {
      renameLabel.value = base.slice(seg.length + 1);
      renameOpen.value = true;
      return;
    }
  }
  renameLabel.value = base;
  renameOpen.value = true;
}

function closeRename() {
  renameOpen.value = false;
  renamingItem.value = null;
  renameLabel.value = '';
}

function submitRename() {
  if (!renamingItem.value || !renameLabel.value.trim()) return;
  router.patch(
    route('media.rename', renamingItem.value.name),
    { label: renameLabel.value.trim() },
    {
      preserveScroll: true,
      onSuccess: () => closeRename(),
    },
  );
}

function confirmDelete(item) {
  if (!window.confirm(`Delete "${item.name}"? This cannot be undone.`)) return;
  router.delete(route('media.destroy', item.name), { preserveScroll: true });
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
          Files are saved under <code>public/{{ uploadSubdir }}/{{ domainSegment || '…' }}/</code> on the server that holds the React <code>public</code> folder for this tenant (<strong>{{ tenantDomain || 'pick a site' }}</strong>).
          Names use your site key plus an optional label and random suffix. Thumbnails load from the CMS so you can preview even before the live domain serves the same path.
        </p>
      </div>

      <div v-if="requiresDomain" class="alert alert-warning mb-3" role="alert">
        Choose a website from the header domain switcher to upload and manage images for that tenant.
      </div>

      <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ flashSuccess }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <p v-if="copyHint" class="small text-success mb-3" role="status">{{ copyHint }}</p>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="h6 mb-2">Upload image</h2>
        <p class="text-muted small mb-3">JPEG, PNG, GIF, WebP, SVG, or AVIF — max 5&nbsp;MB.</p>
        <div class="mb-2" v-if="!requiresDomain">
          <label class="form-label small fw-semibold">Base name (optional)</label>
          <input
            v-model="uploadForm.label"
            type="text"
            class="form-control form-control-sm"
            placeholder="e.g. hero-banner (saved as {{ domainSegment }}-hero-banner-xxxxxx.ext)"
            maxlength="120"
          />
          <InputError :message="uploadForm.errors.label" class="mt-1" />
        </div>
        <div class="d-flex flex-wrap align-items-end gap-2">
          <div>
            <input
              ref="fileInputRef"
              type="file"
              class="form-control form-control-sm"
              accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/avif,.jpg,.jpeg,.png,.gif,.webp,.svg,.avif"
              :disabled="requiresDomain"
              @change="onFileChange"
            />
            <InputError :message="uploadForm.errors.file" class="mt-1" />
          </div>
          <PrimaryButton
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="uploadForm.processing || !uploadForm.file || requiresDomain"
            @click="submitUpload"
          >
            {{ uploadForm.processing ? 'Uploading…' : 'Upload to frontend' }}
          </PrimaryButton>
        </div>
        <p v-if="!publicSiteBaseUrl && !requiresDomain" class="text-muted small mt-3 mb-0">
          Set the active domain’s <strong>frontend URL</strong> in Domains so “Copy link” uses the full public URL.
        </p>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="h6 mb-3">Images for this tenant</h2>
        <p v-if="!mediaItems.length" class="text-muted small mb-0">No files yet. Upload an image above.</p>
        <ul v-else class="media-grid list-unstyled mb-0">
          <li v-for="item in mediaItems" :key="item.name" class="media-card">
            <div class="media-card-thumb">
              <img :src="thumbSrc(item)" :alt="item.name" loading="lazy" />
            </div>
            <div class="media-card-body">
              <div class="media-card-name text-truncate small font-monospace" :title="item.name">{{ item.name }}</div>
              <div class="media-card-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="copyLink(item)">Copy link</button>
                <button type="button" class="btn btn-outline-primary btn-sm" @click="openEdit(item)">Replace</button>
                <button type="button" class="btn btn-outline-primary btn-sm" @click="openRename(item)">Rename</button>
                <button type="button" class="btn btn-outline-danger btn-sm" @click="confirmDelete(item)">Delete</button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Replace image modal -->
    <div v-if="editOpen" class="media-modal-backdrop" @click.self="closeEdit">
      <div class="media-modal card shadow">
        <div class="card-body">
          <h3 class="h6 mb-3">Replace image</h3>
          <p class="text-muted small mb-3">
            The old file <code class="small">{{ editingItem?.name }}</code> will be deleted. A new name is generated from the label (or file name) plus your domain key.
          </p>
          <div class="mb-2">
            <label class="form-label small fw-semibold">New base name (optional)</label>
            <input v-model="replaceLabel" type="text" class="form-control form-control-sm" maxlength="120" placeholder="e.g. product-shot" />
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">New file</label>
            <input
              ref="replaceInputRef"
              type="file"
              class="form-control form-control-sm"
              accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/avif,.jpg,.jpeg,.png,.gif,.webp,.svg,.avif"
              @change="onReplaceFile"
            />
          </div>
          <div class="d-flex gap-2 justify-content-end">
            <SecondaryButton type="button" class="btn btn-light btn-sm" @click="closeEdit">Cancel</SecondaryButton>
            <PrimaryButton
              type="button"
              class="btn btn-primary btn-sm"
              :disabled="!replaceFile"
              @click="submitReplace"
            >
              Save replacement
            </PrimaryButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Rename modal -->
    <div v-if="renameOpen" class="media-modal-backdrop" @click.self="closeRename">
      <div class="media-modal card shadow">
        <div class="card-body">
          <h3 class="h6 mb-3">Rename image</h3>
          <p class="text-muted small mb-3">
            File stays the same; only the name changes to <code>{{ domainSegment }}-&lt;label&gt;-&lt;id&gt;.ext</code>.
          </p>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Label</label>
            <input v-model="renameLabel" type="text" class="form-control form-control-sm" maxlength="120" placeholder="e.g. hero-banner" />
          </div>
          <div class="d-flex gap-2 justify-content-end">
            <SecondaryButton type="button" class="btn btn-light btn-sm" @click="closeRename">Cancel</SecondaryButton>
            <PrimaryButton
              type="button"
              class="btn btn-primary btn-sm"
              :disabled="!renameLabel.trim()"
              @click="submitRename"
            >
              Rename
            </PrimaryButton>
          </div>
        </div>
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
.media-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.media-modal {
  max-width: 420px;
  width: 100%;
  border: none;
}
</style>
