<template>
  <div :class="['fb-theme-layout-tabs__container', {'fb-theme-layout-tabs__container-collapsed': collapsed}]">
    <div
      id="fb-layout-tabs-items"
      class="fb-theme-layout-tabs__container-inner"
    >
      <slot />
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

export default defineComponent({

  name: 'FbLayoutTabs',

  props: {

    collapsed: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
