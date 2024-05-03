import { Meta, StoryObj } from '@storybook/vue3';
import { action } from '@storybook/addon-actions';
import { fn } from '@storybook/test';
import { FbAlert } from '@fastybird/web-ui-components';
import { VariantTypes, EffectTypes } from '@fastybird/web-ui-constants';

import './fb-alert.stories.scss';

const meta: Meta<typeof FbAlert> = {
	component: FbAlert,
	title: 'Components/Feedback/Alert',
	argTypes: {
		title: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the alert title',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		description: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the alert description',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		icon: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'slot for inserting custom icon. this slot will override default component icon',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		default: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'slot for inserting custom content. This slot will override `title` and `description` slots',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		variant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: [VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
			description: 'alert variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: VariantTypes.DEFAULT },
			},
		},
		effect: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: [EffectTypes.LIGHT, EffectTypes.DARK],
			description: 'alert theme style',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: EffectTypes.LIGHT },
			},
		},
		center: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'whether content is placed in the center',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
		closable: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'whether alert can be dismissed',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
		closeText: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'customized close button text',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: undefined },
			},
		},
		onClose: {
			table: {
				disable: true,
			},
		},
	},
	args: {
		description:
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus nunc massa, sollicitudin eget ex eget, pellentesque ultrices felis.',
		variant: VariantTypes.DEFAULT,
		effect: EffectTypes.LIGHT,
		center: false,
		closable: false,
		onClose: fn(),
	},
	excludeStories: /.*Data$/,
};

export const actionsData = {
	onClose: action('close'),
};

export default meta;

type Story = StoryObj<typeof FbAlert>;

export const Component: Story = {
	tags: ['hideInSidebar'],
	args: {
		description: 'A simple default alert — check it out!',
	},
};

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert>
		<template #description>A simple default alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.INFO}">
		<template #description>A simple info alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}">
		<template #description>A simple success alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}">
		<template #description>A simple warning alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.ERROR}">
		<template #description>A simple error alert — check it out!</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert>
			<template #description>A simple default alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.INFO}">
			<template #description>A simple info alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}">
			<template #description>A simple success alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}">
			<template #description>A simple warning alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.ERROR}">
			<template #description>A simple error alert — check it out!</template>
		</fb-alert>
	</div>
</div>`,
	}),
};

export const Dark: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert effect="${EffectTypes.DARK}">
		<template #description>A simple default alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.INFO}" effect="${EffectTypes.DARK}">
		<template #description>A simple info alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}" effect="${EffectTypes.DARK}">
		<template #description>A simple success alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}" effect="${EffectTypes.DARK}">
		<template #description>A simple warning alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.ERROR}" effect="${EffectTypes.DARK}">
		<template #description>A simple error alert — check it out!</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert effect="${EffectTypes.DARK}">
			<template #description>A simple default alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.INFO}" effect="${EffectTypes.DARK}">
			<template #description>A simple info alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}" effect="${EffectTypes.DARK}">
			<template #description>A simple success alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}" effect="${EffectTypes.DARK}">
			<template #description>A simple warning alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.ERROR}" effect="${EffectTypes.DARK}">
			<template #description>A simple error alert — check it out!</template>
		</fb-alert>
	</div>
</div>`,
	}),
};

export const CloseButton: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert :closable="false">
		<template #description>Unclosable alert</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}" close-text="Gotcha">
		<template #description>Customized close text</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}" @close="hello">
		<template #description>Alert with callback</template>
	</fb-alert>
</template>

<script lang="ts" setup>
const hello = (): void => {
	alert("Hi! I'm on close callback");
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		methods: {
			hello: () => {
				alert("Hi! I'm on close callback");
			},
		},
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert :closable="false">
			<template #description>Unclosable alert</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}" close-text="Gotcha">
			<template #description>Customized close text</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}" @close="hello">
			<template #description>Alert with callback</template>
		</fb-alert>
	</div>
</div>`,
	}),
};

export const Icon: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert variant="${VariantTypes.INFO}" icon>
		<template #description>A simple info alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}" icon>
		<template #description>A simple success alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}" icon>
		<template #description>A simple warning alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.ERROR}" icon>
		<template #description>A simple error alert — check it out!</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.INFO}">
			<template #description>A simple info alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}" icon>
			<template #description>A simple success alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}" icon>
			<template #description>A simple warning alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.ERROR}" icon>
			<template #description>A simple error alert — check it out!</template>
		</fb-alert>
	</div>
</div>`,
	}),
};

export const Center: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert center>
		<template #description>A simple default alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.INFO}" center icon>
		<template #description>A simple info alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}" center icon>
		<template #description>A simple success alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}" center icon>
		<template #description>A simple warning alert — check it out!</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.ERROR}" center icon>
		<template #description>A simple error alert — check it out!</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert center icon>
			<template #description>A simple default alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.INFO}" center icon>
			<template #description>A simple info alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}" center icon>
			<template #description>A simple success alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}" center icon>
			<template #description>A simple warning alert — check it out!</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.ERROR}" center icon>
			<template #description>A simple error alert — check it out!</template>
		</fb-alert>
	</div>
</div>`,
	}),
};

export const Title: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert variant="${VariantTypes.INFO}">
		<template #title>With title</template>
		<template #description>This is a description.</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<fb-alert variant="${VariantTypes.INFO}">
		<template #title>With title</template>
		<template #description>This is a description.</template>
	</fb-alert>
</div>`,
	}),
};

export const TitleIcon: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-alert variant="${VariantTypes.INFO}" icon>
		<template #title>Info alert</template>
		<template #description>More text description</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.SUCCESS}" icon>
		<template #title>Success alert</template>
		<template #description>More text description</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.WARNING}" icon>
		<template #title>Warning alert</template>
		<template #description>More text description</template>
	</fb-alert>
	<fb-alert variant="${VariantTypes.ERROR}" icon>
		<template #title>Error alert</template>
		<template #description>More text description</template>
	</fb-alert>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbAlert },
		template: `
<div class="fb-alert-story-block">
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.INFO}" icon>
			<template #title>Info alert</template>
			<template #description>More text description</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.SUCCESS}" icon>
			<template #title>Success alert</template>
			<template #description>More text description</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.WARNING}" icon>
			<template #title>Warning alert</template>
			<template #description>More text description</template>
		</fb-alert>
	</div>
	<div class="fb-alert-story-block__item">
		<fb-alert variant="${VariantTypes.ERROR}" icon>
			<template #title>Error alert</template>
			<template #description>More text description</template>
		</fb-alert>
	</div>
</div>`,
	}),
};
