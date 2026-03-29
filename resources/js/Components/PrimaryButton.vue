<template>
  <button
    type="submit"
    class="btn-primary"
    :class="{ 'btn-primary--loading': loading }"
    :disabled="loading || disabled"
  >
    <span v-if="loading" class="btn-primary-spinner" aria-hidden></span>
    <slot />
  </button>
</template>

<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});
</script>

<style scoped>
.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.625rem 1rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  background: #e03d3d;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, opacity 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #c93535;
}

.btn-primary:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-primary--loading {
  opacity: 0.85;
}

.btn-primary-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: btn-spin 0.6s linear infinite;
}

@keyframes btn-spin {
  to { transform: rotate(360deg); }
}
</style>
