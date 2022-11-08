<template>
  <div class="fb-theme-layout-content__container">
    <div
      v-if="'header' in $slots"
      class="fb-theme-layout-content__header"
    >
      <slot name="header" />
    </div>

    <div class="fb-theme-layout-content__content">
      <template v-if="'content' in $slots">
        <div class="fb-theme-layout-content__content-inner">
          <slot name="content" />
        </div>
      </template>

      <div
        v-if="'items' in $slots"
        class="fb-theme-layout-content__items"
      >
        <div
          v-if="withSearch"
          class="fb-theme-layout-content__items-search"
        >
          <fb-form-input
            v-model="search"
            :orientation="formOrientationTypes.INLINE"
            :placeholder="searchPlaceholder"
            name="search"
            spellcheck="false"
          >
            <template
              v-if="'search-icon' in $slots"
              #right-addon
            >
              <slot name="search-icon" />
            </template>
          </fb-form-input>
        </div>

        <fb-ui-scroll-shadow class="fb-theme-layout-content__items-content">
          <slot name="items" />
        </fb-ui-scroll-shadow>
      </div>

      <div
        v-if="'preview' in $slots"
        class="fb-theme-layout-content__preview"
      >
        <slot name="preview" />
      </div>
    </div>

    <div
      v-if="'footer' in $slots"
      class="fb-theme-layout-content__footer"
    >
      <slot name="footer" />
    </div>
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

import { FbFormOrientationTypes } from '@/types'

import FbFormInput from '../../forms/FbInput/index.vue'
import FbUiScrollShadow from '../../ui/FbScrollShadow/index.vue'

export default defineComponent({

  name: 'FbLayoutContent',

  components: {
    FbUiScrollShadow,
    FbFormInput,
  },

  props: {

    withSearch: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    searchPlaceholder: {
      type: String as PropType<string>,
      default: 'Search...',
    },

  },

  emits: ['search'],

  setup(_props, context: SetupContext) {
    const search = ref<string>('')

    watch(
      (): string => search.value,
      (val): void => {
        context.emit('search', val)
      },
    )

    return {
      search,
      formOrientationTypes: FbFormOrientationTypes,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
