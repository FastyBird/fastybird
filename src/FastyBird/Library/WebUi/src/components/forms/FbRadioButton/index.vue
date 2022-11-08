<template>
  <label
    :data-size="size"
    :data-error="hasError"
    class="fb-theme-form-radio-button__container"
  >
    <input
      :id="id ? id : name"
      v-model="model"
      :name="name"
      :value="option"
      :readonly="readonly"
      :disabled="disabled"
      :tabindex="tabIndex"
      class="fb-theme-form-radio-button__input"
      type="radio"
      @change="onChange"
    >

    <span class="fb-theme-form-radio-button__indicator" />

    <span
      v-if="'default' in $slots || label !== null"
      class="fb-theme-form-radio-button__label"
    >
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  nextTick,
  PropType,
  SetupContext,
} from 'vue'

import {FbSizeTypes} from '@/types'
import FbFormRadioButtonsGroup from '../FbRadioButtonsGroup/index.vue'

import {IFbFormRadioButtonProps} from './types'

export default defineComponent({

  name: 'FbFormRadioButton',

  props: {

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

    option: {
      type: [String, Number, Boolean] as PropType<string | number | boolean>,
      required: true,
    },

    modelValue: {
      type: [String, Number, Boolean] as PropType<string | number | boolean | undefined>,
      default: undefined,
    },

    id: {
      type: String as PropType<string | null>,
      default: null,
    },

    label: {
      type: [String, Number, Boolean] as PropType<string | null>,
      default: null,
    },

    tabIndex: {
      type: Number as PropType<number | undefined>,
      default: undefined,
    },

    hasError: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    readonly: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    group: {
      type: Object as PropType<InstanceType<typeof FbFormRadioButtonsGroup>>,
      default: null,
    },

  },

  emits: ['update:modelValue', 'change'],

  setup(props: IFbFormRadioButtonProps, context: SetupContext) {
    const model = computed<string | number | boolean | undefined>({
      get: (): string | number | boolean | undefined => {
        return props.group !== null ? props.group.modelValue : props.modelValue
      },
      set: (val) => {
        if (props.group !== null) {
          props.group.$emit.apply(props.group, ['update:modelValue', val])
        } else {
          context.emit('update:modelValue', val)
        }
      },
    })

    const onChange = (): void => {
      nextTick(() => {
        if (props.group !== null) {
          props.group.$emit.apply(props.group, ['change', props.group.modelValue])
        } else {
          context.emit('change', props.modelValue)
        }
      })
    }

    return {
      model,
      onChange,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
