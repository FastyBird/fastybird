import { Meta, StoryObj } from '@storybook/vue3';
import { FbCard, FbButton, FbDivider, FbRow, FbCol, CardShadowTypes } from '@fastybird/web-ui-components';
import { FasGear, FasEllipsis, FasPencil } from '@fastybird/web-ui-icons';
import { VariantTypes, EffectTypes } from '@fastybird/web-ui-constants';

// @ts-ignore
import imageFile from './../../../static/example_01.jpg';

import './fb-card.stories.scss';

const meta: Meta<typeof FbCard> = {
	component: FbCard,
	title: 'Components/Data/Card',
	argTypes: {
		header: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the card header',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		default: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'customize default content',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		footer: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the card footer',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		extra: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the extra part of the card header',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		cover: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content of the card cover used for images',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		variant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['', VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
			description: 'card variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		effect: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['', EffectTypes.LIGHT, EffectTypes.DARK],
			description: 'card theme style',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: EffectTypes.LIGHT },
			},
		},
		headerVariant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['', VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
			description: 'card header variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		bodyVariant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['', VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
			description: 'card body variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		footerVariant: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['', VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
			description: 'card footer variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		shadow: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: [CardShadowTypes.ALWAYS, CardShadowTypes.HOVER, CardShadowTypes.NEVER],
			description: 'when to show card shadows',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: CardShadowTypes.ALWAYS },
			},
		},
		bordered: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'when to show card border',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: true },
			},
		},
		loading: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'when to show card loading animation',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: false },
			},
		},
	},
	args: {
		effect: EffectTypes.LIGHT,
		shadow: CardShadowTypes.ALWAYS,
		bordered: true,
		loading: false,
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbCard>;

export const Component: Story = {
	tags: ['hideInSidebar'],
	args: {
		header: 'Card name',
		default: 'Card body content',
		footer: 'Footer content',
	},
};

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-card class="card">
		<template #header>
			<span>Card name</span>
		</template>

		<template #extra>
			<a href="#">More</a>
		</template>

		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>

		<template #footer>Footer content</template>
	</fb-card>
</template>

<style scoped>
.card {
	max-width: 480px;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard },
		template: `
<div class="fb-card-story-block">
	<fb-card class="fb-card-story-block__item">
		<template #header>
			<span>Card name</span>
		</template>
		<template #extra>
			<a href="#">More</a>
		</template>
		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
		<template #footer>Footer content</template>
	</fb-card>
</div>`,
	}),
};

export const Simple: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-card class="card">
		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
	</fb-card>
</template>

<style scoped>
.card {
	max-width: 480px;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard },
		template: `
<div class="fb-card-story-block">
	<fb-card class="fb-card-story-block__item">
		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
	</fb-card>
</div>`,
	}),
};

export const NoBorder: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-card class="card" :bordered="false">
		<template #header>
			<span>Card name</span>
		</template>

		<template #extra>
			<a href="#">More</a>
		</template>

		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>

		<template #footer>Footer content</template>
	</fb-card>
</template>

<style scoped>
.card {
	max-width: 480px;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard },
		template: `
<div class="fb-card-story-block">
	<fb-card class="fb-card-story-block__item" :bordered="false" shadow="${CardShadowTypes.NEVER}">
		<template #header>
			<span>Card name</span>
		</template>
		<template #extra>
			<a href="#">More</a>
		</template>
		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
		<template #footer>Footer content</template>
	</fb-card>
</div>`,
	}),
};

export const Shadow: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-card class="card" shadow="${CardShadowTypes.ALWAYS}">Always</fb-card>
	<fb-card class="card" shadow="${CardShadowTypes.HOVER}">Hover</fb-card>
	<fb-card class="card" shadow="${CardShadowTypes.NEVER}">Never</fb-card>
</template>

<style scoped>
.card {
	max-width: 480px;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard },
		template: `
<div class="fb-card-story-block">
	<fb-card class="fb-card-story-block__item" shadow="${CardShadowTypes.ALWAYS}">Always</fb-card>
	<fb-card class="fb-card-story-block__item" shadow="${CardShadowTypes.HOVER}">Hover</fb-card>
	<fb-card class="fb-card-story-block__item" shadow="${CardShadowTypes.NEVER}">Never</fb-card>
</div>`,
	}),
};

export const Variants: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="default">
				<template #header>
					<span>Default Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.INFO}">
				<template #header>
					<span>Info Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.SUCCESS}">
				<template #header>
					<span>Success Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.WARNING}">
				<template #header>
					<span>Warning Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.DANGER}">
				<template #header>
					<span>Danger Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</template>

<style scoped>
.card {
	max-width: 480px;
	margin: 10px 0;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard, FbRow, FbCol },
		template: `
<div class="fb-card-story-block">
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="default">
				<template #header>
					<span>Default Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.INFO}">
				<template #header>
					<span>Info Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.SUCCESS}">
				<template #header>
					<span>Success Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.WARNING}">
				<template #header>
					<span>Warning Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.DANGER}">
				<template #header>
					<span>Danger Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</div>`,
	}),
};

export const VariantsDark: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="default" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Default Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.INFO}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Info Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.SUCCESS}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Success Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.WARNING}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Warning Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" variant="${VariantTypes.DANGER}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Danger Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</template>

<style scoped>
.card {
	max-width: 480px;
	margin: 10px 0;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard, FbRow, FbCol },
		template: `
<div class="fb-card-story-block">
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="default" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Default Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.INFO}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Info Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.SUCCESS}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Success Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.WARNING}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Warning Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" variant="${VariantTypes.DANGER}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Danger Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</div>`,
	}),
};

export const VariantsParts: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="card" header-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Header Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" body-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Body Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" footer-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Footer Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	
		<fb-col :sm="12" :md="8">
			<fb-card class="card" header-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Header Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" body-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Body Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="card" footer-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Footer Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</template>

<style scoped>
.card {
	max-width: 480px;
	margin: 10px 0;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard, FbRow, FbCol },
		template: `
<div class="fb-card-story-block">
	<fb-row :gutter="20">
		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" header-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Header Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" body-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Body Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" footer-variant="${VariantTypes.PRIMARY}">
				<template #header>
					<span>Primary Footer Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" header-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Header Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" body-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Body Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>

		<fb-col :sm="12" :md="8">
			<fb-card class="fb-card-story-block__in-column-item" footer-variant="${VariantTypes.PRIMARY}" effect="${EffectTypes.DARK}">
				<template #header>
					<span>Primary Footer Variant</span>
				</template>
				<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
				<template #footer>Footer content</template>
			</fb-card>
		</fb-col>
	</fb-row>
</div>`,
	}),
};

export const MoreContent: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-card class="card">
		<template #cover>
			<img
				alt="example"
				:src="imageFile"
			/>
		</template>

		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>

		<template #footer>
			<div class="actions">
				<fb-button :icon="FasGear" link class="action" />
				<fb-divider direction="vertical" />
				<fb-button :icon="FasPencil" link class="action" />
				<fb-divider direction="vertical" />
				<fb-button :icon="FasEllipsis" link class="action" />
			</div>
		</template>
	</fb-card>
</template>

<style scoped>
.card {
	max-width: 300px;
}
.actions {
	display: flex;
}
.action {
	flex: 1;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbCard, FbButton, FbDivider },
		setup() {
			return {
				FasGear,
				FasEllipsis,
				FasPencil,
				imageFile,
			};
		},
		template: `
<div class="fb-card-story-block">
	<fb-card class="fb-card-story-block__item">
		<template #cover>
			<img
				alt="example"
				:src="imageFile"
			/>
		</template>
		<p v-for="o in 4" :key="o">{{ 'List item ' + o }}</p>
		<template #footer>
			<div class="fb-card-story-block__item-actions">
				<fb-button :icon="FasGear" link class="fb-card-story-block__item-actions-button" />
				<fb-divider direction="vertical" />
				<fb-button :icon="FasPencil" link class="fb-card-story-block__item-actions-button" />
				<fb-divider direction="vertical" />
				<fb-button :icon="FasEllipsis" link class="fb-card-story-block__item-actions-button" />
			</div>
		</template>
	</fb-card>
</div>`,
	}),
};
