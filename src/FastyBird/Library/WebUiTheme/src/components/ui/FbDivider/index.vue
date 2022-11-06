<template>
  <hr
    v-if="variant === variantTypes.GRADIENT"
    :data-type="type"
    :data-variant="variant"
    class="fb-theme-ui-divider__container"
  >

  <div
    v-else
    :data-type="type"
    :data-variant="variant"
    class="fb-theme-ui-divider__container"
  >
    <div class="fb-theme-ui-divider__content">
      <slot />
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import { FbUiDividerTypeTypes, FbUiDividerVariantTypes } from '../../../types'

export default defineComponent({

  name: 'FbUiDivider',

  props: {

    type: {
      type: String as PropType<FbUiDividerTypeTypes>,
      default: FbUiDividerTypeTypes.HORIZONTAL,
      validator: (value: FbUiDividerTypeTypes) => {
        // The value must match one of these strings
        return [
          FbUiDividerTypeTypes.HORIZONTAL,
          FbUiDividerTypeTypes.VERTICAL,
        ].includes(value)
      },
    },

    variant: {
      type: String as PropType<FbUiDividerVariantTypes>,
      default: FbUiDividerVariantTypes.DEFAULT,
      validator: (value: FbUiDividerVariantTypes) => {
        // The value must match one of these strings
        return [
          FbUiDividerVariantTypes.DEFAULT,
          FbUiDividerVariantTypes.GRADIENT,
        ].includes(value)
      },
    },

  },

  setup() {
    return {
      variantTypes: FbUiDividerVariantTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
