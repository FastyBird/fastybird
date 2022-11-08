<template>
  <template v-if="teleport">
    <teleport :to="`#${teleportTarget}`">
      <template v-if="actionType === menuItemTypes.LINK">
        <a
          :href="`${action}`"
          :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
          @click.prevent="$emit('click', $event)"
        >
          <template v-if="'icon' in $slots">
            <slot name="icon" />
          </template>

          <template v-else>
            <slot />
          </template>
        </a>
      </template>

      <template v-else-if="actionType === menuItemTypes.VUE_LINK">
        <router-link
          :to="action"
          :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
          @click.prevent="$emit('click', $event)"
        >
          <template v-if="'icon' in $slots">
            <slot name="icon" />
          </template>

          <template v-else>
            <slot />
          </template>
        </router-link>
      </template>

      <template v-else-if="actionType === menuItemTypes.BUTTON">
        <button
          role="button"
          :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
          @click.prevent="$emit('click', $event)"
        >
          <template v-if="'icon' in $slots">
            <slot name="icon" />
          </template>

          <template v-else>
            <slot />
          </template>
        </button>
      </template>

      <template v-else-if="actionType === menuItemTypes.TEXT">
        <div
          :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
        >
          <template v-if="'icon' in $slots">
            <slot name="icon" />
          </template>

          <template v-else>
            <slot />
          </template>
        </div>
      </template>

      <template v-else-if="actionType === menuItemTypes.BLANK">
        <div
          :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
        />
      </template>
    </teleport>
  </template>

  <template v-else>
    <template v-if="actionType === menuItemTypes.LINK">
      <a
        :href="`${action}`"
        :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
        @click.prevent="$emit('click', $event)"
      >
        <template v-if="'icon' in $slots">
          <slot name="icon" />
        </template>

        <template v-else>
          <slot />
        </template>
      </a>
    </template>

    <template v-else-if="actionType === menuItemTypes.VUE_LINK">
      <router-link
        :to="action"
        :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
        @click.prevent="$emit('click', $event)"
      >
        <template v-if="'icon' in $slots">
          <slot name="icon" />
        </template>

        <template v-else>
          <slot />
        </template>
      </router-link>
    </template>

    <template v-else-if="actionType === menuItemTypes.BUTTON">
      <button
        role="button"
        :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
        @click.prevent="$emit('click', $event)"
      >
        <template v-if="'icon' in $slots">
          <slot name="icon" />
        </template>

        <template v-else>
          <slot />
        </template>
      </button>
    </template>

    <template v-else-if="actionType === menuItemTypes.TEXT">
      <div
        :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-textual': !('icon' in $slots)}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
      >
        <template v-if="'icon' in $slots">
          <slot name="icon" />
        </template>

        <template v-else>
          <slot />
        </template>
      </div>
    </template>

    <template v-else-if="actionType === menuItemTypes.BLANK">
      <div
        :class="['fb-theme-layout-header-button__container', {'fb-theme-layout-header-button__container-small': small}, {'fb-theme-layout-header-button__container-left': left}, {'fb-theme-layout-header-button__container-right': right}]"
      />
    </template>
  </template>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {FbMenuItemTypes} from '@/types'

import {IFbLayoutHeaderButtonProps} from './types'

export default defineComponent({

  name: 'FbLayoutHeaderButton',

  props: {

    action: {
      type: [String, Object] as PropType<string | { [key: string]: any } | null>,
      default: null,
    },

    actionType: {
      type: String as PropType<FbMenuItemTypes>,
      default: FbMenuItemTypes.BUTTON,
      validator: (value: FbMenuItemTypes) => {
        // The value must match one of these strings
        return [
          FbMenuItemTypes.BUTTON,
          FbMenuItemTypes.LINK,
          FbMenuItemTypes.VUE_LINK,
        ].includes(value)
      },
    },

    small: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    left: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    right: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    teleport: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['click'],

  setup(props: IFbLayoutHeaderButtonProps) {
    let teleportTarget = 'fb-layout-header-button'

    if (props.small) {
      teleportTarget = `${teleportTarget}-small`
    } else {
      teleportTarget = `${teleportTarget}-${props.left ? 'left' : ''}${props.right ? 'right' : ''}`
    }

    return {
      teleportTarget,
      menuItemTypes: FbMenuItemTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
