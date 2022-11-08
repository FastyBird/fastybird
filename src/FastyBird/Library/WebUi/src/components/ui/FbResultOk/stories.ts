import {
    Args,
    Meta,
    Story,
} from '@storybook/vue3'

import {FbSizeTypes} from '../../../types'

import {IFbUiResultOkProps} from './types'
import FbUiResultOk from './index.vue'

export default {
    component: FbUiResultOk,
    title: 'Components/Ui/FB Result OK',
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
            description: 'Icon size',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: FbSizeTypes.MEDIUM},
            },
        },
    },
    parameters: {
        controls: {disabled: true},
        actions: {disabled: true},
    },
} as Meta

interface TemplateArgs extends IFbUiResultOkProps, Args {
}

const Template: Story<TemplateArgs> = (args) => {
    return {
        components: {FbUiResultOk},
        setup(): any {
            return {args}
        },
        template: `
          <fb-ui-result-ok :size="args.size"/>
        `,
    }
}

export const Default = Template.bind({})
