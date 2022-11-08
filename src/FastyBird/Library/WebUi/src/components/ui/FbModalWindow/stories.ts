import {
    Args,
    Meta,
    Story,
} from '@storybook/vue3'
import {action} from '@storybook/addon-actions'
import {ref} from 'vue'

import {
    FbSizeTypes,
    FbUiModalLayoutTypes,
} from '../../../types'
import FbUiButton from '../FbButton/index.vue'

import {IFbUiModalWindowProps} from './types'
import FbUiModalWindow from './index.vue'

export default {
    component: FbUiModalWindow,
    title: 'Components/Ui/FB Modal window',
    argTypes: {
        content: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Full modal content slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        header: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Full modal header slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        title: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal title slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        subtitle: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal sub-title slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        icon: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal heading icon slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        body: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal body slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        footer: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal footer slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: 'Close'},
            },
        },
        'left-button': {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal custom left button slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: '-'},
            },
        },
        'right-button': {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            description: 'Modal custom right button slot',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: 'Ok'},
            },
        },
        size: {
            type: {name: 'string', required: false},
            control: {type: 'select'},
            defaultValue: FbSizeTypes.MEDIUM,
            options: [
                FbSizeTypes.SMALL,
                FbSizeTypes.MEDIUM,
                FbSizeTypes.LARGE,
            ],
            description: 'Button size',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: FbSizeTypes.MEDIUM},
            },
        },
        layout: {
            type: {name: 'string', required: false},
            control: {type: 'select'},
            defaultValue: FbUiModalLayoutTypes.DEFAULT,
            options: [
                FbUiModalLayoutTypes.DEFAULT,
                FbUiModalLayoutTypes.PHONE,
                FbUiModalLayoutTypes.TABLET,
            ],
            description: 'Modal window layout',
            table: {
                type: {summary: 'string'},
                defaultValue: {summary: FbUiModalLayoutTypes.DEFAULT},
            },
        },
        width: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            defaultValue: null,
        },
        showHeader: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: true,
        },
        showFooter: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: true,
        },
        leftBtnLabel: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            defaultValue: 'Close',
        },
        showLeftBtn: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: true,
        },
        rightBtnLabel: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            defaultValue: 'Ok',
        },
        showRightBtn: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: true,
        },
        closeBtnLabel: {
            type: {name: 'string', required: false},
            control: {type: 'text'},
            defaultValue: 'Ok',
        },
        enableClosing: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: true,
        },
        transparentBg: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: false,
        },
        loader: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: false,
        },
        show: {
            type: {name: 'boolean', required: false},
            control: {type: 'boolean'},
            defaultValue: false,
        },
    },
    parameters: {
        controls: {disabled: true},
    },
} as Meta

interface TemplateArgs extends IFbUiModalWindowProps, Args {
    content?: string
    header?: string
    title?: string
    subtitle?: string
    icon?: string
    body?: string
    footer?: string
    'left-button'?: string
    'right-button'?: string
}

const Template: Story<TemplateArgs> = (args) => {
    return {
        components: {FbUiModalWindow, FbUiButton},
        setup(): any {
            const show = ref<boolean>(false)

            return {args, show}
        },
        template: `
          <div>
          <fb-ui-button @click.prevent="() => { show = true }">Open modal</fb-ui-button>

          <fb-ui-modal-window
              :size="args.size"
              :layout="args.layout"
              :width="args.width"
              :showHeader="args.showHeader"
              :showFooter="args.showFooter"
              :close-btn-label="args.closeBtnLabel"
              :enable-closing="args.enableClosing"
              :right-btn-label="args.rightBtnLabel"
              :show-right-btn="args.showRightBtn"
              :left-btn-label="args.leftBtnLabel"
              :show-left-btn="args.showLeftBtn"
              :transparent-bg="args.transparentBg"
              :loader="args.loader"
              :show="show"
              @close="(e) => { show = false; onClose(e) }"
              @leftSubmit="(e) => { show = false; onLeftSubmit(e) }"
              @rightSubmit="(e) => { show = false; onRightSubmit(e) }"
          >
            <template v-if="${args.content !== null && typeof args.content !== 'undefined'}" #content>${args.content}
            </template>
            <template v-if="${args.title !== null && typeof args.title !== 'undefined'}" #title>${args.title}</template>
            <template v-if="${args.subtitle !== null && typeof args.subtitle !== 'undefined'}" #subtitle>
              ${args.subtitle}
            </template>
            <template v-if="${args.icon !== null && typeof args.icon !== 'undefined'}" #icon>${args.icon}</template>
            <template v-if="${args.body !== null && typeof args.body !== 'undefined'}" #body>${args.body}</template>
            <template v-if="${args.footer !== null && typeof args.footer !== 'undefined'}" #footer>${args.footer}
            </template>
            <template v-if="${args['left-button'] !== null && typeof args['left-button'] !== 'undefined'}" #left-button>
              ${args['left-button']}
            </template>
            <template v-if="${args['right-button'] !== null && typeof args['right-button'] !== 'undefined'}"
                      #right-button>${args['right-button']}
            </template>
          </fb-ui-modal-window>
          </div>
        `,
        methods: {
            onClick: action('button-clicked'),
            onClose: action('modal-close-action-triggered'),
            onLeftSubmit: action('modal-left-button-clicked'),
            onRightSubmit: action('modal-right-button-clicked'),
        },
    }
}

export const Default = Template.bind({})

Default.args = {
    title: 'Modal window header',
    body: `
    <div>
      <p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
      <p>Donec ultrices vel nibh a iaculis. Morbi dapibus sollicitudin libero facilisis dapibus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
      <p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
    </div>
  `,
}

export const WithSubtitleAndIcon = Template.bind({})

WithSubtitleAndIcon.args = {
    title: 'Modal window header',
    subtitle: 'With some fancy subheading',
    icon: `<font-awesome-icon icon="user-secret" />`,
    body: `
    <div>
      <p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
      <p>Donec ultrices vel nibh a iaculis. Morbi dapibus sollicitudin libero facilisis dapibus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
      <p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
    </div>
  `,
}
