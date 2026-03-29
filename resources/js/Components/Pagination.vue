<script setup>
import { computed } from 'vue'

const props = defineProps({
  total:       { type: Number, required: true },
  perPage:     { type: Number, default: 20 },
  currentPage: { type: Number, default: 1 },
})

const emit = defineEmits(['update:currentPage'])

const totalPages = computed(() => Math.ceil(props.total / props.perPage))

const from = computed(() => props.total === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1)
const to   = computed(() => Math.min(props.currentPage * props.perPage, props.total))

/** Generate page numbers with ellipsis. Always show first, last, and ±2 around current. */
const pages = computed(() => {
  const total = totalPages.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const cur = props.currentPage
  const set = new Set([1, total, cur - 1, cur, cur + 1, cur - 2, cur + 2].filter(p => p >= 1 && p <= total))
  const sorted = [...set].sort((a, b) => a - b)
  const result = []
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…')
    result.push(sorted[i])
  }
  return result
})

function go(p) {
  if (typeof p !== 'number') return
  if (p < 1 || p > totalPages.value || p === props.currentPage) return
  emit('update:currentPage', p)
}
</script>

<template>
  <div v-if="totalPages > 1" class="admin-pagination">
    <span class="admin-pagination-info">
      {{ from }}–{{ to }} of {{ total }}
    </span>

    <div class="admin-pagination-pages">
      <!-- Prev -->
      <button
        class="admin-pagination-btn"
        :disabled="currentPage === 1"
        title="Previous page"
        @click="go(currentPage - 1)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      </button>

      <!-- Page numbers -->
      <template v-for="p in pages" :key="p">
        <span v-if="p === '…'" class="admin-pagination-ellipsis">…</span>
        <button
          v-else
          class="admin-pagination-btn"
          :class="{ 'admin-pagination-btn--active': p === currentPage }"
          @click="go(p)"
        >{{ p }}</button>
      </template>

      <!-- Next -->
      <button
        class="admin-pagination-btn"
        :disabled="currentPage === totalPages"
        title="Next page"
        @click="go(currentPage + 1)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.admin-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .75rem 1rem;
  border-top: 1px solid var(--admin-card-border, #eaeaef);
  font-size: .8125rem;
  flex-wrap: wrap;
  gap: .5rem;
}
.admin-pagination-info { color: #888; }
.admin-pagination-pages { display: flex; align-items: center; gap: .2rem; }
.admin-pagination-ellipsis { padding: 0 .3rem; color: #aaa; }
.admin-pagination-btn {
  min-width: 30px;
  height: 30px;
  padding: 0 .4rem;
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  font-size: .8125rem;
  color: #444;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background .12s, border-color .12s;
}
.admin-pagination-btn:hover:not(:disabled) {
  background: #f5f5fb;
  border-color: #c5c3ff;
}
.admin-pagination-btn:disabled { opacity: .4; cursor: not-allowed; }
.admin-pagination-btn--active {
  background: var(--admin-primary, #4945ff);
  border-color: var(--admin-primary, #4945ff);
  color: #fff;
  font-weight: 600;
}
.admin-pagination-btn--active:hover { opacity: .9; }
</style>
