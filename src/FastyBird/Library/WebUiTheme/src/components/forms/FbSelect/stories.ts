import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'
import { action } from '@storybook/addon-actions'
import { ref } from 'vue'

import { FbFormOrientationTypes, FbSizeTypes } from '../../../types'

import { IFbFormSelectProps } from './types'
import FbFormSelect from './index.vue'

export default {
  component: FbFormSelect,
  title: 'Components/Form/FB Select',
  argTypes: {
    'left-addon': {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: undefined,
      description: 'Field left addon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    'right-addon': {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: undefined,
      description: 'Field left addon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    'help-line': {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: undefined,
      description: 'Field help info text slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    name: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      defaultValue: 'field-name',
    },
    items: {
      control: { type: 'text' },
      defaultValue: [
        { name: 'Option one', value: 'one' },
        { name: 'Option two', value: 'two' },
        { name: 'Option three', value: 'three' },
      ],
    },
    value: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      defaultValue: null,
    },
    orientation: {
      type: { name: 'string', required: false },
      control: { type: 'select' },
      defaultValue: FbFormOrientationTypes.VERTICAL,
      options: [
        FbFormOrientationTypes.VERTICAL,
        FbFormOrientationTypes.HORIZONTAL,
        FbFormOrientationTypes.INLINE,
      ],
      description: 'Field vs label orientation',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbFormOrientationTypes.VERTICAL },
      },
    },
    size: {
      type: { name: 'string', required: false },
      control: { type: 'select' },
      defaultValue: FbSizeTypes.MEDIUM,
      options: [
        FbSizeTypes.SMALL,
        FbSizeTypes.MEDIUM,
        FbSizeTypes.LARGE,
      ],
      description: 'Field size',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbSizeTypes.MEDIUM },
      },
    },
    id: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: null,
    },
    label: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: 'Select field label',
    },
    required: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: false,
    },
    tabIndex: {
      type: { name: 'number', required: false },
      control: { type: 'text' },
      defaultValue: null,
    },
    error: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: null,
    },
    blankSelect: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: null,
    },
    readonly: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: false,
    },
    disabled: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: false,
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta

interface TemplateArgs extends IFbFormSelectProps, Args {
  'left-addon'?: string
  'right-addon'?: string
  'help-line'?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbFormSelect },
    setup(): any {
      const value = ref<string | null>(null)

      return { args, value }
    },
    template: `
      <fb-form-select
        v-model="value"
        :orientation="args.orientation"
        :size="args.size"
        :name="args.name"
        :id="args.id"
        :label="args.label"
        :items="args.items"
        :required="args.required"
        :tab-index="args.tabIndex"
        :error="args.error"
        :blank-select="args.blankSelect"
        :readonly="args.readonly"
        :disabled="args.disabled"
        @focus="onFocus"
        @blur="onBlur"
      >
        <template v-if="${args['left-addon'] !== null && typeof args['left-addon'] !== 'undefined'}" #left-addon>${args['left-addon']}</template>
        <template v-if="${args['right-addon'] !== null && typeof args['right-addon'] !== 'undefined'}" #right-addon>${args['right-addon']}</template>
        <template v-if="${args['help-line'] !== null && typeof args['help-line'] !== 'undefined'}" #help-line>${args['help-line']}</template>
      </fb-form-select>
    `,
    methods: {
      onFocus: action('field-focused-updated'),
      onBlur: action('field-blurred-updated'),
    },
  }
}

export const Default = Template.bind({})

Default.args = {
  blankSelect: 'Please pick one value',
}

export const Vertical = Template.bind({})

Vertical.args = {
  orientation: FbFormOrientationTypes.VERTICAL,
}

export const Horizontal = Template.bind({})

Horizontal.args = {
  orientation: FbFormOrientationTypes.HORIZONTAL,
}

export const Inline = Template.bind({})

Inline.args = {
  orientation: FbFormOrientationTypes.INLINE,
}

export const WithLeftAddon = Template.bind({})

WithLeftAddon.args = {
  'left-addon': '<font-awesome-icon icon="user" />',
}

export const WithRightAddon = Template.bind({})

WithRightAddon.args = {
  'right-addon': '<font-awesome-icon icon="user" />',
}

export const WithHelpLine = Template.bind({})

WithHelpLine.args = {
  'help-line': 'Please select one option from select',
}
