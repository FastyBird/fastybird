<template>
  <nav class="fb-theme-layout-navigation__container">
    <ul>
      <li class="fb-theme-layout-navigation__heading">
        {{ name }}
      </li>

      <slot />
    </ul>
  </nav>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

export default defineComponent({

  name: 'FbLayoutNavigation',

  props: {

    name: {
      type: String as PropType<string>,
      required: true,
    },

  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
