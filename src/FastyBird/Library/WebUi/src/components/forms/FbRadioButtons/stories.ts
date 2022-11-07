import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'
import { action } from '@storybook/addon-actions'

import { FbFormOrientationTypes, FbSizeTypes } from '../../../types'

import { IFbFormRadioButtonsProps } from './types'
import FbFormRadioButtons from './index.vue'

export default {
  component: FbFormRadioButtons,
  title: 'Components/Form/FB Radio buttons',
  argTypes: {
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
    options: {
      control: { type: 'text' },
      defaultValue: [
        { name: 'Radio one', value: 'one' },
        { name: 'Radio two', value: 'two' },
        { name: 'Radio three', value: 'three' },
      ],
    },
    value: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      defaultValue: 'one',
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
      defaultValue: 'Radio buttons field label',
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

interface TemplateArgs extends IFbFormRadioButtonsProps, Args {
  'help-line'?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbFormRadioButtons },
    setup(): any {
      return { args }
    },
    template: `
      <fb-form-radio-buttons
        v-model="args.value"
        :orientation="args.orientation"
        :size="args.size"
        :name="args.name"
        :id="args.id"
        :label="args.label"
        :options="args.options"
        :required="args.required"
        :tab-index="args.tabIndex"
        :error="args.error"
        :readonly="args.readonly"
        :disabled="args.disabled"
        @change="onChange"
      >
        <template v-if="${args['help-line'] !== null && typeof args['help-line'] !== 'undefined'}" #help-line>${args['help-line']}</template>
      </fb-form-radio-buttons>
    `,
    methods: {
      onChange: action('field-changed'),
    },
  }
}

export const Default = Template.bind({})

export const Vertical = Template.bind({})

Vertical.args = {
  name: 'vertical-field',
  orientation: FbFormOrientationTypes.VERTICAL,
}

export const Horizontal = Template.bind({})

Horizontal.args = {
  name: 'horizontal-field',
  orientation: FbFormOrientationTypes.HORIZONTAL,
}

export const Inline = Template.bind({})

Inline.args = {
  name: 'inline-field',
  orientation: FbFormOrientationTypes.INLINE,
}

export const WithHelpLine = Template.bind({})

WithHelpLine.args = {
  name: 'with-help-line',
  'help-line': 'Please select one option from select',
}
