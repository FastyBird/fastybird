<template>
  <div class="fb-theme-layout-preview-toolbar__container">
    <div
      v-if="'left' in $slots"
      class="fb-theme-layout-preview-toolbar__left"
    >
      <slot name="left" />
    </div>

    <div
      v-if="'right' in $slots"
      class="fb-theme-layout-preview-toolbar__right"
    >
      <slot name="right" />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({

  name: 'FbLayoutPreviewToolbar',

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
