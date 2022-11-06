<template>
  <fb-ui-modal-window
    :show="show"
    :show-footer="false"
    :show-header="false"
    :size="size"
    :transparent-bg="transparentBg"
    :enable-closing="enableClosing"
    @close="$emit('close', $event)"
  >
    <template #body>
      <div
        :data-variant="variant"
        class="fb-theme-ui-confirmation-window__container"
      >
        <div
          v-if="'icon' in $slots"
          class="fb-theme-ui-confirmation-window__icon"
        >
          <slot name="icon" />
        </div>

        <h3>
          <slot name="title" />
        </h3>

        <slot />

        <div class="fb-theme-ui-confirmation-window__buttons">
          <template v-if="primaryButton === 'no'">
            <fb-ui-button
              v-if="showYes"
              :variant="buttonVariantTypes.LINK_DEFAULT"
              :size="sizeTypes.LARGE"
              uppercase
              tabindex="2"
              @click.prevent="$emit('confirm', $event)"
            >
              {{ yesBtnLabel }}
            </fb-ui-button>

            <fb-ui-button
              v-if="showNo"
              :variant="buttonVariant"
              :size="sizeTypes.LARGE"
              uppercase
              tabindex="3"
              @click.prevent="$emit('close', $event)"
            >
              {{ noBtnLabel }}
            </fb-ui-button>
          </template>

          <template v-else>
            <fb-ui-button
              v-if="showNo"
              :variant="buttonVariantTypes.LINK_DEFAULT"
              :size="sizeTypes.LARGE"
              uppercase
              tabindex="2"
              @click.prevent="$emit('close', $event)"
            >
              {{ noBtnLabel }}
            </fb-ui-button>

            <fb-ui-button
              v-if="showYes"
              :variant="buttonVariant"
              :size="sizeTypes.LARGE"
              uppercase
              tabindex="3"
              @click.prevent="$emit('confirm', $event)"
            >
              {{ yesBtnLabel }}
            </fb-ui-button>
          </template>
        </div>
      </div>
    </template>
  </fb-ui-modal-window>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbSizeTypes,
  FbUiButtonVariantTypes,
  FbUiConfirmationWindowPrimaryButtonTypes,
  FbUiVariantTypes,
} from '../../../types'
import FbUiButton from '../FbButton/index.vue'
import FbUiModalWindow from '../FbModalWindow/index.vue'

import { IFbUiConfirmationWindowProps } from './types'

export default defineComponent({

  name: 'FbUiConfirmationWindow',

  components: {
    FbUiButton,
    FbUiModalWindow,
  },

  props: {

    size: {
      type: String as PropType<FbSizeTypes>,
      default: FbSizeTypes.MEDIUM,
      validator: (value: FbSizeTypes) => {
        // The value must match one of these strings
        return [
          FbSizeTypes.SMALL,
          FbSizeTypes.MEDIUM,
          FbSizeTypes.LARGE,
        ].includes(value)
      },
    },

    primaryButton: {
      type: String as PropType<FbUiConfirmationWindowPrimaryButtonTypes>,
      default: FbUiConfirmationWindowPrimaryButtonTypes.NO,
      validator: (value: FbUiConfirmationWindowPrimaryButtonTypes) => {
        // The value must match one of these strings
        return [
          FbUiConfirmationWindowPrimaryButtonTypes.YES,
          FbUiConfirmationWindowPrimaryButtonTypes.NO,
        ].includes(value)
      },
    },

    variant: {
      type: String as PropType<FbUiVariantTypes>,
      default: FbUiVariantTypes.DANGER,
      validator: (value: FbUiVariantTypes) => {
        // The value must match one of these strings
        return [
          FbUiVariantTypes.DEFAULT,
          FbUiVariantTypes.PRIMARY,
          FbUiVariantTypes.SUCCESS,
          FbUiVariantTypes.DANGER,
          FbUiVariantTypes.WARNING,
          FbUiVariantTypes.INFO,
        ].includes(value)
      },
    },

    showYes: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    yesBtnLabel: {
      type: String as PropType<string>,
      default: 'Yes',
    },

    showNo: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    noBtnLabel: {
      type: String as PropType<string>,
      default: 'No',
    },

    enableClosing: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    transparentBg: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    show: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['confirm', 'close'],

  setup(props: IFbUiConfirmationWindowProps) {
    let buttonVariant = FbUiButtonVariantTypes.DEFAULT

    switch (props.variant) {
      case FbUiVariantTypes.DEFAULT:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_DEFAULT
        break

      case FbUiVariantTypes.PRIMARY:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_PRIMARY
        break

      case FbUiVariantTypes.SUCCESS:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_SUCCESS
        break

      case FbUiVariantTypes.DANGER:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_DANGER
        break

      case FbUiVariantTypes.WARNING:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_WARNING
        break

      case FbUiVariantTypes.INFO:
        buttonVariant = FbUiButtonVariantTypes.OUTLINE_INFO
        break
    }

    return {
      buttonVariant,
      sizeTypes: FbSizeTypes,
      buttonVariantTypes: FbUiButtonVariantTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
