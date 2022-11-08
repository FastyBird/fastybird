<template>
  <div
    ref="element"
    class="fb-theme-layout-expandable-box__container"
  >
    <slot />
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  onBeforeUnmount,
  onMounted,
  PropType,
  ref,
  watch,
} from 'vue'

import {IFbLayoutExpandableBoxProps} from './types'

export default defineComponent({

  name: 'ExpandableBox',

  props: {

    show: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  setup(props: IFbLayoutExpandableBoxProps) {
    const element = ref<HTMLElement | null>(null)

    const setBoxHeight = (): void => {
      if (element.value !== null && element.value.parentElement !== null) {
        element.value.style.height = props.show ? '100%' : '0px'
        element.value.style.overflow = props.show ? 'scroll' : 'hidden'
      }
    }

    onMounted((): void => {
      setBoxHeight()

      window.addEventListener('resize', setBoxHeight)
    })

    onBeforeUnmount((): void => {
      window.removeEventListener('resize', setBoxHeight)
    })

    watch(
        (): boolean => props.show,
        (): void => {
          setBoxHeight()
        },
    )

    return {
      element,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
