import { Meta, StoryObj } from '@storybook/vue3';
import { FbComponentLoading } from '@fastybird/web-ui-components';
import { ComponentSizeTypes } from '@fastybird/web-ui-constants';

const meta: Meta<typeof FbComponentLoading> = {
	component: FbComponentLoading,
	title: 'Components/Feedback/Component loading',
	argTypes: {
		size: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: [ComponentSizeTypes.LARGE, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.SMALL],
			description: 'loading status size',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: ComponentSizeTypes.DEFAULT },
			},
		},
	},
	args: {
		size: ComponentSizeTypes.DEFAULT,
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbComponentLoading>;

export const Component: Story = {};

export const Sizes: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-component-loading size="${ComponentSizeTypes.LARGE}" />
	<fb-component-loading />
	<fb-component-loading size="${ComponentSizeTypes.SMALL}" />
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbComponentLoading },
		template: `
<div style="max-width: 600px;">
	<div>
		<fb-component-loading size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading size="${ComponentSizeTypes.SMALL}" />
	</div>
</div>`,
	}),
};
