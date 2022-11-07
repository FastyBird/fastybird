<template>
  <div
    ref="element"
    :data-disabled="disabled"
    class="fb-theme-ui-swipe-actions-out__container"
  >
    <template v-if="'left' in $slots">
      <div
        ref="left"
        class="fb-theme-ui-swipe-actions-out__left"
      >
        <slot
          :close="onClose"
          name="left"
        />
      </div>
    </template>

    <div
      v-if="disabled"
      ref="content"
      class="fb-theme-ui-swipe-actions-out__content"
    >
      <slot
        :revealed="innerRevealed"
        :disabled="disabled"
        :reveal-left="onRevealLeft"
        :reveal-right="onRevealRight"
        :close="onClose"
        name="content"
      />
    </div>

    <div
      v-else
      ref="content"
      v-touch-pan.horizontal.mouse.prevent.mousePrevent="onPan"
      class="fb-theme-ui-swipe-actions-out__content"
    >
      <slot
        :revealed="innerRevealed"
        :disabled="disabled"
        :reveal-left="onRevealLeft"
        :reveal-right="onRevealRight"
        :close="onClose"
        name="content"
      />
    </div>

    <template v-if="'right' in $slots">
      <div
        ref="right"
        class="fb-theme-ui-swipe-actions-out__right"
      >
        <slot
          :close="onClose"
          name="right"
        />
      </div>
    </template>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  onBeforeUnmount,
  PropType,
  ref,
  SetupContext,
  watch,
} from 'vue'

import { TouchHorizontalPan } from '../../../../directives'
import { ITouchHorizontalChanges } from '../../../../directives/types'
import {
  IFbUiSwipeActionsOutProps,
  TFbUiSwipeActionsOutDir,
} from '../types'

const translateX = (x: number): string => {
  if (x === 0) {
    return ''
  }

  return `translate3d(${x}px, 0, 0)`
}

const areEqual = (a: any, b: any): boolean => {
  if (!a && !b) {
    return true
  }

  return a === b
}

export default defineComponent({

  name: 'FbUiSwipeActionsOut',

  directives: {
    'touchPan': TouchHorizontalPan,
  },

  props: {

    threshold: {
      type: Number as PropType<number>,
      default: 45,
    },

    revealed: {
      type: [String, Boolean] as PropType<TFbUiSwipeActionsOutDir | boolean>,
      default: false,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  emits: ['update:revealed', 'active', 'closed', 'revealed', 'leftRevealed', 'rightRevealed'],

  setup(props: IFbUiSwipeActionsOutProps, context: SetupContext) {
    const element = ref<HTMLElement | null>(null)
    const content = ref<HTMLElement | null>(null)
    const left = ref<HTMLElement | null>(null)
    const right = ref<HTMLElement | null>(null)

    const innerRevealed = ref<boolean | TFbUiSwipeActionsOutDir>(props.revealed || false)
    const animationFrame = ref<number | null>(null)

    const leftActionsWidth = ref<number>(0)
    const rightActionsWidth = ref<number>(0)
    const startLeft = ref<number>(0)

    const isActive = ref<boolean>(false)

    const distanceSwiped = (): number => {
      const contentRect = content.value?.getBoundingClientRect()
      const elementRect = element.value?.getBoundingClientRect()

      return (contentRect ? contentRect.left : 0) - (elementRect ? elementRect.left : 0) - (element.value ? element.value?.clientLeft : 0)
    }

    const onPan = (pan: ITouchHorizontalChanges): void => {
      if (props.disabled) {
        return
      }

      if (pan.isFirst) {
        startListener(pan)

        return
      }

      if (!isActive.value) {
        return
      }

      if (pan.isFinal) {
        stopListener(pan)

        return
      }

      swipeListener(pan)
    }

    const startListener = ({ distance }: { distance: { x: number, y: number } }): void => {
      element.value?.classList.add('fb-theme-ui-swipe-actions-out__no-transition')

      if (distance.y <= 5) {
        leftActionsWidth.value = left.value ? left.value.clientWidth : 0
        rightActionsWidth.value = right.value ? right.value.clientWidth : 0

        startLeft.value = distanceSwiped()
        isActive.value = true

        context.emit('active', true)
      }
    }

    const swipeListener = ({ offset }: { offset: { x: number, y: number } }): void => {
      const newX = offset.x + startLeft.value

      if (!('left' in context.slots) && newX > 0) {
        return animateSlide(0)
      }

      if (!('right' in context.slots) && newX < 0) {
        return animateSlide(0)
      }

      return animateSlide(offset.x + startLeft.value)
    }

    const stopListener = ({ offset, distance }: { distance: { x: number, y: number }, offset: { x: number, y: number } }): void => {
      element.value?.classList.remove('fb-theme-ui-swipe-actions-out__no-transition')

      isActive.value = false

      context.emit('active', false)

      const newX = startLeft.value + offset.x

      if ((startLeft.value === 0 && Math.abs(newX) <= (props.threshold as number)) || (distance.x >= (props.threshold as number) && ((startLeft.value > 0 && distance.x < leftActionsWidth.value) || (startLeft.value < 0 && distance.x < rightActionsWidth.value)))) {
        reveal(false, false)

        return
      }

      reveal(newX > 0 ? 'left' : 'right', false)
    }

    const shiftLeftActions = (newX: number): void => {
      if (!('left' in context.slots)) {
        return
      }

      if (newX < 0) {
        newX = 0
      }

      const actions = left.value

      if (actions === null) {
        return
      }

      const actionsWidth = leftActionsWidth.value

      const progress = 1 - Math.min(newX / actionsWidth, 1)
      const deltaX = Math.min(newX, actionsWidth)

      const { children } = actions
      const { length } = children

      for (let i = 0; i < length; i++) {
        const child = children[i] as HTMLElement
        const offsetLeft = actionsWidth - child.offsetLeft - child.offsetWidth

        child.style.transform = translateX(deltaX + (offsetLeft * progress))

        if (length > 1) {
          child.style.zIndex = `${length - i}`
        }
      }
    }

    const shiftRightActions = (newX: number): void => {
      if (!('right' in context.slots)) {
        return
      }

      if (newX > 0) {
        newX = 0
      }

      const actions = right.value

      if (actions === null) {
        return
      }

      const actionsWidth = rightActionsWidth.value

      const progress = 1 + Math.max(newX / actionsWidth, -1)
      const deltaX = Math.max(newX, -actionsWidth)
      const { children } = actions

      for (let i = 0; i < children.length; i++) {
        const child = children[i] as HTMLElement

        child.style.transform = translateX(deltaX - (child.offsetLeft * progress))
      }
    }

    const reveal = (dir: TFbUiSwipeActionsOutDir | boolean, recalculateWidth: boolean): void => {
      if (isActive.value && areEqual(innerRevealed.value, dir)) {
        return
      }

      if (
        (dir === 'left' && left.value === null)
        || (dir === 'right' && right.value === null)
      ) {
        dir = false
      }

      innerRevealed.value = dir

      context.emit('update:revealed', dir)

      // Close
      if (!dir) {
        animateSlide(0)

        context.emit('closed')

        return
      }

      // Left
      if (dir === 'left' && left.value) {
        leftActionsWidth.value = recalculateWidth ? left.value.clientWidth : leftActionsWidth.value
        animateSlide(leftActionsWidth.value)

        context.emit('revealed', { side: 'left', close: onClose })
        context.emit('leftRevealed', { close: onClose })

        return
      }

      // Right
      if (dir === 'right' && right.value) {
        rightActionsWidth.value = recalculateWidth ? right.value.clientWidth : rightActionsWidth.value
        animateSlide(-rightActionsWidth.value)

        context.emit('revealed', { side: 'right', close: onClose })
        context.emit('rightRevealed', { close: onClose })
      }
    }

    const animateSlide = (to: number): void => {
      if (animationFrame.value !== null) {
        cancelAnimationFrame(animationFrame.value)
      }

      animationFrame.value = requestAnimationFrame(() => {
        if (content.value !== null) {
          content.value.style.transform = translateX(to)
        }

        shiftLeftActions(to)
        shiftRightActions(to)
      })
    }

    const onClose = (): void => {
      if (isActive.value)
        return

      reveal(false, true)
    }

    const onRevealLeft = (): void => {
      if (isActive.value || !left.value) {
        return
      }

      reveal('left', true)
    }

    const onRevealRight = (): void => {
      if (isActive.value || !right.value) {
        return
      }

      reveal('right', true)
    }

    onBeforeUnmount((): void => {
      if (animationFrame.value !== null) {
        cancelAnimationFrame(animationFrame.value)
      }
    })

    watch(
      () => props.revealed,
      (val) => {
        if (innerRevealed.value === val) {
          return
        }

        reveal((val as boolean | TFbUiSwipeActionsOutDir), true)
      },
    )

    return {
      innerRevealed,

      element,
      content,
      left,
      right,

      onPan,

      onClose,
      onRevealLeft,
      onRevealRight,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>

<style rel="stylesheet/scss" lang="scss" scoped>
.swipeout-non-selectable {
  user-select: none !important;
}

.swipeout-no-pointer-events {
  pointer-events: none !important;
}
</style>
