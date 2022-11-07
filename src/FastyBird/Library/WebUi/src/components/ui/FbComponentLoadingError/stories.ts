import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { FbSizeTypes } from '../../../types'

import FbUiComponentLoadingError from './index.vue'

import { IFbUiComponentLoadingErrorProps } from './types'

export default {
  component: FbUiComponentLoadingError,
  title: 'Components/Ui/FB Component loading error',
  argTypes: {
    text: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Loading failed text',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'Content could not be loaded. Please reload page.' },
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
      description: 'Icon size',
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

interface TemplateArgs extends IFbUiComponentLoadingErrorProps, Args {
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiComponentLoadingError },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-component-loading-error
        :size="args.size"
        :text="args.text"
      />
    `,
  }
}

export const Default = Template.bind({})
