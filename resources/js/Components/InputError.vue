<script setup>
defineProps({
  message: { type: String },
});
</script>

<template>
  <p v-if="message" class="input-error">{{ message }}</p>
</template>

<style scoped>
.input-error {
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: #c53030;
}
</style>
