<template>
  <div
    :data-size="size"
    class="fb-theme-ui-component-loading-error__container"
  >
    <div class="fb-theme-ui-component-loading-error__icon">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 576 512"
      >
        <path d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z" />
      </svg>
    </div>

    <p class="fb-theme-ui-component-loading-error__text">
      {{ text }}
    </p>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import { FbSizeTypes } from '@/types'

export default defineComponent({

  name: 'FbUiComponentLoadingError',

  props: {

    text: {
      type: String as PropType<string>,
      default: 'Content could not be loaded. Please reload page.',
    },

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
