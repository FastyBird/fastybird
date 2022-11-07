import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import {
  FbSizeTypes,
  FbUiVariantTypes,
} from '../../../types'

import { IFbUiNoResultsProps } from './types'
import FbUiNoResults from './index.vue'

export default {
  component: FbUiNoResults,
  title: 'Components/Ui/FB No results',
  argTypes: {
    default: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'No result info text slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    icon: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'Main icon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    'second-icon': {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Optional child icon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    variant: {
      type: { name: 'string', required: false },
      control: { type: 'select' },
      defaultValue: FbUiVariantTypes.DEFAULT,
      options: [
        FbUiVariantTypes.DEFAULT,
        FbUiVariantTypes.PRIMARY,
        FbUiVariantTypes.SUCCESS,
        FbUiVariantTypes.DANGER,
        FbUiVariantTypes.WARNING,
        FbUiVariantTypes.INFO,
      ],
      description: 'Child icon color variant',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbUiVariantTypes.DEFAULT },
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
      description: 'Icons sizes',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbSizeTypes.MEDIUM },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
    actions: { disabled: true },
  },
} as Meta

interface TemplateArgs extends IFbUiNoResultsProps, Args {
  default: string
  icon: string
  'second-icon'?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiNoResults },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-no-results :variant="args.variant" :size="args.size">
        <template v-if="${args.default !== null && typeof args.default !== 'undefined'}" #default>${args.default}</template>
        <template v-if="${args.icon !== null && typeof args.icon !== 'undefined'}" #icon>${args.icon}</template>
        <template v-if="${args['second-icon'] !== null && typeof args['second-icon'] !== 'undefined'}" #second-icon>${args['second-icon']}</template>
      </fb-ui-no-results>
    `,
  }
}

export const Default = Template.bind({})

Default.args = {
  default: 'No result were found in our database',
  icon: '<font-awesome-icon icon="envelope" />',
  'second-icon': '<font-awesome-icon icon="exclamation" />',
}
