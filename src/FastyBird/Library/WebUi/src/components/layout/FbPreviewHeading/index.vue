<template>
  <div class="fb-theme-layout-preview-heading__container">
    <div class="fb-theme-layout-preview-heading__icon">
      <slot name="icon" />
    </div>

    <div class="fb-theme-layout-preview-heading__heading">
      <h2>
        <slot name="heading" />
        <small v-if="'subheading' in $slots">
          <slot name="subheading" />
        </small>
      </h2>
    </div>

    <div
      v-if="'buttons' in $slots"
      class="fb-theme-layout-preview-heading__buttons"
    >
      <slot name="buttons" />
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({

  name: 'FbLayoutPreviewHeading',

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
