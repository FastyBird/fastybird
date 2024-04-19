import { ref } from 'vue';

import { Meta, StoryObj } from '@storybook/vue3';
import { FbInputNumber, InputNumberControlPositionTypes } from '@fastybird/web-ui-components';
import { ComponentSizeTypes } from '@fastybird/web-ui-constants';

import './fb-input-number.stories.scss';

const meta: Meta<typeof FbInputNumber> = {
	component: FbInputNumber,
	title: 'Components/Form/Input number',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbInputNumber>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num" :min="1" :max="10" @change="handleChange" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(1);

const handleChange = (value: number) => {
	console.log(value);
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(1);

			const handleChange = (value: number): void => {
				console.log(value);
			};

			return {
				num,
				handleChange,
			};
		},
		template: `<fb-input-number v-model="num" :min="1" :max="10" @change="handleChange" />`,
	}),
};

export const Disabled: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num" :disabled="true" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(1);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(1);

			return {
				num,
			};
		},
		template: `<fb-input-number v-model="num" :disabled="true" />`,
	}),
};

export const Steps: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num" :step="2" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(5);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(5);

			return {
				num,
			};
		},
		template: `<fb-input-number v-model="num" :step="2" />`,
	}),
};

export const StrictlyStep: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num" :step="2" step-strictly />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(2);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(2);

			return {
				num,
			};
		},
		template: `<fb-input-number v-model="num" :step="2" step-strictly />`,
	}),
};

export const Precision: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num" :precision="2" :step="0.1" :max="10" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(1);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(1);

			return {
				num,
			};
		},
		template: `<fb-input-number v-model="num" :precision="2" :step="0.1" :max="10" />`,
	}),
};

export const Size: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number v-model="num1" size="${ComponentSizeTypes.LARGE}" />
	<fb-input-number v-model="num2" />
	<fb-input-number v-model="num3" size="${ComponentSizeTypes.SMALL}" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num1 = ref(1);
const num2 = ref(2);
const num3 = ref(3);
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num1 = ref(1);
			const num2 = ref(2);
			const num3 = ref(3);

			return {
				num1,
				num2,
				num3,
			};
		},
		template: `
<div class="fb-input-number-story-block">
	<fb-input-number v-model="num1" size="${ComponentSizeTypes.LARGE}" />
	<fb-input-number v-model="num2" />
	<fb-input-number v-model="num3" size="${ComponentSizeTypes.SMALL}" />
</div>`,
	}),
};

export const ControlsPosition: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		size="${ComponentSizeTypes.LARGE}"
		@change="handleChange"
	/>
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		@change="handleChange"
	/>
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		size="${ComponentSizeTypes.SMALL}"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		@change="handleChange"
	/>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const num = ref(1);

const handleChange = (value: number) => {
	console.log(value);
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbInputNumber },
		setup: () => {
			const num = ref(1);

			const handleChange = (value: number) => {
				console.log(value);
			};

			return {
				num,
				handleChange,
			};
		},
		template: `
<div class="fb-input-number-story-block">
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		size="${ComponentSizeTypes.LARGE}"
		@change="handleChange"
	/>
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		@change="handleChange"
	/>
	<fb-input-number
		v-model="num"
		:min="1"
		:max="10"
		size="${ComponentSizeTypes.SMALL}"
		controls-position="${InputNumberControlPositionTypes.RIGHT}"
		@change="handleChange"
	/>
</div>`,
	}),
};
