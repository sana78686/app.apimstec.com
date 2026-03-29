<script setup>
defineProps({
  value: { type: String },
  for: { type: String },
});
</script>

<template>
  <label :for="for" class="input-label">
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
  </label>
</template>

<style scoped>
.input-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #333;
  margin-bottom: 0.375rem;
}
</style>
