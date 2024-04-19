import { Meta, StoryObj } from '@storybook/vue3';
import { action } from '@storybook/addon-actions';
import { FasShare } from '@fastybird/web-ui-icons';
import { FbBadge, FbButton } from '@fastybird/web-ui-components';
import { VariantTypes } from '@fastybird/web-ui-constants';

import './fb-badge.stories.scss';

const meta: Meta<typeof FbBadge> = {
	component: FbBadge,
	title: 'Components/Data/Badge',
	argTypes: {
		default: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'customize default content',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		value: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'display value',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		max: {
			type: { name: 'number', required: false },
			control: { type: 'text' },
			description: 'maximum value, shows `{max}+` when exceeded. Only works if value is a number',
			table: {
				type: { summary: 'number' },
				defaultValue: { summary: '99' },
			},
		},
		isDot: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'whether a little dot is displayed',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
		hidden: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'whether a badge is hidden',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
		variant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: [VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
			description: 'badge variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: VariantTypes.DANGER },
			},
		},
		showZero: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'whether to show badge when value is zero',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
	},
	args: {
		value: '15',
		max: 99,
		isDot: false,
		hidden: false,
		variant: VariantTypes.DANGER,
		showZero: true,
		default: 'Here is some badge',
	},
	excludeStories: /.*Data$/,
};

export const actionsData = {
	onClose: action('close'),
};

export default meta;

type Story = StoryObj<typeof FbBadge>;

export const Component: Story = {
	tags: ['hideInSidebar'],
};

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-badge :value="12">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="3">
		<fb-button>replies</fb-button>
	</fb-badge>
	<fb-badge :value="1" warning="primary">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="2" warning="warning">
		<fb-button>replies</fb-button>
	</fb-badge>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbBadge, FbButton },
		template: `
<div class="fb-badge-story-block">
	<fb-badge :value="12" class="fb-badge-story-block__item">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="3" class="fb-badge-story-block__item">
		<fb-button>replies</fb-button>
	</fb-badge>
	<fb-badge :value="1" warning="primary" class="fb-badge-story-block__item">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="2" warning="warning" class="fb-badge-story-block__item">
		<fb-button>replies</fb-button>
	</fb-badge>
</div>`,
	}),
};

export const MaxValue: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-badge :value="200" :max="99">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="100" :max="10">
		<fb-button>replies</fb-button>
	</fb-badge>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbBadge, FbButton },
		template: `
<div class="fb-badge-story-block">
	<fb-badge :value="200" :max="99" class="fb-badge-story-block__item">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge :value="100" :max="10" class="fb-badge-story-block__item">
		<fb-button>replies</fb-button>
	</fb-badge>
</div>`,
	}),
};

export const Customizations: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-badge value="new">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge value="hot" variant="${VariantTypes.SUCCESS}">
		<fb-button>replies</fb-button>
	</fb-badge>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbBadge, FbButton },
		template: `
<div class="fb-badge-story-block">
	<fb-badge value="new" class="fb-badge-story-block__item">
		<fb-button>comments</fb-button>
	</fb-badge>
	<fb-badge value="hot" class="fb-badge-story-block__item" variant="${VariantTypes.SUCCESS}">
		<fb-button>replies</fb-button>
	</fb-badge>
</div>`,
	}),
};

export const RedDot: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-badge is-dot>query</fb-badge>
	<fb-badge is-dot>
		<fb-button :icon="FasShare" variant="${VariantTypes.PRIMARY}" />
	</fb-badge>
</template>

<script lang="ts" setup>
import { FasShare } from '@fastybird/web-ui-icons';
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbBadge, FbButton },
		setup() {
			return { FasShare };
		},
		template: `
<div class="fb-badge-story-block">
	<fb-badge is-dot class="fb-badge-story-block__item">query</fb-badge>
	<fb-badge is-dot class="fb-badge-story-block__item">
		<fb-button :icon="FasShare" variant="${VariantTypes.PRIMARY}" />
	</fb-badge>
</div>`,
	}),
};
