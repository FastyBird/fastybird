import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { FbUiVariantTypes } from '../../../types'

import { IFbUiAlertProps } from './types'
import FbUiAlert from './index.vue'

export default {
  component: FbUiAlert,
  title: 'Components/Ui/FB Alert',
  argTypes: {
    default: {
      type: { name: 'string', required: true },
      control: { type: 'text' },
      defaultValue: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus nunc massa, sollicitudin eget ex eget, pellentesque ultrices felis. Nunc at tempus purus. Nulla diam sem, consectetur id magna vitae, pellentesque viverra ipsum.',
      description: 'Alert content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    icon: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: undefined,
      description: 'Alert icon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
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
      description: 'Alert color variant',
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

interface TemplateArgs extends IFbUiAlertProps, Args {
  default: string
  icon?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiAlert },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-alert :variant="args.variant" style="font-size: 14px; line-height: 20px;">
      <template v-if="${args.icon !== null && typeof args.icon !== 'undefined'}" #icon>${args.icon}</template>
      <p style="padding: 0; margin: 0;">${args.default}</p>
      </fb-ui-alert>
    `,
  }
}

export const Default = Template.bind({})

Default.args = {
  default: 'A simple default alert—check it out!',
  variant: FbUiVariantTypes.DEFAULT,
}

export const Primary = Template.bind({})

Primary.args = {
  default: 'A simple primary alert—check it out!',
  variant: FbUiVariantTypes.PRIMARY,
}

export const Success = Template.bind({})

Success.args = {
  default: 'A simple success alert—check it out!',
  variant: FbUiVariantTypes.SUCCESS,
}

export const Danger = Template.bind({})

Danger.args = {
  default: 'A simple danger alert—check it out!',
  variant: FbUiVariantTypes.DANGER,
}

export const Warning = Template.bind({})

Warning.args = {
  default: 'A simple warning alert—check it out!',
  variant: FbUiVariantTypes.WARNING,
}

export const Info = Template.bind({})

Info.args = {
  default: 'A simple info alert—check it out!',
  variant: FbUiVariantTypes.INFO,
}

export const WithIcon = Template.bind({})

WithIcon.args = {
  default: 'An example alert with an icon',
  icon: `<font-awesome-icon icon="info-circle" size="lg" />`,
  variant: FbUiVariantTypes.PRIMARY,
}

export const WithLink: Story<TemplateArgs> = () => {
  return {
    components: { FbUiAlert },
    template: `
      <div>
        <div style="padding: 0 0 5px 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.DEFAULT}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
        <div style="padding: 5px 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.PRIMARY}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
        <div style="padding: 5px 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.SUCCESS}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
        <div style="padding: 5px 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.DANGER}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
        <div style="padding: 5px 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.WARNING}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
        <div style="padding: 5px 0 0 0;">
          <fb-ui-alert variant="${FbUiVariantTypes.INFO}">
            <p style="font-size: 14px; padding: 0; margin: 0; line-height: 20px;">An example alert with
              <a href="https://www.fastybird.com" target="_blank">link in</a> content</p>
          </fb-ui-alert>
        </div>
      </div>
    `,
  }
}
