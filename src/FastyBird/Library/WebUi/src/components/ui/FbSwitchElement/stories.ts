import {
    Args,
    Meta,
    Story,
} from '@storybook/vue3'
import {action} from '@storybook/addon-actions'
import {ref} from 'vue'

import {
    FbSizeTypes,
    FbUiVariantTypes,
} from '../../../types'

import {IFbUiSwitchElementProps} from './types'
import FbUiSwitchElement from './index.vue'

export default {
    component: FbUiSwitchElement,
    title: 'Components/Ui/FB Switch element',
    argTypes: {
        size: {
            type: {name: 'string', required: false},
            control: {type: 'select'},
            defaultValue: FbSizeTypes.MEDIUM,
            options: [
                FbSizeTypes.SMALL,
                FbSizeTypes.MEDIUM,
                FbSizeTypes.LARGE,
            ],
            description: 'Switch size',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: FbSizeTypes.MEDIUM},
            },
        },
        variant: {
            type: {name: 'string', required: false},
            control: {type: 'select'},
            defaultValue: FbUiVariantTypes.PRIMARY,
            options: [
                FbUiVariantTypes.DEFAULT,
                FbUiVariantTypes.PRIMARY,
                FbUiVariantTypes.SUCCESS,
                FbUiVariantTypes.DANGER,
                FbUiVariantTypes.WARNING,
                FbUiVariantTypes.INFO,
            ],
            description: 'Switch color variant',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: FbUiVariantTypes.DEFAULT},
            },
        },
        status: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: false,
        },
        disabled: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: false,
        },
    },
    parameters: {
        controls: {disabled: true},
        actions: {disabled: true},
    },
} as Meta

interface TemplateArgs extends IFbUiSwitchElementProps, Args {
}

const Template: Story<TemplateArgs> = (args) => {
    return {
        components: {FbUiSwitchElement},
        setup(): any {
            const status = ref<boolean>(args?.status ?? false)

            return {args, status}
        },
        template: `
          <fb-ui-switch-element
              :size="args.size"
              :variant="args.variant"
              :status="status"
              :disabled="args.disabled"
              @change="onChange"
          />
        `,
        methods: {
            onChange: action('switch-changed'),
        },
    }
}

export const Default = Template.bind({})

Default.args = {
    variant: FbUiVariantTypes.DEFAULT,
}

export const Primary = Template.bind({})

Primary.args = {
    variant: FbUiVariantTypes.PRIMARY,
    status: true,
}

export const Success = Template.bind({})

Success.args = {
    variant: FbUiVariantTypes.SUCCESS,
    status: true,
}

export const Danger = Template.bind({})

Danger.args = {
    variant: FbUiVariantTypes.DANGER,
    status: true,
}

export const Warning = Template.bind({})

Warning.args = {
    variant: FbUiVariantTypes.WARNING,
    status: true,
}

export const Info = Template.bind({})

Info.args = {
    variant: FbUiVariantTypes.INFO,
    status: true,
}

export const Sizes: Story<TemplateArgs> = () => {
    return {
        components: {FbUiSwitchElement},
        template: `
          <div style="display: flex;">
          <div style="padding: 5px 15px 5px 0;">
            <fb-ui-switch-element size="${FbSizeTypes.LARGE}" :status="true" @change="onChange"/>
          </div>
          <div style="padding: 5px 15px 5px 0;">
            <fb-ui-switch-element size="${FbSizeTypes.MEDIUM}" :status="true" @change="onChange"/>
          </div>
          <div style="padding: 5px 15px 5px 0;">
            <fb-ui-switch-element size="${FbSizeTypes.SMALL}" :status="true" @change="onChange"/>
          </div>
          </div>
        `,
        methods: {
            onChange: action('switch-changed'),
        },
    }
}

Sizes.parameters = {
    controls: {disabled: true},
}
