<template>
  <div
    ref="root"
    @keyup.esc="close"
  >
    <transition name="fb-theme-layout-off-canvas-overlay">
      <div
        v-if="show"
        class="fb-theme-layout-off-canvas__overlay"
        @click.prevent="$emit('close')"
      />
    </transition>

    <transition name="fb-theme-layout-off-canvas-body">
      <div
        v-if="show"
        class="fb-theme-layout-off-canvas__body"
      >
        <slot />
      </div>
    </transition>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
  ref,
  watch,
} from 'vue'

import { IFbLayoutOffCanvasProps } from './types'

export default defineComponent({

  name: 'FbLayoutOffCanvas',

  props: {

    show: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  emits: ['close'],

  setup(props: IFbLayoutOffCanvasProps) {
    const root = ref(null)

    watch(() => props.show, (val: boolean): void => {
      if (val && root.value) {
        // @ts-ignore: Object is possibly 'null'
        root.value.tabIndex = 1
      }
    })

    return {
      root,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
