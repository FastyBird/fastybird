<template>
  <div
    :data-size="size"
    class="fb-theme-ui-component-loading__container"
  >
    <div class="fb-theme-ui-component-loading__icon">
      <fb-ui-spinner :size="size" />
    </div>

    <p class="fb-theme-ui-component-loading__text">
      {{ text }}
    </p>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {FbSizeTypes} from '@/types'
import FbUiSpinner from '../FbSpinner/index.vue'

export default defineComponent({

  name: 'FbUiComponentLoading',

  components: {
    FbUiSpinner,
  },

  props: {

    text: {
      type: String as PropType<string>,
      default: 'Preparing content, please wait...',
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
