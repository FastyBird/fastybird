import { Args, Meta, Story } from '@storybook/vue3';
import { ref } from 'vue';

import { FbUiButtonVariantTypes } from '../../../types';
import FbUiButton from '../../ui/FbButton/index.vue';

import { IFbLayoutPhoneMenuProps } from './types';
import { FbLayoutPhoneMenu, FbLayoutPhoneMenuButton, FbLayoutPhoneMenuContent, FbLayoutPhoneMenuHeading } from './index';
import { action } from '@storybook/addon-actions';

export default {
	component: FbLayoutPhoneMenu,
	title: 'Components/Layout/FB Phone menu',
	argTypes: {
		default: {
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
} as Meta;

interface ITemplateArgs extends IFbLayoutPhoneMenuProps, Args {
	default?: string;
	header?: string;
	footer?: string;
}

const Template: Story<ITemplateArgs> = (args) => {
	return {
		components: {
			FbLayoutPhoneMenu,
			FbLayoutPhoneMenuButton,
			FbLayoutPhoneMenuContent,
			FbLayoutPhoneMenuHeading,

			FbUiButton,
		},
		setup(): any {
			const show = ref<boolean>(false);

			return { args, show };
		},
		template: `
      <div>
        <fb-ui-button @click="show = true">Open menu</fb-ui-button>

        <fb-layout-phone-menu
          :show="show"
          :showClose="args.showClose"
          :closeBtnText="args.closeBtnText"
          @close="show = false"
        >
          <template #heading>
            <fb-layout-phone-menu-heading :teleport="false">
              Select one from items
            </fb-layout-phone-menu-heading>
          </template>

          <template #default>
            <fb-layout-phone-menu-content :teleport="false">
              <fb-ui-button
                style="font-size: 1.8rem; padding: 1rem;"
                variant="${FbUiButtonVariantTypes.LINK}"
                block
                @click.prevent="onClick"
              >
                Option one
              </fb-ui-button>
            </fb-layout-phone-menu-content>

            <fb-layout-phone-menu-content :teleport="false">
              <fb-ui-button
                style="font-size: 1.8rem; padding: 1rem;"
                variant="${FbUiButtonVariantTypes.LINK}"
                block
                @click.prevent="onClick"
              >
                Option two
              </fb-ui-button>
            </fb-layout-phone-menu-content>

            <fb-layout-phone-menu-content :teleport="false">
              <fb-ui-button
                style="font-size: 1.8rem; padding: 1rem;"
                variant="${FbUiButtonVariantTypes.LINK}"
                block
                @click.prevent="onClick"
              >
                Option three
              </fb-ui-button>
            </fb-layout-phone-menu-content>
          </template>
        </fb-layout-phone-menu>
      </div>
    `,
		methods: {
			onClick: action('button-clicked'),
		},
	};
};

export const Default = Template.bind({});

Default.args = {};
