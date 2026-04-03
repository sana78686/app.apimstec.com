<script setup>
import CmsQuillEditor from '@/Components/CmsQuillEditor.vue';

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <CmsQuillEditor v-model="model" variant="full" />
</template>
