<template>
  <template v-if="teleport">
    <teleport to="#fb-layout-header-heading">
      <div class="fb-theme-layout-phone-header-heading__container">
        <div
          :class="['fb-theme-layout-phone-header-heading__heading', `fb-theme-layout-phone-header-heading__heading-${align}`]"
        >
          <h1 v-if="subHeading === null">
            {{ heading }}
          </h1>

          <h1 v-if="subHeading !== null">
            <span>{{ heading }}</span>
            <small>{{ subHeading }}</small>
          </h1>
        </div>

        <div
          v-if="'icon' in $slots"
          class="fb-theme-layout-phone-header-heading__icon"
        >
          <slot name="icon" />
        </div>
      </div>
    </teleport>
  </template>

  <template v-else>
    <div class="fb-theme-layout-phone-header-heading__container">
      <div
        :class="['fb-theme-layout-phone-header-heading__heading', `fb-theme-layout-phone-header-heading__heading-${align}`]"
      >
        <h1 v-if="subHeading === null">
          {{ heading }}
        </h1>

        <h1 v-if="subHeading !== null">
          <span>{{ heading }}</span>
          <small>{{ subHeading }}</small>
        </h1>
      </div>

      <div
        v-if="'icon' in $slots"
        class="fb-theme-layout-phone-header-heading__icon"
      >
        <slot name="icon" />
      </div>
    </div>
  </template>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {FbLayoutHeaderHeadingAlign} from '@/types'

export default defineComponent({

  name: 'FbLayoutHeaderHeading',

  props: {

    heading: {
      type: String as PropType<string>,
      required: true,
    },

    subHeading: {
      type: String as PropType<string | null>,
      default: null,
    },

    align: {
      type: String as PropType<FbLayoutHeaderHeadingAlign>,
      default: FbLayoutHeaderHeadingAlign.LEFT,
      validator: (value: FbLayoutHeaderHeadingAlign) => {
        // The value must match one of these strings
        return [FbLayoutHeaderHeadingAlign.LEFT, FbLayoutHeaderHeadingAlign.RIGHT, FbLayoutHeaderHeadingAlign.CENTER].includes(value)
      },
    },

    teleport: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
