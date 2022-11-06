import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import {
  FbSizeTypes,
  FbUiVariantTypes,
} from '../../../types'

import FbUiSpinner from './index.vue'

export default {
  component: FbUiSpinner,
  title: 'Components/Ui/FB Spinner',
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
      description: 'Spinner size',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbSizeTypes.MEDIUM },
      },
    },
    variant: {
      type: { name: 'string', required: false },
      control: { type: 'select' },
      defaultValue: FbUiVariantTypes.PRIMARY,
      options: [
        FbUiVariantTypes.DEFAULT,
        FbUiVariantTypes.PRIMARY,
        FbUiVariantTypes.SUCCESS,
        FbUiVariantTypes.DANGER,
        FbUiVariantTypes.WARNING,
        FbUiVariantTypes.INFO,
      ],
      description: 'Spinner color variant',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbUiVariantTypes.DEFAULT },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
    actions: { disabled: true },
  },
} as Meta

interface TemplateArgs extends Args {
  size: FbSizeTypes
  variant: FbUiVariantTypes
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiSpinner },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-spinner
        :size="args.size"
        :variant="args.variant"
      />
    `,
  }
}

export const Default = Template.bind({})

Default.args = {
  variant: FbUiVariantTypes.DEFAULT,
}

export const Primary = Template.bind({})

Primary.args = {
  variant: FbUiVariantTypes.PRIMARY,
}

export const Success = Template.bind({})

Success.args = {
  variant: FbUiVariantTypes.SUCCESS,
}

export const Danger = Template.bind({})

Danger.args = {
  variant: FbUiVariantTypes.DANGER,
}

export const Warning = Template.bind({})

Warning.args = {
  variant: FbUiVariantTypes.WARNING,
}

export const Info = Template.bind({})

Info.args = {
  variant: FbUiVariantTypes.INFO,
}

export const Sizes: Story<TemplateArgs> = () => {
  return {
    components: { FbUiSpinner },
    template: `
      <div style="display: flex;">
        <div style="padding: 5px 15px 5px 0;">
          <fb-ui-spinner size="${FbSizeTypes.LARGE}" />
        </div>
        <div style="padding: 5px 15px 5px 0;">
          <fb-ui-spinner size="${FbSizeTypes.MEDIUM}" />
        </div>
        <div style="padding: 5px 15px 5px 0;">
          <fb-ui-spinner size="${FbSizeTypes.SMALL}" />
        </div>
      </div>
    `,
  }
}

Sizes.parameters = {
  controls: { disabled: true },
}
