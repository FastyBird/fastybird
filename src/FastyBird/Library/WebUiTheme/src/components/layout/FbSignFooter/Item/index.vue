<template>
  <li class="fb-theme-layout-sign-footer-item__container">
    <slot />
  </li>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({

  name: 'FbLayoutSignFooterItem',

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
