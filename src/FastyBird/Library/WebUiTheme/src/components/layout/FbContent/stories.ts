import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import FbLayoutContent from './index.vue'

export default {
  component: FbLayoutContent,
  title: 'Components/Layout/FB Content',
  argTypes: {
    preview: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      description: 'Content body slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    header: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Content header slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    footer: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Content footer slot',
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
  preview: string
  header?: string
  footer?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbLayoutContent },
    setup(): any {
      return { args }
    },
    template: `
      <div style="height: 150px">
        <fb-layout-content>
          <template v-if="${args.preview !== null && typeof args.preview !== 'undefined'}" #preview>${args.preview}</template>
          <template v-if="${args.header !== null && typeof args.header !== 'undefined'}" #header>${args.header}</template>
          <template v-if="${args.footer !== null && typeof args.footer !== 'undefined'}" #footer>${args.footer}</template>
        </fb-layout-content>
      </div>
    `,
  }
}

export const Default = Template.bind({})

Default.args = {
  preview: 'Full height content',
  header: 'Content header slot placed on top',
  footer: 'Content footer slot placed on bottom',
}
