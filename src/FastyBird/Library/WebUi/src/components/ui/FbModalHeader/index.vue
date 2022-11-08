<template>
  <div
    :data-layout="layout"
    class="fb-theme-ui-modal-header__container"
  >
    <div class="fb-theme-ui-modal-header__inner">
      <template v-if="layout === modalVariantTypes.PHONE || layout === modalVariantTypes.TABLET">
        <div class="fb-theme-ui-modal-header__heading">
          <div
            v-if="'title' in $slots && !('description' in $slots)"
            class="fb-theme-ui-modal-header__heading-title"
          >
            <h4 class="fb-theme-ui-modal-header__heading-title-heading">
              <slot name="title" />

              <small
                v-if="'subtitle' in $slots"
                class="fb-theme-ui-modal-header__heading-title-small"
              >
                <slot name="subtitle" />
              </small>
            </h4>
          </div>
        </div>

        <div class="fb-theme-ui-modal-header__left-button">
          <slot
            v-if="showLeftBtn"
            name="left-button"
          >
            <fb-ui-button
              :variant="buttonVariantTypes.LINK_DEFAULT"
              :size="sizesTypes.MEDIUM"
              uppercase
              @click.prevent="$emit('leftSubmit', $event)"
            >
              {{ leftBtnLabel }}
            </fb-ui-button>
          </slot>
        </div>

        <div class="fb-theme-ui-modal-header__right-button">
          <slot
            v-if="showRightBtn"
            name="right-button"
          >
            <fb-ui-button
              :variant="buttonVariantTypes.LINK_DEFAULT"
              :size="sizesTypes.MEDIUM"
              uppercase
              @click.prevent="$emit('rightSubmit', $event)"
            >
              {{ rightBtnLabel }}
            </fb-ui-button>
          </slot>
        </div>
      </template>

      <template v-else>
        <div
          v-if="!('description' in $slots)"
          class="fb-theme-ui-modal-header__heading"
        >
          <div
            v-if="'icon' in $slots"
            class="fb-theme-ui-modal-header__heading-icon"
          >
            <slot name="icon" />
          </div>

          <div
            v-if="'title' in $slots"
            class="fb-theme-ui-modal-header__heading-title"
          >
            <h4 class="fb-theme-ui-modal-header__heading-title-heading">
              <slot name="title" />

              <small
                v-if="'subtitle' in $slots"
                class="fb-theme-ui-modal-header__heading-title-small"
              >
                <slot name="subtitle" />
              </small>
            </h4>
          </div>
        </div>

        <template v-if="enableClosing">
          <button
            type="button"
            class="fb-theme-ui-modal-header__close"
            @click.prevent="$emit('close', $event)"
          >
            <span aria-hidden="true">×</span>
            <span class="fb-theme-ui-modal-header__close-text">{{ closeBtnLabel }}</span>
          </button>
        </template>
      </template>
    </div>

    <div
      v-if="'description' in $slots"
      class="fb-theme-ui-modal-header__description"
    >
      <div
        v-if="'icon' in $slots"
        class="fb-theme-ui-modal-header__description-icon"
      >
        <slot name="icon" />
      </div>

      <div
        v-if="'title' in $slots"
        class="fb-theme-ui-modal-header__description-title"
      >
        <h4 class="fb-theme-ui-modal-header__description-title-heading">
          <slot name="title" />

          <small
            v-if="'subtitle' in $slots"
            class="fb-theme-ui-modal-header__description-title-small"
          >
            <slot name="subtitle" />
          </small>
        </h4>
      </div>

      <div class="fb-theme-ui-modal-header__description-content">
        <slot name="description" />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
} from 'vue'

import {
  FbUiModalLayoutTypes,
  FbUiButtonVariantTypes,
  FbSizeTypes,
} from '@/types'
import FbUiButton from '../FbButton/index.vue'

export default defineComponent({

  name: 'FbUiModalHeader',

  components: {
    FbUiButton,
  },

  props: {

    layout: {
      type: String as PropType<FbUiModalLayoutTypes>,
      default: FbUiModalLayoutTypes.DEFAULT,
      validator: (value: FbUiModalLayoutTypes) => {
        // The value must match one of these strings
        return [
          FbUiModalLayoutTypes.DEFAULT,
          FbUiModalLayoutTypes.PHONE,
          FbUiModalLayoutTypes.TABLET,
        ].includes(value)
      },
    },

    leftBtnLabel: {
      type: String as PropType<string>,
      default: 'Close',
    },

    showLeftBtn: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    rightBtnLabel: {
      type: String as PropType<string>,
      default: 'Ok',
    },

    showRightBtn: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    closeBtnLabel: {
      type: String as PropType<string>,
      default: 'Close',
    },

    enableClosing: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['rightSubmit', 'leftSubmit', 'close'],

  setup() {
    return {
      sizesTypes: FbSizeTypes,
      modalVariantTypes: FbUiModalLayoutTypes,
      buttonVariantTypes: FbUiButtonVariantTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
