<template>
  <div
    ref="container"
    :data-disabled="disabled"
    class="fb-theme-ui-swipe-actions-out__container"
  >
    <template
      v-for="(item, index) in items"
      :key="index"
    >
      <fb-ui-swipe-actions-out
        ref="elements"
        v-model:revealed="innerRevealed[index]"
        :threshold="threshold"
        :disabled="itemDisabled(item) || disabled"
        class="fb-theme-ui-swipe-actions-out__item"
        @closed="onClosed(item, index)"
        @revealed="onRevealed(item, index, $event)"
        @left-revealed="$emit('leftRevealed', { index, item, close: $event.close })"
        @right-revealed="$emit('rightRevealed', { index, item, close: $event.close })"
        @active="$emit('active', $event)"
      >
        <template #content="{ revealed: rowRevealed, disabled: rowDisabled, revealLeft, revealRight, close }">
          <slot
            :item="item"
            :index="index"
            :revealed="rowRevealed"
            :disabled="rowDisabled"
            :reveal-left="revealLeft"
            :reveal-right="revealRight"
            :close="close"
          />
        </template>

        <template
          v-if="'left' in $slots"
          #left="{ close }"
        >
          <slot
            :item="item"
            :index="index"
            :close="close"
            name="left"
          />
        </template>

        <template
          v-if="'right' in $slots"
          #right="{ close }"
        >
          <slot
            :item="item"
            :index="index"
            :close="close"
            name="right"
          />
        </template>
      </fb-ui-swipe-actions-out>
    </template>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
  ref,
  SetupContext,
  watch,
} from 'vue'
import isEmpty from 'lodash/isEmpty'

import FbUiSwipeActionsOut from '../SwipeOut/index.vue'
import {
  IFbUiSwipeActionsListProps,
  TFbUiSwipeActionsOutDir,
} from '../types'

export default defineComponent({

  name: 'FbUiSwipeActionsList',

  components: {
    FbUiSwipeActionsOut,
  },

  props: {

    items: {
      type: Array as PropType<any[]>,
      required: true,
    },

    threshold: {
      type: Number as PropType<number>,
      default: 45,
    },

    revealed: {
      type: Object as PropType<{ [key: number]: TFbUiSwipeActionsOutDir }>,
      default: () => {
        return {}
      },
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    itemDisabled: {
      type: Function as PropType<(item: any) => boolean>,
      default: () => false,
    },

  },

  emits: ['update:revealed', 'active', 'closed', 'revealed', 'leftRevealed', 'rightRevealed'],

  setup(props: IFbUiSwipeActionsListProps<any>, context: SetupContext) {
    const container = ref<HTMLElement | null>(null)
    const elements = ref<InstanceType<typeof FbUiSwipeActionsOut>[]>([])
    const innerRevealed = ref<{ [key: (number)]: TFbUiSwipeActionsOutDir }>(props.revealed || {})

    const onRevealLeft = (index: number): void => {
      if (!(index in elements.value)) {
        return
      }

      elements.value[index].onRevealLeft()
    }

    const onRevealRight = (index: number): void => {
      if (!(index in elements.value)) {
        return
      }

      elements.value[index].onRevealRight()
    }

    const onClose = (index?: number): void => {
      if (isEmpty(elements.value)) {
        return
      }

      if (index === undefined) {
        return Object.values(elements.value).forEach(element => element.onClose())
      }

      if (!(index in elements.value)) {
        return
      }

      elements.value[index].onClose()
    }

    const isRevealed = (index: number): boolean => {
      return index in innerRevealed.value || false
    }

    const onRevealed = (item: any, index: number, event: { side: TFbUiSwipeActionsOutDir, close: () => void }): void => {
      context.emit('revealed', {
        index,
        item,
        side: event.side,
        close: event.close,
      })

      emitRevealed({
        ...innerRevealed.value,
        [index]: event.side,
      })
    }

    const onClosed = (item: any, index: number): void => {
      context.emit('closed', {
        index,
        item,
      })

      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const {[index]: omit, ...newRevealed} = innerRevealed.value

      emitRevealed(newRevealed)
    }

    const emitRevealed = (val: { [key: number]: TFbUiSwipeActionsOutDir }): void => {
      context.emit('update:revealed', val)
    }

    watch(
        () => props.revealed,
        (val): void => {
          innerRevealed.value = val as { [key: (number)]: TFbUiSwipeActionsOutDir }
        },
    )

    watch(
        () => props.items,
        (): void => {
          emitRevealed({})
        },
    )

    return {
      container,
      elements,

      innerRevealed,

      onRevealLeft,
      onRevealRight,
      onClose,

      isRevealed,
      onRevealed,
      onClosed,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
