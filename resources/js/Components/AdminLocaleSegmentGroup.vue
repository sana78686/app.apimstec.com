<script setup>
/**
 * Horizontal segmented control: "All" + locale codes (dark group, single active segment).
 */
defineProps({
  modelValue: { type: String, default: 'all' },
  /** { value: locale code, label: e.g. "ID" | "EN" } — do not include "all" */
  options: { type: Array, default: () => [] },
  ariaLabel: { type: String, default: 'Filter by locale' },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="admin-locale-segment" role="group" :aria-label="ariaLabel">
    <button
      type="button"
      class="admin-locale-segment__btn"
      :class="{ 'is-active': modelValue === 'all' }"
      @click="$emit('update:modelValue', 'all')"
    >
      All
    </button>
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      class="admin-locale-segment__btn"
      :class="{ 'is-active': modelValue === opt.value }"
      @click="$emit('update:modelValue', opt.value)"
    >
      {{ opt.label }}
    </button>
  </div>
</template>

<style scoped>
.admin-locale-segment {
  display: inline-flex;
  flex-wrap: nowrap;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
}

.admin-locale-segment__btn {
  border: none;
  margin: 0;
  padding: 0.42rem 0.95rem;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  background: #3f3f46;
  color: #fafafa;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
  line-height: 1.2;
  cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease;
}

.admin-locale-segment__btn:last-child {
  border-right: none;
}

.admin-locale-segment__btn:hover:not(.is-active) {
  background: #52525b;
  color: #fff;
}

.admin-locale-segment__btn.is-active {
  background: #71717a;
  color: #fff;
}

.admin-locale-segment__btn:focus-visible {
  outline: 2px solid #a5b4fc;
  outline-offset: 2px;
  z-index: 1;
}
</style>
