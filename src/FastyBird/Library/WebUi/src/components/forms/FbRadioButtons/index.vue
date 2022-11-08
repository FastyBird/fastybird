<template>
  <fb-form-field
    :id="id"
    :orientation="orientation"
    :size="size"
    :name="name"
    :label="label"
    :required="required"
    :has-value="true"
    :error="error"
  >
    <template #field>
      <div
        role="group"
        aria-label="radiobutton-group"
        class="fb-theme-form-radio-buttons__control"
      >
        <template
          v-for="(option, index) in options"
          :key="index"
        >
          <fb-form-radio-button
            :id="`${id ? id : name}_${index}`"
            v-model="model"
            :name="name"
            :option="option.value"
            :size="size"
            :label="option.name"
            :tab-index="tabIndex ? tabIndex + index + 1 : undefined"
            :has-error="error !== null"
            :readonly="readonly"
            :disabled="disabled"
            @change="onChange"
          />
        </template>
      </div>
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
  computed,
  defineComponent,
  PropType,
  SetupContext,
} from 'vue'

import {
  FbFormOrientationTypes,
  FbSizeTypes,
} from '@/types'
import FbFormField from '../FbField/index.vue'
import FbFormRadioButton from '../FbRadioButton/index.vue'

import {
  IFbFormRadioButtonsItem,
  IFbFormRadioButtonsProps,
} from './types'

export default defineComponent({

  name: 'FbFormRadioButtons',

  components: {
    FbFormField,
    FbFormRadioButton,
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

    options: {
      type: Array as PropType<IFbFormRadioButtonsItem[]>,
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
      type: String as PropType<string | null>,
      default: null,
    },

    required: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    tabIndex: {
      type: Number as PropType<number | undefined>,
      default: undefined,
    },

    error: {
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

  emits: ['update:modelValue', 'change'],

  setup(props: IFbFormRadioButtonsProps, context: SetupContext) {
    const model = computed<string | number | boolean | undefined>({
      get: (): string | number | boolean | undefined => {
        return props.modelValue
      },
      set: (val) => {
        context.emit('update:modelValue', val)
      },
    })

    const onChange = (): void => {
      context.emit('change', props.modelValue)
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
