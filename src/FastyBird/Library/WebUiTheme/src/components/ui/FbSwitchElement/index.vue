<template>
  <label
    :data-variant="variant"
    :data-size="size"
    class="fb-theme-ui-switch-element__container"
  >
    <input
      :checked="status"
      :disabled="disabled"
      type="checkbox"
      @change.prevent="$emit('change')"
    >
    <span class="fb-theme-ui-switch-element__track" />
    <span class="fb-theme-ui-switch-element__thumb" />
  </label>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbSizeTypes,
  FbUiVariantTypes,
} from '../../../types'

export default defineComponent({

  name: 'FbUiSwitchElement',

  props: {

    status: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

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

  emits: ['change'],

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
