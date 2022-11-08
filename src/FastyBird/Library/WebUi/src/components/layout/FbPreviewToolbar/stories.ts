import { Args, Meta, Story } from '@storybook/vue3';

import { FbUiButtonVariantTypes, FbSizeTypes } from '../../../types';
import FbUiButton from '../../ui/FbButton/index.vue';

import FbLayoutPreviewToolbar from './index.vue';

export default {
	component: FbLayoutPreviewToolbar,
	title: 'Components/Layout/FB Preview toolbar',
	argTypes: {
		left: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Toolbar left content slot',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		right: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Toolbar right content slot',
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

interface TemplateArgs extends Args {
	left?: string;
	right?: string;
}

const Template: Story<TemplateArgs> = (args) => {
	return {
		components: {
			FbLayoutPreviewToolbar,
			FbUiButton,
		},
		setup(): any {
			return { args };
		},
		template: `
      <fb-layout-preview-toolbar :copyright="args.copyright" :author="args.author" :website="args.website">
        <template v-if="${args.left !== null && typeof args.left !== 'undefined'}" #left>${args.left}</template>
        <template v-if="${args.right !== null && typeof args.right !== 'undefined'}" #right>${args.right}</template>
      </fb-layout-preview-toolbar>
    `,
	};
};

export const Default = Template.bind({});

Default.args = {
	left: `
    <fb-ui-button
      variant="${FbUiButtonVariantTypes.LINK_DEFAULT}"
      size="${FbSizeTypes.EXTRA_SMALL}"
    >
      <font-awesome-icon icon="times" />
      Close
    </fb-ui-button>

    <fb-ui-button
      variant="${FbUiButtonVariantTypes.LINK_DEFAULT}"
      size="${FbSizeTypes.EXTRA_SMALL}"
    >
      <font-awesome-icon icon="pencil-alt" />
      Edit
    </fb-ui-button>
  `,
	right: `
    <div style="display: inline-block; padding: 0 1rem;">
      <span style="font-weight: 800;">
        1
      </span>
      of
      <span style="font-weight: 800;">
        10
      </span>
    </div>

    <fb-ui-button
      variant="${FbUiButtonVariantTypes.LINK_DEFAULT}"
      size="${FbSizeTypes.EXTRA_SMALL}"
    >
      <font-awesome-icon icon="angle-left" />
    </fb-ui-button>

    <fb-ui-button
      variant="${FbUiButtonVariantTypes.LINK_DEFAULT}"
      size="${FbSizeTypes.EXTRA_SMALL}"
    >
      <font-awesome-icon icon="angle-right" />
    </fb-ui-button>
  `,
};
