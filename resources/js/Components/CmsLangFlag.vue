<script setup>
import { computed } from 'vue';

const props = defineProps({
  lang: { type: String, required: true },
  width: { type: Number, default: 20 },
});

const codes = { en: 'gb', ms: 'my', es: 'es', fr: 'fr', ar: 'sa', ru: 'ru' };

const code = computed(() => codes[props.lang] || 'un');
const height = computed(() => Math.round((props.width * 15) / 20));
const src = computed(() => `https://flagcdn.com/w40/${code.value}.png`);
</script>

<template>
  <img
    :src="src"
    alt=""
    :width="width"
    :height="height"
    class="cms-lang-flag-img"
    loading="lazy"
    decoding="async"
  />
</template>
