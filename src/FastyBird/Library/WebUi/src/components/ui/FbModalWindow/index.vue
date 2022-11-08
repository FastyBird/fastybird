<template>
  <transition name="fb-theme-ui-modal">
    <section
      v-show="show"
      id="fb-modal-container"
      v-body-scroll-lock="show"
      :class="['fb-theme-ui-modal-window__container', {'fb-theme-ui-modal-window__container-transparent': transparentBg}]"
      :data-size="size"
      :data-layout="layout"
      role="dialog"
      @keyup.esc="closeModal"
      @click="clickOverlay"
    >
      <div class="fb-theme-ui-modal-window__window">
        <div
          ref="element"
          :style="{width: optionalWidth}"
          class="fb-theme-ui-modal-window__dialog"
          role="document"
          tabindex="1"
        >
          <transition name="fb-theme-ui-modal-bounce">
            <div
              v-if="loader"
              class="fb-theme-ui-modal-window__loading"
            >
              <slot name="loader">
                <fb-ui-loading-box :size="sizeTypes.LARGE">
                  <template
                    v-if="'loading-icon' in $slots"
                    #icon
                  >
                    <slot name="loading-icon" />
                  </template>

                  <slot name="loading-content" />
                </fb-ui-loading-box>
              </slot>
            </div>
          </transition>

          <slot name="content">
            <slot
              v-if="showHeader"
              name="header"
            >
              <fb-ui-modal-header
                :layout="layout"
                :right-btn-label="rightBtnLabel"
                :show-right-btn="showRightBtn"
                :left-btn-label="leftBtnLabel"
                :show-left-btn="showLeftBtn"
                :close-btn-label="closeBtnLabel"
                :enable-closing="enableClosing"
                @right-submit="$emit('rightSubmit', $event)"
                @left-submit="$emit('leftSubmit', $event)"
                @close="$emit('close', $event)"
              >
                <template
                  v-if="'title' in $slots"
                  #title
                >
                  <slot name="title" />
                </template>

                <template
                  v-if="'subtitle' in $slots"
                  #subtitle
                >
                  <slot name="subtitle" />
                </template>

                <template
                  v-if="'icon' in $slots"
                  #icon
                >
                  <slot name="icon" />
                </template>

                <template
                  v-if="'left-button' in $slots"
                  #left-button
                >
                  <slot name="left-button" />
                </template>

                <template
                  v-if="'right-button' in $slots"
                  #right-button
                >
                  <slot name="right-button" />
                </template>
              </fb-ui-modal-header>
            </slot>

            <div class="fb-theme-ui-modal-window__body">
              <fb-ui-transition-expand>
                <slot name="body" />
              </fb-ui-transition-expand>
            </div>

            <div
              v-if="showFooter && layout !== modalLayoutTypes.PHONE && layout !== modalLayoutTypes.TABLET"
              class="fb-theme-ui-modal-window__footer"
            >
              <slot name="footer">
                <slot
                  v-if="showLeftBtn"
                  name="left-button"
                >
                  <fb-ui-button
                    :variant="buttonVariantTypes.LINK_DEFAULT"
                    :size="sizeTypes.LARGE"
                    uppercase
                    tabindex="2"
                    @click.prevent="$emit('leftSubmit', $event)"
                  >
                    {{ leftBtnLabel }}
                  </fb-ui-button>
                </slot>

                <slot
                  v-if="showRightBtn"
                  name="right-button"
                >
                  <fb-ui-button
                    :variant="buttonVariantTypes.OUTLINE_PRIMARY"
                    :size="sizeTypes.LARGE"
                    uppercase
                    tabindex="3"
                    @click.prevent="$emit('rightSubmit', $event)"
                  >
                    {{ rightBtnLabel }}
                  </fb-ui-button>
                </slot>
              </slot>
            </div>
          </slot>
        </div>
      </div>
    </section>
  </transition>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  onMounted,
  PropType,
  ref,
  SetupContext,
} from 'vue'

import get from 'lodash/get'

import {
  FbSizeTypes,
  FbUiModalLayoutTypes,
  FbUiButtonVariantTypes,
} from '@/types'
import FbUiButton from '@/components/ui/FbButton/index.vue'
import FbUiModalHeader from '@/components/ui/FbModalHeader/index.vue'
import FbUiLoadingBox from '@/components/ui/FbLoadingBox/index.vue'
import FbUiTransitionExpand from '@/components/ui/FbTransitionExpand/index.vue'
import {BodyScrollLock as BodyScrollLockDirective} from '@/directives'

import {IFbUiModalWindowProps} from './types'

export default defineComponent({

  name: 'FbUiModalWindow',

  components: {
    FbUiButton,
    FbUiModalHeader,
    FbUiLoadingBox,
    FbUiTransitionExpand,
  },

  directives: {
    'bodyScrollLock': BodyScrollLockDirective,
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

    width: {
      type: [String, Number] as PropType<string | number | null>,
      default: null,
    },

    showHeader: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

    showFooter: {
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

    leftBtnLabel: {
      type: String as PropType<string>,
      default: 'Close',
    },

    showLeftBtn: {
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

    transparentBg: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    loader: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    show: {
      type: Boolean as PropType<boolean>,
      default: true,
    },

  },

  emits: ['rightSubmit', 'leftSubmit', 'close'],

  setup(props: IFbUiModalWindowProps, context: SetupContext) {
    const element = ref<HTMLElement | null>(null)

    const optionalWidth = computed<string | undefined>((): string | undefined => {
      if (props.width === null) {
        return undefined
      } else if (typeof props.width === 'number') {
        return `${props.width}px`
      }

      return `${props.width}`
    })

    const clickOverlay = (e: Event): void => {
      if (get(e, 'target.id', null) === 'fb-modal-container') {
        if (props.enableClosing) {
          context.emit('close', e)
        }
      }
    }

    const closeModal = (e: KeyboardEvent): void => {
      if (props.enableClosing) {
        context.emit('close', e)
      }
    }

    onMounted((): void => {
      nextTick(() => {
        if (element.value !== null) {
          element.value.focus()
        }
      })
    })

    return {
      element,
      optionalWidth,
      clickOverlay,
      closeModal,
      sizeTypes: FbSizeTypes,
      modalLayoutTypes: FbUiModalLayoutTypes,
      buttonVariantTypes: FbUiButtonVariantTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
