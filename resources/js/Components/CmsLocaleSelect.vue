<script setup>
import InputError from '@/Components/InputError.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, required: true },
  id: { type: String, default: 'cms_content_locale' },
  label: { type: String, default: 'Content language' },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const page = usePage();
const options = computed(() => page.props.cmsLocaleOptions ?? []);

function onInput(e) {
  emit('update:modelValue', e.target.value);
}
</script>

<template>
  <div class="cms-locale-select">
    <label :for="id" class="form-label small fw-semibold">
      {{ label }} <span class="text-danger" aria-hidden="true">*</span>
    </label>
    <select
      :id="id"
      class="form-select form-select-sm"
      style="max-width: 22rem;"
      :value="modelValue"
      required
      :disabled="disabled"
      @change="onInput"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }} ({{ opt.value }})
      </option>
    </select>
    <p v-if="!options.length" class="text-warning small mb-0 mt-1">No locale options loaded.</p>
    <InputError :message="error" class="mt-1" />
  </div>
</template>
