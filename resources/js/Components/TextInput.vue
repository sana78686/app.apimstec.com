<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
  type: [String, Number],
  default: '',
});

defineProps({
  invalid: { type: Boolean, default: false },
});

const input = ref(null);

onMounted(() => {
  if (input.value?.hasAttribute('autofocus')) {
    input.value.focus();
    input.value.select();
  }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
  <input
    ref="input"
    class="input-text"
    :class="{ 'input-text--invalid': invalid }"
    :type="type"
    v-model="model"
    v-bind="$attrs"
  />
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    type: { type: String, default: 'text' },
  },
};
</script>

<style scoped>
.input-text {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.9375rem;
  color: #1a1a1a;
  background: #f8f8fa;
  border: 1px solid #ddd;
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-text::placeholder {
  color: #999;
}

.input-text:hover {
  border-color: #ccc;
}

.input-text:focus {
  outline: none;
  border-color: #181826;
  box-shadow: 0 0 0 2px rgba(24, 24, 38, 0.2);
}

/* Red border only when there is a validation error */
.input-text.input-text--invalid,
.input-text.input-text--invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
}
</style>
