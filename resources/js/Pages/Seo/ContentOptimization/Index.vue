<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const selected = ref(null);
const loading = ref(false);
const error = ref('');
const result = ref(null);

async function analyze() {
  if (!selected.value) {
    error.value = 'Select a page or blog to analyze.';
    return;
  }
  error.value = '';
  result.value = null;
  loading.value = true;
  try {
    const { data } = await window.axios.post('/api/seo/content-optimization/analyze', {
      type: selected.value.type,
      id: selected.value.id,
    });
    result.value = data;
  } catch (e) {
    error.value = e.response?.data?.message || 'Analysis failed.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Head title="Content Optimization Tools" />

  <AuthenticatedLayout>
    <template #header>Content Optimization Tools</template>

    <div class="admin-list-page">
      <p v-if="error" class="admin-flash admin-flash-error">{{ error }}</p>

      <div class="admin-list-page-header">
        <div>
          <h1 class="admin-list-page-title">Content Optimization Tools</h1>
          <p class="admin-list-page-desc">
            Get <strong>keyword suggestions</strong>, <strong>readability</strong> (Flesch score), and <strong>heading checks</strong> for your pages and blogs. Select content below and run analysis to see recommendations.
          </p>
        </div>
      </div>

      <div class="admin-box admin-box-smooth mb-4">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Select content to analyze</h2>
        <div class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label small text-muted mb-1">Page or blog</label>
            <select
              v-model="selected"
              class="form-select"
              :disabled="!items || items.length === 0"
            >
              <option :value="null">— Select a page or blog —</option>
              <option
                v-for="item in items"
                :key="`${item.type}-${item.id}`"
                :value="item"
              >
                {{ item.title }} ({{ item.type }}{{ item.slug ? ': ' + item.slug : '' }})
              </option>
            </select>
          </div>
          <div class="col-md-4">
            <PrimaryButton
              :disabled="!selected || loading"
              @click="analyze"
            >
              {{ loading ? 'Analyzing…' : 'Analyze' }}
            </PrimaryButton>
          </div>
        </div>
        <p v-if="items && items.length === 0" class="text-muted small mt-2 mb-0">No pages or blogs found. Add content first.</p>
      </div>

      <div v-if="result" class="admin-box admin-box-smooth">
        <h2 class="admin-form-page-title admin-form-page-title-sm mb-3" style="font-size: 1rem;">Results: {{ result.title }}</h2>

        <!-- Readability -->
        <div class="mb-4">
          <h3 class="h6 fw-semibold mb-2">Readability</h3>
          <div class="row g-2 mb-2">
            <div class="col-6 col-md-3">
              <div class="admin-box admin-box-smooth p-2 small">
                <span class="text-muted">Words</span>
                <div class="fw-semibold">{{ result.readability?.word_count ?? '—' }}</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="admin-box admin-box-smooth p-2 small">
                <span class="text-muted">Sentences</span>
                <div class="fw-semibold">{{ result.readability?.sentence_count ?? '—' }}</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="admin-box admin-box-smooth p-2 small">
                <span class="text-muted">Flesch score</span>
                <div class="fw-semibold">{{ result.readability?.flesch_reading_ease ?? '—' }}</div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="admin-box admin-box-smooth p-2 small">
                <span class="text-muted">Level</span>
                <div class="fw-semibold">{{ result.readability?.readability_level ?? '—' }}</div>
              </div>
            </div>
          </div>
          <ul v-if="result.readability?.suggestions?.length" class="mb-0 ps-3 small text-muted">
            <li v-for="(s, i) in result.readability.suggestions" :key="i">{{ s }}</li>
          </ul>
        </div>

        <!-- Headings -->
        <div class="mb-4">
          <h3 class="h6 fw-semibold mb-2">Headings</h3>
          <div v-if="result.headings?.headings?.length" class="mb-2">
            <div
              v-for="(h, i) in result.headings.headings"
              :key="i"
              class="small mb-1"
              :style="{ paddingLeft: (h.level - 1) * 12 + 'px' }"
            >
              <code class="admin-list-code">H{{ h.level }}</code> {{ h.text }}
            </div>
          </div>
          <p v-else class="text-muted small mb-2">No headings found.</p>
          <ul v-if="result.headings?.issues?.length" class="mb-0 ps-3 small text-warning">
            <li v-for="(issue, i) in result.headings.issues" :key="i">{{ issue }}</li>
          </ul>
        </div>

        <!-- Keyword -->
        <div>
          <h3 class="h6 fw-semibold mb-2">Keyword</h3>
          <p v-if="result.keyword?.focus_keyword" class="small mb-2">
            Focus keyword: <strong>{{ result.keyword.focus_keyword }}</strong>
            · In title: {{ result.keyword.in_title ? 'Yes' : 'No' }}
            · In meta: {{ result.keyword.in_meta ? 'Yes' : 'No' }}
            · Count: {{ result.keyword.count_in_content ?? '—' }}
            · Density: {{ result.keyword.density_percent != null ? result.keyword.density_percent + '%' : '—' }}
          </p>
          <p v-else class="small text-muted mb-2">No focus keyword set for this content.</p>
          <ul v-if="result.keyword?.suggestions?.length" class="mb-0 ps-3 small text-muted">
            <li v-for="(s, i) in result.keyword.suggestions" :key="i">{{ s }}</li>
          </ul>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
