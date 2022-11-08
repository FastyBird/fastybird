<template>
  <div
    :data-size="size"
    :data-variant="variant"
    class="fb-theme-ui-no-results__container"
  >
    <fb-ui-icon-with-child
      v-if="'second-icon' in $slots"
      :variant="variant"
      :size="size"
    >
      <template #main>
        <slot name="icon" />
      </template>

      <template #child>
        <slot name="second-icon" />
      </template>
    </fb-ui-icon-with-child>

    <template v-else>
      <slot name="icon" />
    </template>

    <p>
      <slot />
    </p>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbSizeTypes,
  FbUiVariantTypes,
} from '@/types'
import FbUiIconWithChild from '../FbIconWithChild/index.vue'

export default defineComponent({

  name: 'FbUiNoResults',

  components: {
    FbUiIconWithChild,
  },

  props: {

    variant: {
      type: String as PropType<FbUiVariantTypes>,
      default: FbUiVariantTypes.DEFAULT,
      validator: (value: FbUiVariantTypes) => {
        // The value must match one of these strings
        return [
          FbUiVariantTypes.DEFAULT,
          FbUiVariantTypes.PRIMARY,
          FbUiVariantTypes.SUCCESS,
          FbUiVariantTypes.DANGER,
          FbUiVariantTypes.WARNING,
          FbUiVariantTypes.INFO,
        ].includes(value)
      },
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
