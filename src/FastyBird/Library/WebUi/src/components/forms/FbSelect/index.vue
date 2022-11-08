<template>
  <fb-form-field
    :id="id"
    :orientation="orientation"
    :size="size"
    :name="name"
    :label="label"
    :required="required"
    :is-focused="isFocused"
    :has-value="true"
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
      <select
        :id="id ? id : name"
        :ref="`field-${name}`"
        v-model="internalValue"
        :data-size="size"
        :data-error="error !== null"
        :name="name"
        :tabindex="tabIndex"
        :disabled="disabled"
        :readonly="readonly"
        class="fb-theme-form-select__control"
        @input="onUpdateValue"
        @focus="onSetFocus(true)"
        @blur="onSetFocus(false)"
        @change="onChange"
      >
        <option
          v-if="blankSelect !== null"
          value=""
        >
          {{ blankSelect }}
        </option>

        <template v-for="(item, index) in items">
          <optgroup
            v-if="'items' in item && item.items.length"
            :key="`optgrp_${index}`"
            :label="item.name"
          >
            <option
              v-for="(subitem, subindex) in item.items"
              :key="`${index}_${subindex}`"
              :value="subitem.value"
            >
              {{ subitem.name }}
            </option>
          </optgroup>

          <option
            v-else-if="'value' in item"
            :key="index"
            :value="item.value"
          >
            {{ item.name }}
          </option>
        </template>
      </select>
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
  watch,
} from 'vue'
import get from 'lodash.get'

import {
  FbFormOrientationTypes,
  FbSizeTypes,
} from '@/types'
import FbFormField from '../FbField/index.vue'

import {
  IFbFormSelectProps,
  IFbFormSelectItem,
  IFbFormSelectItemGroup,
} from './types'

export default defineComponent({

  name: 'FbFormSelect',

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

    items: {
      type: Array as PropType<(IFbFormSelectItem | IFbFormSelectItemGroup)[]>,
      required: true,
    },

    modelValue: {
      type: [String, Number] as PropType<string | number | undefined>,
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

    blankSelect: {
      type: String as PropType<string | null>,
      default: null,
    },

    readonly: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

    disabled: {
      type: Boolean as PropType<boolean>,
      default: false,
    },

  },

  emits: ['update:modelValue', 'change', 'focus', 'blur', 'change'],

  setup(props: IFbFormSelectProps, context: SetupContext) {
    const internalValue = ref<string | number | undefined>(props.modelValue)
    const isFocused = ref<boolean>(false)

    // Emit an input event up to the parent
    const onUpdateValue = (event: Event): void => {
      context.emit('update:modelValue', get(event.target, 'value'))
      context.emit('change', get(event.target, 'value'))
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

    const onChange = (): void => {
      context.emit('change', props.modelValue)
    }

    watch(
      (): string | number | undefined => props.modelValue,
      (value) => {
        internalValue.value = value
      },
    )

    return {
      internalValue,
      isFocused,
      onUpdateValue,
      onSetFocus,
      onChange,
    }
  },

})
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
