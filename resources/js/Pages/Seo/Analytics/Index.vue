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
      gsc_site_url: '',
      ga_measurement_id: '',
    }),
  },
  summary: {
    type: Object,
    default: () => ({
      clicks: null,
      impressions: null,
      ctr: null,
      position: null,
    }),
  },
  topPages: { type: Array, default: () => [] },
  topKeywords: { type: Array, default: () => [] },
});

const form = useForm({
  gsc_site_url: props.settings.gsc_site_url ?? '',
  ga_measurement_id: props.settings.ga_measurement_id ?? '',
});

const successMessage = ref(page.props.flash?.success || '');

const GSC_URL = 'https://search.google.com/search-console';
const GA_URL = 'https://analytics.google.com';
</script>

<template>
  <Head title="SEO Analytics & Reports" />

  <AuthenticatedLayout>
    <template #header>SEO Analytics & Reports</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">SEO Analytics & Reports</h1>
          <p class="admin-list-page-desc">
            Track <strong>clicks & impressions</strong>, see <strong>top pages & keywords</strong>, and integrate with <strong>Google Search Console</strong> and <strong>Google Analytics</strong> to monitor performance and rankings.
          </p>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">Clicks</div>
            <div class="fs-4 fw-semibold">{{ summary?.clicks != null ? summary.clicks.toLocaleString() : '—' }}</div>
            <div v-if="summary?.clicks == null" class="small text-muted">Connect Search Console below</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">Impressions</div>
            <div class="fs-4 fw-semibold">{{ summary?.impressions != null ? summary.impressions.toLocaleString() : '—' }}</div>
            <div v-if="summary?.impressions == null" class="small text-muted">Connect Search Console below</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">CTR</div>
            <div class="fs-4 fw-semibold">{{ summary?.ctr != null ? (summary.ctr + '%') : '—' }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">Avg. position</div>
            <div class="fs-4 fw-semibold">{{ summary?.position != null ? summary.position : '—' }}</div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="admin-box admin-box-smooth">
            <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Top pages</h2>
            <p class="text-muted small mb-3">Pages with the most clicks from search (Google Search Console data).</p>
            <div class="admin-list-table-wrap" v-if="topPages && topPages.length">
              <table class="admin-list-table admin-list-table-sm" role="grid">
                <thead>
                  <tr>
                    <th class="admin-url-table-th">Page</th>
                    <th class="admin-url-table-th">Clicks</th>
                    <th class="admin-url-table-th">Impressions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in topPages" :key="i">
                    <td><code class="admin-list-code small">{{ row.page || row.url }}</code></td>
                    <td>{{ row.clicks }}</td>
                    <td>{{ row.impressions }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="admin-text-muted p-3 mb-0">Connect Google Search Console and add your property below to see top pages.</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="admin-box admin-box-smooth">
            <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Top keywords</h2>
            <p class="text-muted small mb-3">Queries that drive the most traffic (Search Console data).</p>
            <div class="admin-list-table-wrap" v-if="topKeywords && topKeywords.length">
              <table class="admin-list-table admin-list-table-sm" role="grid">
                <thead>
                  <tr>
                    <th class="admin-url-table-th">Keyword</th>
                    <th class="admin-url-table-th">Clicks</th>
                    <th class="admin-url-table-th">Impressions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in topKeywords" :key="i">
                    <td>{{ row.query || row.keyword }}</td>
                    <td>{{ row.clicks }}</td>
                    <td>{{ row.impressions }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="admin-text-muted p-3 mb-0">Connect Google Search Console and add your property below to see top keywords.</p>
          </div>
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Integration</h2>
        <p class="text-muted small mb-3">Add your Google Search Console property and Google Analytics (GA4) Measurement ID. Data can be synced via API when configured.</p>

        <form @submit.prevent="form.put(route('seo.analytics.update'))" class="mb-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Google Search Console property</label>
            <input
              v-model="form.gsc_site_url"
              type="text"
              class="form-control"
              placeholder="e.g. sc_domain:yoursite.com or https://yoursite.com/"
              maxlength="500"
            />
            <p class="form-text mb-0">The property URL or domain you use in Search Console (e.g. <code class="admin-list-code">sc_domain:example.com</code> or your URL prefix).</p>
            <InputError v-if="form.errors.gsc_site_url" :message="form.errors.gsc_site_url" class="mt-1" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Google Analytics (GA4) Measurement ID</label>
            <input
              v-model="form.ga_measurement_id"
              type="text"
              class="form-control"
              placeholder="G-XXXXXXXXXX"
              maxlength="50"
            />
            <p class="form-text mb-0">Your GA4 Measurement ID from Admin → Data Streams (starts with G-).</p>
            <InputError v-if="form.errors.ga_measurement_id" :message="form.errors.ga_measurement_id" class="mt-1" />
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <PrimaryButton :disabled="form.processing">Save settings</PrimaryButton>
            <span v-if="form.processing" class="text-muted small">Saving…</span>
          </div>
        </form>

        <div class="d-flex flex-wrap gap-2">
          <a :href="GSC_URL" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Open Google Search Console</a>
          <a :href="GA_URL" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">Open Google Analytics</a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
