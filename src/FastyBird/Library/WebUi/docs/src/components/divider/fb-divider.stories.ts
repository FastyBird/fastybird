import { Meta, StoryObj } from '@storybook/vue3';
import { FasStar } from '@fastybird/web-ui-icons';
import {
	FbDivider,
	FbIcon,
	DividerDirectionTypes,
	dividerDirections,
	DividerContentPositionTypes,
	dividerContentPositions,
} from '@fastybird/web-ui-components';

import './fb-divider.stories.scss';

const meta: Meta<typeof FbDivider> = {
	component: FbDivider,
	title: 'Components/Layout/Divider',
	argTypes: {
		direction: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: dividerDirections,
			description: "set divider's direction",
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: DividerDirectionTypes.HORIZONTAL },
			},
		},
		borderStyle: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'set the style of divider',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: 'solid' },
			},
		},
		contentPosition: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: dividerContentPositions,
			description: 'the position of the customized content on the divider line',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: DividerContentPositionTypes.CENTER },
			},
		},
		gradient: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'determine whether show divider with gradient effect',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
	},
	args: {
		direction: DividerDirectionTypes.HORIZONTAL,
		borderStyle: 'solid',
		contentPosition: DividerContentPositionTypes.CENTER,
		gradient: false,
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbDivider>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<span>
		I sit at my window this morning where the world like a passer-by stops for a moment, nods to me and goes.
	</span>
	<fb-divider />
	<span>
		There little thoughts are the rustle of leaves; they have their whisper of joy in my mind.
	</span>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbDivider },
		template: `
<span>
	I sit at my window this morning where the world like a passer-by stops for a moment, nods to me and goes.
</span>
<fb-divider />
<span>
	There little thoughts are the rustle of leaves; they have their whisper of joy in my mind.
</span>`,
	}),
};

export const CustomContent: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<span>What you are you do not see, what you see is your shadow. </span>
	<fb-divider content-position="${DividerContentPositionTypes.LEFT}">Rabindranath Tagore</fb-divider>
	<span>
		My wishes are fools, they shout across thy song, my Master. Let me but listen.
	</span>
	<fb-divider>
		<fb-icon><fas-star /></fb-icon>
	</fb-divider>
	<span>I cannot choose the best. The best chooses me.</span>
	<fb-divider content-position="${DividerContentPositionTypes.RIGHT}">Rabindranath Tagore</fb-divider>
</template>

<script lang="ts" setup>
import { FasStar } from '@fastybird/web-ui-icons';
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbDivider, FbIcon, FasStar },
		template: `
<span>What you are you do not see, what you see is your shadow. </span>
<fb-divider content-position="${DividerContentPositionTypes.LEFT}">Rabindranath Tagore</fb-divider>
<span>
	My wishes are fools, they shout across thy song, my Master. Let me but listen.
</span>
<fb-divider>
	<fb-icon><fas-star /></fb-icon>
</fb-divider>
<span>I cannot choose the best. The best chooses me.</span>
<fb-divider content-position="${DividerContentPositionTypes.RIGHT}">Rabindranath Tagore</fb-divider>`,
	}),
};

export const DashedLine: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div>
		<span>What language is thine, O sea?</span>
		<fb-divider border-style="dashed" />
		<span>The language of eternal question.</span>
	</div>
	<fb-divider border-style="dotted" />
	<span>What language is thy answer, O sky?</span>
	<fb-divider border-style="double" />
	<span>The language of eternal silence.</span>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbDivider },
		template: `
<div>
	<span>What language is thine, O sea?</span>
	<fb-divider border-style="dashed" />
	<span>The language of eternal question.</span>
</div>
<fb-divider border-style="dotted" />
<span>What language is thy answer, O sky?</span>
<fb-divider border-style="double" />
<span>The language of eternal silence.</span>`,
	}),
};

export const VerticalDivider: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div class="row">
		<span>Rain</span>
		<fb-divider direction="${DividerDirectionTypes.VERTICAL}" />
		<span>Home</span>
		<fb-divider direction="${DividerDirectionTypes.VERTICAL}" border-style="dashed" />
		<span>Grass</span>
	</div>
</template>

<style scoped>
.row {
	display: flex;
	flex-direction: row;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbDivider },
		template: `
<div class="fb-divider-story-block--vertical">
	<span>Rain</span>
	<fb-divider direction="${DividerDirectionTypes.VERTICAL}" />
	<span>Home</span>
	<fb-divider direction="${DividerDirectionTypes.VERTICAL}" border-style="dashed" />
	<span>Grass</span>
</div>`,
	}),
};
