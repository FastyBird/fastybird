<template>
  <div
    ref="element"
    class="fb-theme-layout-phone-menu__container"
    @keyup.esc="$emit('close', $event)"
  >
    <transition name="fb-theme-layout-phone-menu-backdrop">
      <div
        v-if="show || teleportHasContent"
        class="fb-theme-layout-phone-menu__backdrop"
        @click.prevent="$emit('close', $event)"
      />
    </transition>

    <transition name="fb-theme-layout-phone-menu-content">
      <div
        v-if="show || teleportHasContent"
        class="fb-theme-layout-phone-menu__inner"
      >
        <div class="fb-theme-layout-phone-menu__content">
          <h4
            id="fb-layout-phone-menu-heading"
            class="fb-theme-layout-phone-menu__heading"
          >
            <slot name="heading" />
          </h4>

          <div
            id="fb-layout-phone-menu-items"
            @change="itemsTeleportChanged"
          >
            <slot />
          </div>
        </div>

        <div
          v-if="showClose"
          id="fb-layout-phone-menu-button"
          class="fb-theme-layout-phone-menu__footer"
        >
          <slot name="close-button">
            <fb-ui-button
              :variant="buttonVariants.LINK"
              :size="sizeTypes.LARGE"
              :type="buttonTypes.BUTTON"
              block
              @click.prevent="$emit('close', $event)"
            >
              {{ closeBtnText }}
            </fb-ui-button>
          </slot>
        </div>
      </div>
    </transition>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  nextTick,
  PropType,
  ref,
  watch,
} from 'vue'

import {
  FbSizeTypes,
  FbUiButtonButtonTypes,
  FbUiButtonVariantTypes,
} from '@/types'
import FbUiButton from '@/components/ui/FbButton/index.vue'

export default defineComponent({

  name: 'FbLayoutPhoneMenu',

  components: {
    FbUiButton,
  },

  props: {

    show: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    showClose: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    closeBtnText: {
      type: String as PropType<string>,
      default: 'Close',
    },

  },

  emits: ['close'],

  setup() {
    const element = ref<HTMLElement | null>(null)

    const teleportHasContent = ref<boolean>(false)

    const itemsTeleportChanged = (payload: Event): void => {
      teleportHasContent.value = payload.target !== null
    }

    watch(
        () => teleportHasContent.value,
        (val: boolean): void => {
          nextTick(() => {
            if (val && element.value !== null) {
              element.value.focus()
            }
          })
        },
    )

    return {
      element,
      teleportHasContent,
      itemsTeleportChanged,
      sizeTypes: FbSizeTypes,
      buttonTypes: FbUiButtonButtonTypes,
      buttonVariants: FbUiButtonVariantTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
