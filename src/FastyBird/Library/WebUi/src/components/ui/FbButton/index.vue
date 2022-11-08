<template>
  <template v-if="actionType === actionTypes.VUE_LINK">
    <router-link
      ref="element"
      :to="action"
      :data-variant="variant"
      :data-size="size"
      :class="classNames"
      role="button"
      @click.prevent="onClick($event)"
    >
      <div
        v-if="'icon' in $slots && 'default' in $slots"
        class="fb-theme-ui-button__inner"
      >
        <div class="fb-theme-ui-button__inner-icon">
          <slot name="icon" />
        </div>
        <div
          v-if="'default' in $slots"
          class="fb-theme-ui-button__inner-label"
        >
          <slot />
        </div>
      </div>

      <div
        v-else-if="'icon' in $slots"
        class="fb-theme-ui-button__icon"
      >
        <slot name="icon" />
      </div>

      <template v-else>
        <slot />
      </template>

      <span
        v-if="loading"
        class="fb-theme-ui-button__loading"
      >
        <fb-ui-spinner
          :size="size"
          :variant="loader"
        />
      </span>
    </router-link>
  </template>

  <template v-else-if="actionType === actionTypes.LINK">
    <a
      ref="element"
      :href="`${action}`"
      :data-variant="variant"
      :data-size="size"
      :class="classNames"
      role="button"
      @click.prevent="onClick($event)"
    >
      <div
        v-if="'icon' in $slots && 'default' in $slots"
        class="fb-theme-ui-button__inner"
      >
        <div class="fb-theme-ui-button__inner-icon">
          <slot name="icon" />
        </div>
        <div
          v-if="'default' in $slots"
          class="fb-theme-ui-button__inner-label"
        >
          <slot />
        </div>
      </div>

      <div
        v-else-if="'icon' in $slots"
        class="fb-theme-ui-button__icon"
      >
        <slot name="icon" />
      </div>

      <template v-else>
        <slot />
      </template>

      <span
        v-if="loading"
        class="fb-theme-ui-button__loading"
      >
        <fb-ui-spinner
          :size="size"
          :variant="loader"
        />
      </span>
    </a>
  </template>

  <template v-else-if="actionType === actionTypes.BUTTON">
    <button
      ref="element"
      :type="type"
      :data-variant="variant"
      :data-size="size"
      :class="classNames"
      :disabled="disabled"
      role="button"
      @click.prevent="onClick($event)"
    >
      <div
        v-if="'icon' in $slots && 'default' in $slots"
        class="fb-theme-ui-button__inner"
      >
        <div class="fb-theme-ui-button__inner-icon">
          <slot name="icon" />
        </div>
        <div
          v-if="'default' in $slots"
          class="fb-theme-ui-button__inner-label"
        >
          <slot />
        </div>
      </div>

      <div
        v-else-if="'icon' in $slots"
        class="fb-theme-ui-button__icon"
      >
        <slot name="icon" />
      </div>

      <template v-else>
        <slot />
      </template>

      <span
        v-if="loading"
        class="fb-theme-ui-button__loading"
      >
        <fb-ui-spinner
          :size="size"
          :variant="loader"
        />
      </span>
    </button>
  </template>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  ref,
  SetupContext,
} from 'vue'

import {
  FbSizeTypes,
  FbUiVariantTypes,
  FbUiButtonButtonTypes,
  FbUiButtonVariantTypes,
  FbUiButtonActionsTypes,
} from '../../../types'
import FbUiSpinner from '../FbSpinner/index.vue'

import {IFbUiButtonProps} from './types'

export default defineComponent({

  name: 'FbUiButton',

  components: {
    FbUiSpinner,
  },

  props: {

    action: {
      type: [String, Object] as PropType<string | { [key: string]: any } | null>,
      default: null,
    },

    actionType: {
      type: String as PropType<FbUiButtonActionsTypes>,
      default: FbUiButtonActionsTypes.BUTTON,
      validator: (value: FbUiButtonActionsTypes) => {
        // The value must match one of these strings
        return [
          FbUiButtonActionsTypes.BUTTON,
          FbUiButtonActionsTypes.LINK,
          FbUiButtonActionsTypes.VUE_LINK,
        ].includes(value)
      },
    },

    type: {
      type: String as PropType<FbUiButtonButtonTypes>,
      default: FbUiButtonButtonTypes.BUTTON,
      validator: (value: FbUiButtonButtonTypes) => {
        // The value must match one of these strings
        return [
          FbUiButtonButtonTypes.BUTTON,
          FbUiButtonButtonTypes.RESET,
          FbUiButtonButtonTypes.SUBMIT,
        ].includes(value)
      },
    },

    size: {
      type: String as PropType<FbSizeTypes>,
      default: FbSizeTypes.MEDIUM,
      validator: (value: FbSizeTypes) => {
        // The value must match one of these strings
        return [
          FbSizeTypes.EXTRA_SMALL,
          FbSizeTypes.SMALL,
          FbSizeTypes.MEDIUM,
          FbSizeTypes.LARGE,
        ].includes(value)
      },
    },

    variant: {
      type: String as PropType<FbUiButtonVariantTypes>,
      default: FbUiButtonVariantTypes.DEFAULT,
      validator: (value: FbUiButtonVariantTypes) => {
        // The value must match one of these strings
        return [
          FbUiButtonVariantTypes.DEFAULT,
          FbUiButtonVariantTypes.PRIMARY,
          FbUiButtonVariantTypes.SUCCESS,
          FbUiButtonVariantTypes.DANGER,
          FbUiButtonVariantTypes.WARNING,
          FbUiButtonVariantTypes.INFO,
          FbUiButtonVariantTypes.OUTLINE_DEFAULT,
          FbUiButtonVariantTypes.OUTLINE_PRIMARY,
          FbUiButtonVariantTypes.OUTLINE_SUCCESS,
          FbUiButtonVariantTypes.OUTLINE_DANGER,
          FbUiButtonVariantTypes.OUTLINE_WARNING,
          FbUiButtonVariantTypes.OUTLINE_INFO,
          FbUiButtonVariantTypes.LINK,
          FbUiButtonVariantTypes.LINK_DEFAULT,
        ].includes(value)
      },
    },

    block: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    uppercase: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    pill: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    thick: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    active: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    loading: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  emits: ['click'],

  setup(props: IFbUiButtonProps, context: SetupContext) {
    const element = ref<HTMLElement | null>(null)

    const classNames = computed<string[]>((): string[] => {
      const computedClassnames = []

      computedClassnames.push('fb-theme-ui-button')

      if (props.block) {
        computedClassnames.push('fb-theme-ui-button-block')
      }

      if (props.pill) {
        computedClassnames.push('fb-theme-ui-button-pill')
      }

      if (props.thick) {
        computedClassnames.push('fb-theme-ui-button-thick')
      }

      if (props.uppercase) {
        computedClassnames.push('fb-theme-ui-button-uppercase')
      }

      if (props.active) {
        computedClassnames.push('active')
      }

      if (props.loading) {
        computedClassnames.push('fb-theme-ui-button-loading')
      }

      return computedClassnames
    })

    const spinnerVariant = computed<FbUiVariantTypes>((): FbUiVariantTypes => {
      if (
          props.variant === FbUiButtonVariantTypes.PRIMARY ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_PRIMARY ||
          props.variant === FbUiButtonVariantTypes.LINK
      ) {
        return FbUiVariantTypes.PRIMARY
      } else if (
          props.variant === FbUiButtonVariantTypes.WARNING ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_WARNING
      ) {
        return FbUiVariantTypes.WARNING
      } else if (
          props.variant === FbUiButtonVariantTypes.SUCCESS ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_SUCCESS
      ) {
        return FbUiVariantTypes.SUCCESS
      } else if (
          props.variant === FbUiButtonVariantTypes.INFO ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_INFO
      ) {
        return FbUiVariantTypes.INFO
      } else if (
          props.variant === FbUiButtonVariantTypes.DANGER ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_DANGER
      ) {
        return FbUiVariantTypes.DANGER
      } else if (
          props.variant === FbUiButtonVariantTypes.DEFAULT ||
          props.variant === FbUiButtonVariantTypes.OUTLINE_DEFAULT ||
          props.variant === FbUiButtonVariantTypes.LINK_DEFAULT
      ) {
        return FbUiVariantTypes.DEFAULT
      }

      return FbUiVariantTypes.DEFAULT
    })

    const onClick = (e: Event): void => {
      context.emit('click', e)

      if (element.value !== null && typeof element.value.blur !== 'undefined') {
        element.value.blur()
      }
    }

    return {
      element,
      classNames,
      onClick,
      loader: spinnerVariant,
      buttonTypes: FbUiButtonButtonTypes,
      actionTypes: FbUiButtonActionsTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
