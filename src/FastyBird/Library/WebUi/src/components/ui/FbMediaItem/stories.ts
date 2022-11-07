import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'
import { action } from '@storybook/addon-actions'

import FbUiButton from '../FbButton/index.vue'

import FbUiMediaItem from './index.vue'

export default {
  component: FbUiMediaItem,
  title: 'Components/Ui/FB Media item',
  argTypes: {
    left: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Item left box content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    right: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Item right box content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    heading: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Item heading slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    description: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Item description slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    action: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Item action slot',
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
  left?: string
  right?: string
  heading?: string
  description?: string
  action?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiMediaItem, FbUiButton },
    template: `
      <fb-ui-media-item>
        <template v-if="${args.left !== null && typeof args.left !== 'undefined'}" #left>${args.left}</template>
        <template v-if="${args.right !== null && typeof args.right !== 'undefined'}" #right>${args.right}</template>
        <template v-if="${args.heading !== null && typeof args.heading !== 'undefined'}" #heading>${args.heading}</template>
        <template v-if="${args.description !== null && typeof args.description !== 'undefined'}" #description>${args.description}</template>
        <template v-if="${args.action !== null && typeof args.action !== 'undefined'}" #action>${args.action}</template>
      </fb-ui-media-item>
    `,
    methods: {
      onClick: action('button-clicked'),
    },
  }
}

export const Default = Template.bind({})

Default.args = {
  left: `<div><font-awesome-icon icon="magic" size="2x" /></div>`,
  heading: 'All created items',
  description: 'Here could find all created items stored in database',
  action: '<fb-ui-button @click.prevent="onClick">Reload</fb-ui-button>',
}
