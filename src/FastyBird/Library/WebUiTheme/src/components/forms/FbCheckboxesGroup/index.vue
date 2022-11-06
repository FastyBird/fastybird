<template>
  <div
    role="group"
    aria-label="checkbox-group"
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

  name: 'FbFormCheckboxesGroup',

  props: {

    modelValue: {
      type: Array as PropType<(string | number | boolean)[]>,
      default: () => {
        return []
      },
    },

  },

})
</script>
