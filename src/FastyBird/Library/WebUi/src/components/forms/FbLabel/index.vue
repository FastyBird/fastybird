<template>
  <label
    v-if="'default' in $slots && orientation !== 'inline'"
    :for="id"
    :class="['fb-label__container', {'fb-label__container-required': required}]"
    :data-orientation="orientation"
    :data-size="size"
    :data-error="hasError"
  >
    <slot />
  </label>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbFormOrientationTypes,
  FbSizeTypes,
} from '@/types'

export default defineComponent({

  name: 'FbFormLabel',

  props: {

    id: {
      type: String as PropType<string>,
      required: true,
    },

    orientation: {
      type: String as PropType<FbFormOrientationTypes>,
      default: FbFormOrientationTypes.VERTICAL,
      validator: (value: FbFormOrientationTypes) => {
        // The value must match one of these strings
        return [
          FbFormOrientationTypes.VERTICAL,
          FbFormOrientationTypes.HORIZONTAL,
          FbFormOrientationTypes.INLINE,
        ].includes(value)
      },
    },

    size: {
      type: String as PropType<FbSizeTypes>,
      default: FbSizeTypes.MEDIUM,
      validator: (value: FbSizeTypes) => {
        // The value must match one of these strings
        return [
          FbSizeTypes.LARGE,
          FbSizeTypes.MEDIUM,
          FbSizeTypes.SMALL,
        ].includes(value)
      },
    },

    required: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    hasError: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
