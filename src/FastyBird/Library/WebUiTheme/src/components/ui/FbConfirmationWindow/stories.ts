import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'
import { action } from '@storybook/addon-actions'
import { ref } from 'vue'

import {
  FbSizeTypes,
  FbUiConfirmationWindowPrimaryButtonTypes,
  FbUiVariantTypes,
} from '../../../types'
import FbUiButton from '../FbButton/index.vue'

import { IFbUiConfirmationWindowProps } from './types'
import FbUiConfirmationWindow from './index.vue'

export default {
  component: FbUiConfirmationWindow,
  title: 'Components/Ui/FB Confirmation window',
  argTypes: {
    icon: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Confirmation icon slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    title: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Confirmation title slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
    default: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Confirmation content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
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
      description: 'Button size',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbSizeTypes.MEDIUM },
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
      description: 'Confirmation color variant',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbUiVariantTypes.DANGER },
      },
    },
    primaryButton: {
      type: { name: 'string', required: false },
      control: { type: 'select' },
      defaultValue: FbUiConfirmationWindowPrimaryButtonTypes.NO,
      options: [
        FbUiConfirmationWindowPrimaryButtonTypes.NO,
        FbUiConfirmationWindowPrimaryButtonTypes.YES,
      ],
      description: 'Confirmation primary button type',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: FbUiConfirmationWindowPrimaryButtonTypes.NO },
      },
    },
    yesBtnLabel: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: 'Yes',
    },
    showYes: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: true,
    },
    noBtnLabel: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      defaultValue: 'No',
    },
    showNo: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: true,
    },
    enableClosing: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: true,
    },
    transparentBg: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: false,
    },
    show: {
      type: { name: 'boolean', required: false },
      control: { type: 'boolean' },
      defaultValue: false,
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta

interface TemplateArgs extends IFbUiConfirmationWindowProps, Args {
  icon?: string
  title?: string
  default?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiConfirmationWindow, FbUiButton },
    setup(): any {
      const show = ref<boolean>(false)

      return { args, show }
    },
    template: `
      <div>
        <fb-ui-button @click.prevent="() => { show = true }" variant="${args.variant}">Open confirmation window</fb-ui-button>

        <fb-ui-confirmation-window
          :show="show"
          :size="args.size"
          :variant="args.variant"
          :primary-button="args.primaryButton"
          :yes-btn-label="args.yesBtnLabel"
          :show-yes="args.showYes"
          :no-btn-label="args.noBtnLabel"
          :show-no="args.showNo"
          :transparent-bg="args.transparentBg"
          :enable-closing="args.enableClosing"
          @close="(e) => { show = false; onClosed(e) }"
          @confirm="(e) => { show = false; onConfirmed(e) }"
        >
          <template v-if="${args.icon !== null && typeof args.icon !== 'undefined'}" #icon>${args.icon}</template>
          <template v-if="${args.title !== null && typeof args.title !== 'undefined'}" #title>${args.title}</template>
          <template v-if="${args.default !== null && typeof args.default !== 'undefined'}" #default>${args.default}</template>
        </fb-ui-confirmation-window>
      </div>
    `,
    methods: {
      onClick: action('button-clicked'),
      onClosed: action('close-clicked'),
      onConfirmed: action('confirmed-clicked'),
    },
  }
}

export const Default = Template.bind({})

Default.args = {
  variant: FbUiVariantTypes.DEFAULT,
  title: 'Delete articles',
  default: 'Are you sure to delete selected items. This action can\'t be undone.',
  icon: `<font-awesome-icon icon="trash" size="6x" />`,
}

export const Primary = Template.bind({})

Primary.args = {
  variant: FbUiVariantTypes.PRIMARY,
  title: 'Invite user',
  default: 'Are you sure to invite new user into application.',
  icon: `<font-awesome-icon icon="user" size="6x" />`,
  yesBtnLabel: 'Invite',
  noBtnLabel: 'Later',
  primaryButton: FbUiConfirmationWindowPrimaryButtonTypes.YES,
}

export const Success = Template.bind({})

Success.args = {
  variant: FbUiVariantTypes.SUCCESS,
  title: 'Article created',
  default: 'New article was created. Would you like to navigate to detail?',
  icon: `<font-awesome-icon icon="newspaper" size="6x" />`,
  primaryButton: FbUiConfirmationWindowPrimaryButtonTypes.YES,
  yesBtnLabel: 'Yes, take me there',
  noBtnLabel: 'No',
}

export const Danger = Template.bind({})

Danger.args = {
  variant: FbUiVariantTypes.DANGER,
  title: 'Delete articles',
  default: 'Are you sure to delete selected items. This action can\'t be undone.',
  icon: `<font-awesome-icon icon="trash" size="6x" />`,
}

export const Warning = Template.bind({})

Warning.args = {
  variant: FbUiVariantTypes.WARNING,
  title: 'Refresh page',
  default: 'This page has to be refreshed.',
  icon: `<font-awesome-icon icon="paper-plane" size="6x" />`,
  showNo: false,
}

export const Info = Template.bind({})

Info.args = {
  variant: FbUiVariantTypes.INFO,
  title: 'Update was received',
  default: 'Your data are now fresh and updated from server.',
  icon: `<font-awesome-icon icon="info-circle" size="6x" />`,
  showNo: false,
  yesBtnLabel: 'Ok',
  primaryButton: FbUiConfirmationWindowPrimaryButtonTypes.YES,
  enableClosing: false,
}
