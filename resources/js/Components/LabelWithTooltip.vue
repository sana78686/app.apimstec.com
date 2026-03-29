<script setup>
defineProps({
  for: { type: String, default: '' },
  value: { type: String, default: '' },
  tip: { type: String, default: '' },
  required: { type: Boolean, default: false },
  optional: { type: Boolean, default: false },
});
</script>

<template>
  <div class="admin-label-tooltip-wrap">
    <label :for="for" class="admin-label-tooltip-label">
      <span v-if="value">{{ value }}</span>
      <span v-else><slot /></span>
      <span v-if="required" class="admin-label-required" aria-hidden="true">*</span>
      <span v-if="optional && !required" class="admin-label-optional">optional</span>
    </label>
    <span v-if="tip" class="admin-tooltip-trigger" tabindex="0" aria-label="Help" :data-tip="tip">?</span>
  </div>
</template>

<style scoped>
.admin-label-tooltip-wrap {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.25rem;
  position: relative;
}
.admin-label-tooltip-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--admin-text-muted, #666687);
  cursor: default;
}
.admin-label-required {
  color: #dc3545;
  margin-left: 0.15rem;
}
.admin-label-optional {
  font-size: 0.625rem;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  color: var(--admin-text-muted, #666687);
  margin-left: 0.35rem;
}
.admin-tooltip-trigger {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.125rem;
  height: 1.125rem;
  font-size: 0.625rem;
  font-weight: 600;
  color: var(--admin-text-muted, #666687);
  background: var(--admin-main-bg, #f6f6f9);
  border: 1px solid var(--admin-card-border, #eaeaef);
  border-radius: 50%;
  cursor: help;
  transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}
.admin-tooltip-trigger:hover,
.admin-tooltip-trigger:focus {
  color: var(--admin-text, #32324d);
  border-color: var(--admin-primary, #181826);
  outline: none;
}
.admin-tooltip-trigger::before {
  content: attr(data-tip);
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.5rem);
  transform: translateX(-50%) scale(0.96);
  min-width: 10rem;
  max-width: 20rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 400;
  line-height: 1.4;
  color: #fff;
  background: #181826;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: normal;
  text-align: center;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
  z-index: 1000;
}
.admin-tooltip-trigger::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.25rem);
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #181826;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s;
  z-index: 1001;
}
.admin-tooltip-trigger:hover::before,
.admin-tooltip-trigger:focus::before,
.admin-tooltip-trigger:hover::after,
.admin-tooltip-trigger:focus::after {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) scale(1);
}
.admin-tooltip-trigger:focus::after {
  transform: translateX(-50%);
}
</style>
