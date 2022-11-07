import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { IFbLayoutFooterProps } from './types'
import FbLayoutFooter from './index.vue'

export default {
  component: FbLayoutFooter,
  title: 'Components/Layout/FB Footer',
  argTypes: {
    default: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Footer custom content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    copyright: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: '© 2017',
      description: 'Copyright text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '© 2017' },
      },
    },
    author: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: 'FastyBird s.r.o.',
      description: 'Application author name',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'FastyBird s.r.o.' },
      },
    },
    website: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: 'https://www.fastybird.com',
      description: 'Application author website',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'https://www.fastybird.com' },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta

interface TemplateArgs extends IFbLayoutFooterProps, Args {
  default?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbLayoutFooter },
    setup(): any {
      return { args }
    },
    template: `
      <fb-layout-footer :copyright="args.copyright" :author="args.author" :website="args.website">
        <template v-if="${args.default !== null && typeof args.default !== 'undefined'}" #default>${args.default}</template>
      </fb-layout-footer>
    `,
  }
}

export const Default = Template.bind({})

export const WithCustomContent = Template.bind({})

WithCustomContent.args = {
  default: `
    Made with lot of <font-awesome-icon icon="mug-hot" /> and <font-awesome-icon icon="heart" />
  `,
}
