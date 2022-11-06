import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'
import { action } from '@storybook/addon-actions'

import {
  FbSizeTypes,
  FbUiButtonVariantTypes,
} from '../../../types'
import FbUiButton from '../FbButton/index.vue'

import FbUiItemsContainer from './index.vue'

export default {
  component: FbUiItemsContainer,
  title: 'Components/Ui/FB Item container',
  argTypes: {
    default: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'Items content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    heading: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'Items heading slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    subheading: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'Items sub-heading slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    buttons: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Items buttons slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta

interface TemplateArgs extends Args {
  default: string
  heading?: string
  subheading?: string
  buttons?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiItemsContainer, FbUiButton },
    template: `
      <fb-ui-items-container>
        <template v-if="${args.default !== null && typeof args.default !== 'undefined'}" #default>${args.default}</template>
        <template v-if="${args.heading !== null && typeof args.heading !== 'undefined'}" #heading>${args.heading}</template>
        <template v-if="${args.subheading !== null && typeof args.subheading !== 'undefined'}" #subheading>${args.subheading}</template>
        <template v-if="${args.buttons !== null && typeof args.buttons !== 'undefined'}" #buttons>${args.buttons}</template>
      </fb-ui-items-container>
    `,
    methods: {
      onClick: action('button-clicked'),
    },
  }
}

export const Default = Template.bind({})

Default.args = {
  heading: 'List of awesome items',
  default: 'Some item content',
  buttons: `
    <fb-ui-button size="${FbSizeTypes.SMALL}" @click.prevent="onClick" style="margin-right: 5px">Add</fb-ui-button>
    <fb-ui-button size="${FbSizeTypes.SMALL}" variant="${FbUiButtonVariantTypes.WARNING}" :icon="true"  @click.prevent="onClick">
      <template #icon>
        <font-awesome-icon icon="cog" />
      </template>
    </fb-ui-button>
  `,
}

export const WithSubheading = Template.bind({})

WithSubheading.args = {
  heading: 'List of awesome items',
  subheading: 'List of awesome items',
  default: 'Some item content',
  buttons: `
    <fb-ui-button size="${FbSizeTypes.SMALL}" @click.prevent="onClick" style="margin-right: 5px">Add</fb-ui-button>
    <fb-ui-button size="${FbSizeTypes.SMALL}" variant="${FbUiButtonVariantTypes.WARNING}" :icon="true"  @click.prevent="onClick">
      <template #icon>
        <font-awesome-icon icon="cog" />
      </template>
    </fb-ui-button>
  `,
}
