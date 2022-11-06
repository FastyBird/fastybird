import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { FbSizeTypes } from '../../../types'

import FbUiComponentLoading from './index.vue'

import { IFbUiComponentLoadingProps } from './types'

export default {
  component: FbUiComponentLoading,
  title: 'Components/Ui/FB Component loading',
  argTypes: {
    text: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Loading text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'Preparing content, please wait...' },
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
      description: 'Spinner size',
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

interface TemplateArgs extends IFbUiComponentLoadingProps, Args {
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiComponentLoading },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-component-loading
        :size="args.size"
        :text="args.text"
      />
    `,
  }
}

export const Default = Template.bind({})
