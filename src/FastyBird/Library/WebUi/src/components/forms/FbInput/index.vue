<template>
  <fb-form-field
    :id="id"
    :orientation="orientation"
    :size="size"
    :name="name"
    :label="label"
    :required="required"
    :is-focused="isFocused"
    :has-value="modelValue !== '' && modelValue !== null || placeholder !== null"
    :error="error"
  >
    <template
      v-if="'left-addon' in $slots"
      #left-addon
    >
      <slot name="left-addon" />
    </template>

    <template
      v-if="'right-addon' in $slots"
      #right-addon
    >
      <slot name="right-addon" />
    </template>

    <template #field>
      <input
        :id="id ? id : name"
        :ref="`field-${name}`"
        :data-size="size"
        :data-error="error !== null"
        :name="name"
        :type="type"
        :tabindex="tabIndex"
        :disabled="disabled"
        :readonly="readonly"
        :value="modelValue"
        :placeholder="error !== null && !isFocused ? error : placeholder"
        class="fb-theme-form-input__control"
        @input="onUpdateValue($event.target.value)"
        @focus="onSetFocus(true)"
        @blur="onSetFocus(false)"
        @keydown="onKeyDown"
        @keyup="onKeyUp"
      >
    </template>

    <template
      v-if="'help-line' in $slots"
      #help-line
    >
      <slot name="help-line" />
    </template>
  </fb-form-field>
</template>

<script lang="ts">
import {
  defineComponent,
  PropType,
  ref,
  SetupContext,
} from 'vue'

import {
  FbFormInputTypeTypes,
  FbFormOrientationTypes,
  FbSizeTypes,
} from '../../../types'
import FbFormField from '../FbField/index.vue'

import { IFbFormInputProps } from './types'

export default defineComponent({

  name: 'FbFormInput',

  components: {
    FbFormField,
  },

  props: {

    orientation: {
      type: String as PropType<FbFormOrientationTypes>,
      default: FbFormOrientationTypes.VERTICAL,
      validator: (value: FbFormOrientationTypes) => {
        // The value must match one of these strings
        return [
          FbFormOrientationTypes.HORIZONTAL,
          FbFormOrientationTypes.VERTICAL,
          FbFormOrientationTypes.INLINE,
        ].includes(value)
      },
    },

    size: {
      type: String as PropType<FbSizeTypes>,
      default: FbSizeTypes.MEDIUM,
      validator: (value: FbSizeTypes) => {
        // The value must match one of these strings
        return [
          FbSizeTypes.LARGE,
          FbSizeTypes.MEDIUM,
          FbSizeTypes.SMALL,
        ].includes(value)
      },
    },

    name: {
      type: String as PropType<string>,
      required: true,
    },

    id: {
      type: String as PropType<string | null>,
      default: null,
    },

    label: {
      type: String as PropType<string | null>,
      default: null,
    },

    type: {
      type: String as PropType<FbFormInputTypeTypes>,
      default: FbFormInputTypeTypes.TEXT,
      validator: (value: FbFormInputTypeTypes) => {
        // The value must match one of these strings
        return [
          FbFormInputTypeTypes.TEXT,
          FbFormInputTypeTypes.PASSWORD,
          FbFormInputTypeTypes.EMAIL,
          FbFormInputTypeTypes.NUMBER,
          FbFormInputTypeTypes.TEL,
          FbFormInputTypeTypes.URL,
        ].includes(value)
      },
    },

    required: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    modelValue: {
      type: [String, Number] as PropType<string | number | null>,
      default: null,
    },

    tabIndex: {
      type: Number as PropType<number | null>,
      default: null,
    },

    error: {
      type: String as PropType<string | null>,
      default: null,
    },

    placeholder: {
      type: String as PropType<string | null>,
      default: null,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    readonly: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  emits: ['update:modelValue', 'change', 'focus', 'blur', 'keydown', 'keyup'],

  setup(_props: IFbFormInputProps, context: SetupContext) {
    const isFocused = ref<boolean>(false)

    // Emit an input event up to the parent
    const onUpdateValue = (value: string | number | null): void => {
      context.emit('update:modelValue', value)
      context.emit('change', value)
    }

    // Fire focus & blur events
    const onSetFocus = (value: boolean): void => {
      isFocused.value = value

      if (value) {
        context.emit('focus')
      } else {
        context.emit('blur')
      }
    }

    const onKeyDown = (event: Event): void => {
      context.emit('keydown', event)
    }

    const onKeyUp = (event: Event): void => {
      context.emit('keyup', event)
    }

    return {
      isFocused,
      onUpdateValue,
      onSetFocus,
      onKeyDown,
      onKeyUp,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
  @import 'index';
</style>
