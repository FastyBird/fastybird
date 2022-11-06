<template>
  <div class="fb-theme-layout-header__container">
    <div
      id="fb-layout-header-button-small"
      ref="buttonSmall"
      :class="['fb-theme-layout-header__buttons-small', {'fb-theme-layout-header__buttons-small-expanded': hasSmallButtons}]"
    >
      <slot name="button-small" />
    </div>

    <div class="fb-theme-layout-header__heading">
      <div
        id="fb-layout-header-heading"
        class="fb-theme-layout-header__heading-heading"
      >
        <slot name="heading">
          <slot name="logo" />
        </slot>
      </div>

      <div
        id="fb-layout-header-button-left"
        class="fb-theme-layout-header__heading-button-left"
      >
        <slot name="button-left" />
      </div>

      <div
        id="fb-layout-header-button-right"
        class="fb-theme-layout-header__heading-button-right"
      >
        <slot name="button-right">
          <button
            v-if="!menuButtonHidden"
            :class="['fb-theme-layout-header__button-hamburger', { 'fb-theme-layout-header__button-hamburger-opened': !menuCollapsed }]"
            type="button"
            @click.prevent="$emit('toggleMenu', $event)"
          >
            <span class="fb-theme-layout-header__button-hamburger-bars" />
            <span class="fb-theme-layout-header__button-hamburger-bars" />
            <span class="fb-theme-layout-header__button-hamburger-bars" />
            <span class="fb-theme-layout-header__button-hamburger-label">Toggle navigation</span>
          </button>
        </slot>
      </div>
    </div>

    <div
      id="fb-layout-header-sub-content"
      ref="subContent"
      :class="['fb-theme-layout-header__content', {'fb-theme-layout-header__content-expanded': hasSubContent}]"
    >
      <slot name="sub-content" />
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  onMounted,
  onUnmounted,
  PropType,
  ref,
} from 'vue'

const newMutationObserver = (callback: () => void): MutationObserver | null => {
  // Skip this feature for browsers which
  // do not support ResizeObserver
  // https://caniuse.com/#search=mutationobserver
  if (typeof MutationObserver === 'undefined') {
    return null
  }

  // @ts-ignore
  return new MutationObserver(callback)
}

export default defineComponent({

  name: 'FbLayoutHeader',

  props: {

    menuButtonHidden: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    menuCollapsed: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['toggleMenu'],

  setup() {
    const buttonSmall = ref<HTMLElement | null>(null)
    const subContent = ref<HTMLElement | null>(null)

    const hasSmallButtons = ref<boolean>(false)
    const hasSubContent = ref<boolean>(false)

    let mutationObserver: MutationObserver | null = null

    const mutationObserverCallback = (): void => {
      hasSmallButtons.value = buttonSmall.value !== null && buttonSmall.value?.children.length > 0
      hasSubContent.value = subContent.value !== null && subContent.value?.children.length > 0
    }

    onMounted((): void => {
      hasSmallButtons.value = buttonSmall.value !== null && buttonSmall.value?.children.length > 0
      hasSubContent.value = subContent.value !== null && subContent.value?.children.length > 0

      mutationObserver = newMutationObserver(mutationObserverCallback)

      if (mutationObserver !== null && buttonSmall.value !== null) {
        mutationObserver.observe(buttonSmall.value, { childList: true })
      }

      if (mutationObserver !== null && subContent.value !== null) {
        mutationObserver.observe(subContent.value, { childList: true })
      }
    })

    onUnmounted((): void => {
      if (mutationObserver !== null) {
        mutationObserver.disconnect()
      }
    })

    return {
      buttonSmall,
      subContent,

      hasSmallButtons,
      hasSubContent,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
