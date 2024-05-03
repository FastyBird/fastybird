import { onMounted, onUnmounted, ref } from 'vue';

import { Meta, StoryObj } from '@storybook/vue3';
import { FbButton, FbTooltip } from '@fastybird/web-ui-components';

import './fb-tooltip.stories.scss';

import type { Ref } from 'vue';

const meta: Meta<typeof FbTooltip> = {
	component: FbTooltip,
	title: 'Components/Feedback/Tooltip',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbTooltip>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div>
		<fb-tooltip
			effect="dark"
			content="Top Left prompts info"
			placement="top-start"
		>
			<fb-button>top-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Top Center prompts info"
			placement="top"
		>
			<fb-button>top</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Top Right prompts info"
			placement="top-end"
		>
			<fb-button>top-end</fb-button>
		</fb-tooltip>
	</div>
	<div>
		<fb-tooltip
			effect="dark"
			content="Left Top prompts info"
			placement="left-start"
		>
			<fb-button>left-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Top prompts info"
			placement="right-start"
		>
			<fb-button>right-start</fb-button>
		</fb-tooltip>
	</div>
	<div>
		<fb-tooltip
			effect="dark"
			content="Left Center prompts info"
			placement="left"
		>
			<fb-button class="mt-3 mb-3">left</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Center prompts info"
			placement="right"
		>
			<fb-button>right</fb-button>
		</fb-tooltip>
	</div>
	<div>
		<fb-tooltip
			effect="dark"
			content="Left Bottom prompts info"
			placement="left-end"
		>
			<fb-button>left-end</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Bottom prompts info"
			placement="right-end"
		>
			<fb-button>right-end</fb-button>
		</fb-tooltip>
	</div>
	<div>
		<fb-tooltip
			effect="dark"
			content="Bottom Left prompts info"
			placement="bottom-start"
		>
			<fb-button>bottom-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Bottom Center prompts info"
			placement="bottom"
		>
			<fb-button>bottom</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Bottom Right prompts info"
			placement="bottom-end"
		>
			<fb-button>bottom-end</fb-button>
		</fb-tooltip>
	</div>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		template: `
<div class="fb-tooltip-story-block">
	<div class="fb-tooltip-story-block__layout-row-1">
		<fb-tooltip
			effect="dark"
			content="Top Left prompts info"
			placement="top-start"
		>
			<fb-button>top-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Top Center prompts info"
			placement="top"
		>
			<fb-button>top</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Top Right prompts info"
			placement="top-end"
		>
			<fb-button>top-end</fb-button>
		</fb-tooltip>
	</div>
	<div class="fb-tooltip-story-block__layout-row-2">
		<fb-tooltip
			effect="dark"
			content="Left Top prompts info"
			placement="left-start"
		>
			<fb-button>left-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Top prompts info"
			placement="right-start"
		>
			<fb-button>right-start</fb-button>
		</fb-tooltip>
	</div>
	<div class="fb-tooltip-story-block__layout-row-3">
		<fb-tooltip
			effect="dark"
			content="Left Center prompts info"
			placement="left"
		>
			<fb-button class="mt-3 mb-3">left</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Center prompts info"
			placement="right"
		>
			<fb-button>right</fb-button>
		</fb-tooltip>
	</div>
	<div class="fb-tooltip-story-block__layout-row-4">
		<fb-tooltip
			effect="dark"
			content="Left Bottom prompts info"
			placement="left-end"
		>
			<fb-button>left-end</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Right Bottom prompts info"
			placement="right-end"
		>
			<fb-button>right-end</fb-button>
		</fb-tooltip>
	</div>
	<div class="fb-tooltip-story-block__layout-row-5">
		<fb-tooltip
			effect="dark"
			content="Bottom Left prompts info"
			placement="bottom-start"
		>
			<fb-button>bottom-start</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Bottom Center prompts info"
			placement="bottom"
		>
			<fb-button>bottom</fb-button>
		</fb-tooltip>
		<fb-tooltip
			effect="dark"
			content="Bottom Right prompts info"
			placement="bottom-end"
		>
			<fb-button>bottom-end</fb-button>
		</fb-tooltip>
	</div>
</div>`,
	}),
};

export const Theme: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip content="Top center" placement="top">
		<fb-button>Dark</fb-button>
	</fb-tooltip>

	<fb-tooltip content="Bottom center" placement="bottom" effect="light">
		<fb-button>Light</fb-button>
	</fb-tooltip>

	<fb-tooltip content="Bottom center" effect="customized-tooltip">
		<fb-button>Customized theme</fb-button>
	</fb-tooltip>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		template: `
<fb-tooltip content="Top center" placement="top">
	<fb-button>Dark</fb-button>
</fb-tooltip>

<fb-tooltip content="Bottom center" placement="bottom" effect="light">
	<fb-button>Light</fb-button>
</fb-tooltip>

<fb-tooltip content="Bottom center" effect="customized-tooltip">
	<fb-button>Customized theme</fb-button>
</fb-tooltip>`,
	}),
};

export const MoreContent: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip placement="top">
		<template #content> multiple lines<br />second line </template>
		<fb-button>Top center</fb-button>
	</fb-tooltip>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		template: `
<fb-tooltip placement="top">
	<template #content> multiple lines<br />second line </template>
	<fb-button>Top center</fb-button>
</fb-tooltip>`,
	}),
};

export const Advanced: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip
		:disabled="disabled"
		content="click to close tooltip function"
		placement="bottom"
		effect="light"
	>
		<fb-button @click="disabled = !disabled">
			click to {{ disabled ? 'active' : 'close' }} tooltip function
		</fb-button>
	</fb-tooltip>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const disabled = ref(false);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		setup: (): {
			disabled: Ref<boolean>;
		} => {
			const disabled = ref(false);

			return {
				disabled,
			};
		},
		template: `
<fb-tooltip
	:disabled="disabled"
	content="click to close tooltip function"
	placement="bottom"
	effect="light"
>
	<fb-button @click="disabled = !disabled">
		click to {{ disabled ? 'active' : 'close' }} tooltip function
	</fb-button>
</fb-tooltip>`,
	}),
};

export const HtmlContent: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip
		content="<span>The content can be <strong>HTML</strong></span>"
		raw-content
	>
		<fb-button>hover me</fb-button>
	</fb-tooltip>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		template: `
<fb-tooltip
	content="<span>The content can be <strong>HTML</strong></span>"
	raw-content
>
	<fb-button>hover me</fb-button>
</fb-tooltip>`,
	}),
};

export const VirtualTrigger: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip
		v-model:visible="visible"
		content="Bottom center"
		placement="bottom"
		effect="light"
		trigger="click"
		virtual-triggering
		:virtual-ref="triggerRef"
	/>

	<fb-button @click="visible = !visible">Trigger me</fb-button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const visible = ref(false);

const triggerRef = ref({
	getBoundingClientRect() {
		return position.value;
	},
});

const position = ref({
	top: 0,
	left: 0,
	bottom: 0,
	right: 0,
});

const mousemoveHandler = (e) => {
	position.value = DOMRect.fromRect({
		width: 0,
		height: 0,
		x: e.clientX,
		y: e.clientY,
	});
};

onMounted(() => {
	document.addEventListener('mousemove', mousemoveHandler);
});

onUnmounted(() => {
	document.removeEventListener('mousemove', mousemoveHandler);
});
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		setup: (): {
			visible: Ref<boolean>;
			triggerRef: Ref<{ getBoundingClientRect: () => { top: number; left: number; bottom: number; right: number } }>;
			position: Ref<{
				top: number;
				left: number;
				bottom: number;
				right: number;
			}>;
		} => {
			const visible = ref(false);

			const triggerRef = ref({
				getBoundingClientRect() {
					return position.value;
				},
			});

			const position = ref({
				top: 0,
				left: 0,
				bottom: 0,
				right: 0,
			});

			const mousemoveHandler = (e): void => {
				position.value = DOMRect.fromRect({
					width: 0,
					height: 0,
					x: e.clientX,
					y: e.clientY,
				});
			};

			onMounted(() => {
				document.addEventListener('mousemove', mousemoveHandler);
			});

			onUnmounted(() => {
				document.removeEventListener('mousemove', mousemoveHandler);
			});

			return {
				visible,
				triggerRef,
				position,
			};
		},
		template: `
<fb-tooltip
	v-model:visible="visible"
	content="Bottom center"
	placement="bottom"
	effect="light"
	trigger="click"
	virtual-triggering
	:virtual-ref="triggerRef"
/>

<fb-button @click="visible = !visible">Trigger me</fb-button>`,
	}),
};

export const Singleton: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button
		v-for="i in 3"
		:key="i"
		@mouseover="(e) => (buttonRef = e.currentTarget)"
		@click="visible = !visible"
	>
		Click to open tooltip
	</fb-button>

	<fb-tooltip
		ref="tooltipRef"
		:visible="visible"
		:popper-options="{
			modifiers: [
				{
					name: 'computeStyles',
					options: {
						adaptive: false,
						enabled: false,
					},
				},
			],
		}"
		:virtual-ref="buttonRef"
		virtual-triggering
		popper-class="singleton-tooltip"
	>
		<template #content>
			<span> Some content </span>
		</template>
	</fb-tooltip>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const buttonRef = ref();
const tooltipRef = ref();

const visible = ref(false);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		setup: (): {
			buttonRef: Ref<HTMLElement | undefined>;
			tooltipRef: Ref<HTMLElement | undefined>;
			visible: Ref<boolean>;
		} => {
			const buttonRef = ref();
			const tooltipRef = ref();

			const visible = ref(false);

			return {
				buttonRef,
				tooltipRef,
				visible,
			};
		},
		template: `
<fb-button
	v-for="i in 3"
	:key="i"
	@mouseover="(e) => (buttonRef = e.currentTarget)"
	@click="visible = !visible"
>
	Click to open tooltip
</fb-button>

<fb-tooltip
	ref="tooltipRef"
	:visible="visible"
	:popper-options="{
		modifiers: [
			{
				name: 'computeStyles',
				options: {
					adaptive: false,
					enabled: false,
				},
			},
		],
	}"
	:virtual-ref="buttonRef"
	virtual-triggering
	popper-class="singleton-tooltip"
>
	<template #content>
		<span> Some content </span>
	</template>
</fb-tooltip>`,
	}),
};

export const Controlled: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip :visible="visible">
		<template #content>
			<span>Content</span>
		</template>

		<fb-button @mouseenter="visible = true" @mouseleave="visible = false">
			Hover me
		</fb-button>
	</fb-tooltip>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const visible = ref(false);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		setup: (): {
			visible: Ref<boolean>;
		} => {
			const visible = ref(false);

			return {
				visible,
			};
		},
		template: `
<fb-tooltip :visible="visible">
	<template #content>
		<span>Content</span>
	</template>

	<fb-button @mouseenter="visible = true" @mouseleave="visible = false">
		Hover me
	</fb-button>
</fb-tooltip>`,
	}),
};

export const Animations: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tooltip content="I am an fb-tooltip">
		<fb-button>Trigger me</fb-button>
	</fb-tooltip>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTooltip, FbButton },
		template: `
<fb-tooltip content="I am an fb-tooltip">
	<fb-button>Trigger me</fb-button>
</fb-tooltip>`,
	}),
};
