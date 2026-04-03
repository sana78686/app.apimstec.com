<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();
const props = defineProps({
  settings: {
    type: Object,
    default: () => ({
      gsc_site_url: '',
      ga_measurement_id: '',
      frontend_head_snippet: '',
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
  searchConsoleLiveData: { type: Boolean, default: false },
  searchConsoleLiveDataNote: { type: String, default: '' },
  gscDateRange: { type: Object, default: null },
  gscError: { type: String, default: null },
  gscConnected: { type: Boolean, default: false },
  gscConnectedEmail: { type: String, default: '' },
  gscOAuthConfigured: { type: Boolean, default: false },
});

const form = useForm({
  gsc_site_url: props.settings.gsc_site_url ?? '',
  ga_measurement_id: props.settings.ga_measurement_id ?? '',
  frontend_head_snippet: props.settings.frontend_head_snippet ?? '',
});

watch(
  () => props.settings,
  (s) => {
    form.gsc_site_url = s?.gsc_site_url ?? '';
    form.ga_measurement_id = s?.ga_measurement_id ?? '';
    form.frontend_head_snippet = s?.frontend_head_snippet ?? '';
  },
  { deep: true },
);

const successMessage = ref(page.props.flash?.success || '');
const errorMessage = ref(page.props.flash?.error || '');

const GSC_URL = 'https://search.google.com/search-console';
const GA_URL = 'https://analytics.google.com';

function disconnectGsc() {
  if (!window.confirm('Disconnect Google Search Console for this website? You can connect again anytime.')) return;
  router.post(route('seo.analytics.google.disconnect'));
}
</script>

<template>
  <Head title="SEO Analytics & Reports" />

  <AuthenticatedLayout>
    <template #header>SEO Analytics & Reports</template>

    <div class="admin-list-page">
      <p v-if="successMessage" class="admin-flash admin-flash-success">{{ successMessage }}</p>
      <p v-if="errorMessage" class="admin-flash admin-flash-error" role="alert">{{ errorMessage }}</p>

      <p
        v-if="searchConsoleLiveDataNote"
        class="text-muted small border rounded p-3 mb-4 bg-light"
        role="status"
      >
        {{ searchConsoleLiveDataNote }}
      </p>

      <p v-if="gscError" class="admin-flash admin-flash-error" role="alert">{{ gscError }}</p>

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
            <div class="fs-4 fw-semibold">{{ searchConsoleLiveData ? Number(summary?.clicks ?? 0).toLocaleString() : '—' }}</div>
            <div v-if="!searchConsoleLiveData" class="small text-muted">Connect Search Console below</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">Impressions</div>
            <div class="fs-4 fw-semibold">{{ searchConsoleLiveData ? Number(summary?.impressions ?? 0).toLocaleString() : '—' }}</div>
            <div v-if="!searchConsoleLiveData" class="small text-muted">Connect Search Console below</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">CTR</div>
            <div class="fs-4 fw-semibold">{{ searchConsoleLiveData && summary?.ctr != null ? summary.ctr + '%' : '—' }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="admin-box admin-box-smooth h-100 p-3">
            <div class="small text-muted mb-1">Avg. position</div>
            <div class="fs-4 fw-semibold">{{ searchConsoleLiveData && summary?.position != null ? summary.position : '—' }}</div>
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
            <p v-else class="admin-text-muted p-3 mb-0">
              {{ searchConsoleLiveData ? 'No page-level data in this period.' : 'Connect Search Console and set the property URL to see top pages.' }}
            </p>
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
            <p v-else class="admin-text-muted p-3 mb-0">
              {{ searchConsoleLiveData ? 'No query data in this period.' : 'Connect Search Console and set the property URL to see top keywords.' }}
            </p>
          </div>
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Google Search Console (live data)</h2>
        <p class="text-muted small mb-3">
          Each website stores its own OAuth tokens. The Google account you use must have access to the property in Search Console.
        </p>
        <div v-if="!gscOAuthConfigured" class="alert alert-warning small mb-3">
          OAuth is not configured: set <code class="admin-list-code">GOOGLE_CLIENT_ID</code>,
          <code class="admin-list-code">GOOGLE_CLIENT_SECRET</code>, and
          <code class="admin-list-code">GOOGLE_GSC_REDIRECT_URI</code> in the CMS <code class="admin-list-code">.env</code>, then run
          <code class="admin-list-code">php artisan config:clear</code>.
        </div>
        <div v-else class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <a
            v-if="!gscConnected"
            :href="route('seo.analytics.google.connect')"
            class="btn btn-primary btn-sm"
          >Connect Google Search Console</a>
          <template v-else>
            <span class="small text-muted">
              Connected<span v-if="gscConnectedEmail"> as <strong>{{ gscConnectedEmail }}</strong></span>
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm" @click="disconnectGsc">Disconnect</button>
          </template>
        </div>
      </div>

      <div class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Integration settings</h2>
        <p class="text-muted small mb-3">
          Property URL must match Search Console exactly (e.g. <code class="admin-list-code">https://yoursite.com/</code> or <code class="admin-list-code">sc-domain:yoursite.com</code>).
          GA4 Measurement ID is used on the public React site.
        </p>

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
            <p class="form-text mb-0">
              Injects <code class="admin-list-code">gtag.js</code> on the public site (same as Google’s snippet) unless this ID is already present in the custom head HTML below.
            </p>
            <InputError v-if="form.errors.ga_measurement_id" :message="form.errors.ga_measurement_id" class="mt-1" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Public site <code>&lt;head&gt;</code> HTML (Search Console meta, GTM, other tags)</label>
            <textarea
              v-model="form.frontend_head_snippet"
              class="form-control font-monospace"
              rows="8"
              spellcheck="false"
              placeholder="e.g. &lt;meta name=&quot;google-site-verification&quot; content=&quot;…&quot; /&gt;&#10;&lt;script&gt; … &lt;/script&gt;"
            />
            <p class="form-text mb-0">
              Rendered in the live React app’s <code class="admin-list-code">document.head</code> for SEO and third-party scripts. Same field as
              <Link :href="route('content-manager.index')" class="text-decoration-none">Content manager → Home → Meta tags</Link>.
            </p>
            <InputError v-if="form.errors.frontend_head_snippet" :message="form.errors.frontend_head_snippet" class="mt-1" />
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
