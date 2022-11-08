<template>
  <div
    :data-size="size"
    class="fb-theme-ui-result-err__container"
  >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 130.2 130.2"
    >
      <circle
        cx="65.1"
        cy="65.1"
        r="62.1"
      />

      <line
        x1="34.4"
        y1="37.9"
        x2="95.8"
        y2="92.3"
      />

      <line
        x1="95.8"
        y1="38"
        x2="34.4"
        y2="92.2"
      />
    </svg>

    <div
      v-if="'default' in $slots"
      class="fb-theme-ui-result-err__content"
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

import {FbSizeTypes} from '@/types'

export default defineComponent({

  name: 'FbUiResultErr',

  props: {

    size: {
      type: String as PropType<FbSizeTypes>,
      default: FbSizeTypes.MEDIUM,
      validator: (value: FbSizeTypes) => {
        // The value must match one of these strings
        return [
          FbSizeTypes.SMALL,
          FbSizeTypes.MEDIUM,
          FbSizeTypes.LARGE,
        ].includes(value)
      },
    },

  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
