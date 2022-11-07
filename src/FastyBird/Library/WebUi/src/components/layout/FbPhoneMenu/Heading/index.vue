<template>
  <teleport
    to="#fb-layout-phone-menu-heading"
    :disabled="!teleport"
  >
    <slot />
  </teleport>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

export default defineComponent({

  name: 'FbLayoutPhoneMenuHeading',

  props: {

    teleport: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

})
</script>
