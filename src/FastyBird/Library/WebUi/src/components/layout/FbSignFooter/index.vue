<template>
  <div class="fb-theme-layout-sign-footer__container">
    <slot name="info" />

    <ul>
      <slot name="links" />
    </ul>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({

  name: 'FbLayoutSignFooter',

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
