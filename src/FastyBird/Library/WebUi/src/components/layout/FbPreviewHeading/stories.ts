import { Args, Meta, Story } from '@storybook/vue3';

import { FbUiButtonVariantTypes } from '../../../types';
import FbUiButton from '../../ui/FbButton/index.vue';

import FbLayoutPreviewHeading from './index.vue';

export default {
	component: FbLayoutPreviewHeading,
	title: 'Components/Layout/FB Preview heading',
	argTypes: {
		icon: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Heading icon slot',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		heading: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Heading heading slot',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		subheading: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Heading sub-heading slot',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		buttons: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'Heading buttons slot',
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
	icon?: string;
	heading?: string;
	subheading?: string;
	buttons?: string;
}

const Template: Story<TemplateArgs> = (args) => {
	return {
		components: {
			FbLayoutPreviewHeading,
			FbUiButton,
		},
		setup(): any {
			return { args };
		},
		template: `
          <fb-layout-preview-heading :copyright="args.copyright" :author="args.author" :website="args.website">
          <template v-if="${args.icon !== null && typeof args.icon !== 'undefined'}" #icon>${args.icon}</template>
          <template v-if="${args.heading !== null && typeof args.heading !== 'undefined'}" #heading>${args.heading}
          </template>
          <template v-if="${args.subheading !== null && typeof args.subheading !== 'undefined'}" #subheading>
            ${args.subheading}
          </template>
          <template v-if="${args.buttons !== null && typeof args.buttons !== 'undefined'}" #buttons>${args.buttons}
          </template>
          </fb-layout-preview-heading>
        `,
	};
};

export const Default = Template.bind({});

Default.args = {
	icon: `<font-awesome-icon icon="mug-hot" />`,
	heading: 'Lorem ipsum dolor sit amet',
	subheading: 'Suspendisse auctor tellus vehicula tincidunt malesuada',
	buttons: `
    <fb-ui-button variant="${FbUiButtonVariantTypes.OUTLINE_DEFAULT}" style="margin-right: 5px;">
      Option one
    </fb-ui-button>
    <fb-ui-button variant="${FbUiButtonVariantTypes.PRIMARY}">
      <font-awesome-icon icon="cog" />
    </fb-ui-button>
  `,
};
