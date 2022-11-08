<template>
  <div
    :data-variant="variant"
    :role="'button' in $slots ? 'button' : undefined"
    data-element="list-item"
    class="fb-theme-ui-item__container"
    @click="onClick"
  >
    <div
      v-if="'icon' in $slots"
      class="fb-theme-ui-item__icon"
    >
      <slot name="icon" />
    </div>

    <div class="fb-theme-ui-item__heading">
      <h2>
        <slot name="heading" />
        <small v-if="'subheading' in $slots">
          <slot name="subheading" />
        </small>
      </h2>
    </div>

    <div
      v-if="'detail' in $slots"
      class="fb-theme-ui-item__content"
    >
      <slot name="detail" />
    </div>

    <div
      v-else-if="'button' in $slots"
      class="fb-theme-ui-item__button"
    >
      <slot name="button" />
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
  SetupContext,
} from 'vue'

import { useEventElementPath } from '@/composables'
import { FbUiItemVariantTypes } from '@/types'

export default defineComponent({

  name: 'FbUiItem',

  props: {

    variant: {
      type: String as PropType<FbUiItemVariantTypes>,
      default: FbUiItemVariantTypes.DEFAULT,
      validator: (value: FbUiItemVariantTypes): boolean => {
        // The value must match one of these strings
        return [
          FbUiItemVariantTypes.DEFAULT,
          FbUiItemVariantTypes.LIST,
        ].includes(value)
      },
    },

  },

  emits: ['click'],

  setup(_props, context: SetupContext) {
    const { getPath } = useEventElementPath()

    const onClick = (event: UIEvent): void => {
      const path = getPath(event)

      if (path.length) {
        const filteredPath: (Element | EventTarget)[] = []

        for (const pathItem of path) {
          if (
            'getAttribute' in pathItem &&
            typeof pathItem.getAttribute === 'function' &&
            pathItem.getAttribute('data-element') === 'list-item'
          ) {
            break
          }

          filteredPath.push(pathItem)
        }

        const isWithRole = filteredPath.find((pathItem): boolean => {
          return 'hasAttribute' in pathItem && typeof pathItem.hasAttribute === 'function' && pathItem.hasAttribute('role')
        })

        if (isWithRole) {
          return
        }
      }

      context.emit('click', event)
    }

    return {
      onClick,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
