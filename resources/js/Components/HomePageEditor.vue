<script setup>
import CmsQuillEditor from '@/Components/CmsQuillEditor.vue';

const model = defineModel({ type: String, default: '' });
</script>

<template>
  <CmsQuillEditor
    v-model="model"
    variant="home"
    placeholder='Design your home page. Use "Card" to add card blocks…'
    class="home-page-editor"
  />
</template>
