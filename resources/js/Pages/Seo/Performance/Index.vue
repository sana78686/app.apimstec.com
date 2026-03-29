<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({
      cache_ttl: 3600,
      minify_html: false,
      lazy_load_images: true,
      cdn_base_url: '',
    }),
  },
});

const form = useForm({
  cache_ttl: props.settings.cache_ttl ?? 3600,
  minify_html: props.settings.minify_html ?? false,
  lazy_load_images: props.settings.lazy_load_images ?? true,
  cdn_base_url: props.settings.cdn_base_url ?? '',
});

const successMessage = ref(page.props.flash?.success || '');
const errorMessage = ref('');
const clearingCache = ref(false);

function clearCache() {
  clearingCache.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  window.axios
    .post('/api/seo/performance/clear-cache')
    .then(({ data }) => {
      successMessage.value = data.message || 'Cache cleared.';
    })
    .catch((e) => {
      errorMessage.value = e.response?.data?.message || 'Failed to clear cache.';
    })
    .finally(() => {
      clearingCache.value = false;
    });
}
</script>

<template>
  <Head title="Performance & Speed" />

  <AuthenticatedLayout>
    <template #header>Performance & Speed</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error">{{ errorMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Performance & Speed</h1>
          <p class="admin-list-page-desc">
            Improve page speed and rankings with <strong>caching</strong>, <strong>minification</strong>, <strong>lazy loading</strong>, and <strong>CDN</strong> support. Faster sites rank better and give users a better experience.
          </p>
        </div>
      </div>

      <form class="admin-box admin-box-smooth mb-4" @submit.prevent="form.put(route('seo.performance.update'))">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Settings</h2>

        <div class="mb-4">
          <label class="form-label fw-semibold">Cache TTL (seconds)</label>
          <input
            v-model.number="form.cache_ttl"
            type="number"
            min="0"
            max="31536000"
            class="form-control"
            style="max-width: 12rem;"
          />
          <p class="form-text mb-0">How long responses can be cached (0 = no caching). e.g. 3600 = 1 hour. Your app can use this to set Cache-Control headers.</p>
          <InputError v-if="form.errors.cache_ttl" :message="form.errors.cache_ttl" class="mt-1" />
        </div>

        <div class="mb-4">
          <div class="form-check">
            <input
              v-model="form.minify_html"
              id="minify_html"
              type="checkbox"
              class="form-check-input"
            />
            <label class="form-check-label" for="minify_html">Minify HTML</label>
          </div>
          <p class="form-text mb-0">When enabled, your frontend or middleware can strip extra whitespace from HTML to reduce size. Often combined with build-time minification (Vite).</p>
          <InputError v-if="form.errors.minify_html" :message="form.errors.minify_html" class="mt-1" />
        </div>

        <div class="mb-4">
          <div class="form-check">
            <input
              v-model="form.lazy_load_images"
              id="lazy_load_images"
              type="checkbox"
              class="form-check-input"
            />
            <label class="form-check-label" for="lazy_load_images">Lazy load images</label>
          </div>
          <p class="form-text mb-0">Use <code class="admin-list-code">loading="lazy"</code> on images so they load as the user scrolls. Improves initial page load and Core Web Vitals.</p>
          <InputError v-if="form.errors.lazy_load_images" :message="form.errors.lazy_load_images" class="mt-1" />
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">CDN base URL</label>
          <input
            v-model="form.cdn_base_url"
            type="url"
            class="form-control"
            placeholder="https://cdn.example.com"
            maxlength="500"
          />
          <p class="form-text mb-0">Optional. If you serve assets from a CDN, set the base URL here. Your frontend can prefix asset paths with this URL for faster delivery.</p>
          <InputError v-if="form.errors.cdn_base_url" :message="form.errors.cdn_base_url" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <PrimaryButton :disabled="form.processing">Save settings</PrimaryButton>
          <span v-if="form.processing" class="text-muted small">Saving…</span>
        </div>
      </form>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Cache</h2>
        <p class="text-muted small mb-3">Clear the application cache (config, route, view, general) when you change env or need a fresh start.</p>
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="clearingCache"
          @click="clearCache"
        >
          <span v-if="clearingCache">Clearing…</span>
          <span v-else>Clear application cache</span>
        </button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
