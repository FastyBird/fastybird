<template>
  <div
    v-if="'header' in $slots || 'content' in $slots || 'footer' in $slots"
    class="fb-theme-layout-sidebar__container"
  >
    <transition name="fb-theme-layout-sidebar-overlay">
      <div
        v-if="!collapsed"
        class="fb-theme-layout-sidebar__overlay"
        @click.prevent="$emit('collapse', $event)"
      />
    </transition>

    <div :class="['fb-theme-layout-sidebar__inner', {'fb-theme-layout-sidebar__inner-open': !collapsed}]">
      <div
        v-if="'header' in $slots"
        class="fb-theme-layout-sidebar__header"
      >
        <slot name="header" />
      </div>

      <div
        v-if="'content' in $slots"
        class="fb-theme-layout-sidebar__content"
      >
        <slot name="content" />
      </div>

      <div
        v-if="'footer' in $slots"
        class="fb-theme-layout-sidebar__footer"
      >
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

export default defineComponent({

  name: 'FbLayoutSidebar',

  props: {

    collapsed: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['collapse'],

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
