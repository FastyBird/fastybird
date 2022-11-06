import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { FbSizeTypes } from '../../../types'

import { IFbUiResultErrProps } from './types'
import FbUiResultErr from './index.vue'

export default {
  component: FbUiResultErr,
  title: 'Components/Ui/FB Result error',
  argTypes: {
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

interface TemplateArgs extends IFbUiResultErrProps, Args {
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiResultErr },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-result-err :size="args.size" />
    `,
  }
}

export const Default = Template.bind({})
