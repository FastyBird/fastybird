<template>
  <div
    role="group"
    aria-label="radiobutton-group"
  >
    <slot />
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

export default defineComponent({

  name: 'FbFormRadioButtonsGroup',

  props: {

    modelValue: {
      type: [String, Number, Boolean] as PropType<string | number | boolean | undefined>,
      default: undefined,
    },

  },

})
</script>
