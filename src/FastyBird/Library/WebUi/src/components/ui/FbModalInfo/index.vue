<template>
  <fb-ui-modal-window
    :show="show"
    :size="size"
    :layout="layout"
    :show-header="'header' in $slots || 'title' in $slots"
    :transparent-bg="transparentBg"
    :enable-closing="enableClosing"
    :show-footer="enableClosing"
    :show-right-btn="false"
    :left-btn-label="closeBtnLabel"
    @close="$emit('close', $event)"
    @left-submit="$emit('close', $event)"
  >
    <template
      v-if="'title' in $slots"
      #title
    >
      <slot name="title" />
    </template>

    <template
      v-if="'icon' in $slots"
      #icon
    >
      <slot name="icon" />
    </template>

    <template
      v-if="'header' in $slots"
      #header
    >
      <slot name="header" />
    </template>

    <template #body>
      <slot />
    </template>
  </fb-ui-modal-window>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbUiModalLayoutTypes,
  FbSizeTypes,
} from '@/types'
import FbUiModalWindow from '@/components/ui/FbModalWindow/index.vue'

export default defineComponent({

  name: 'FbUiModalInfo',

  components: {
    FbUiModalWindow,
  },

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

    layout: {
      type: String as PropType<FbUiModalLayoutTypes>,
      default: FbUiModalLayoutTypes.DEFAULT,
      validator: (value: FbUiModalLayoutTypes) => {
        // The value must match one of these strings
        return [
          FbUiModalLayoutTypes.DEFAULT,
          FbUiModalLayoutTypes.PHONE,
          FbUiModalLayoutTypes.TABLET,
        ].includes(value)
      },
    },

    enableClosing: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    closeBtnLabel: {
      type: String as PropType<string>,
      default: 'Close',
    },

    transparentBg: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    show: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['close'],

})
</script>
