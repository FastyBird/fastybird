import { ref } from 'vue';

import { Meta, StoryObj } from '@storybook/vue3';
import { FbRadio, FbRadioGroup, FbRadioButton } from '@fastybird/web-ui-components';
import { ComponentSizeTypes } from '@fastybird/web-ui-constants';

import './fb-radio.stories.scss';

const meta: Meta<typeof FbRadio> = {
	component: FbRadio,
	title: 'Components/Form/Radio',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbRadio>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div>
		<fb-radio-group v-model="radio1">
			<fb-radio value="1" size="${ComponentSizeTypes.LARGE}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.LARGE}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio2">
			<fb-radio value="1">Option 1</fb-radio>
			<fb-radio value="2">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio3">
			<fb-radio value="1" size="${ComponentSizeTypes.SMALL}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.SMALL}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio3" disabled>
			<fb-radio value="1" size="${ComponentSizeTypes.SMALL}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.SMALL}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const radio1 = ref('1');
const radio2 = ref('1');
const radio3 = ref('1');
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbRadio, FbRadioGroup },
		setup: () => {
			const radio1 = ref('1');
			const radio2 = ref('1');
			const radio3 = ref('1');

			return {
				radio1,
				radio2,
				radio3,
			};
		},
		template: `
<div class="fb-radio-story-block">
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio1">
			<fb-radio value="1" size="${ComponentSizeTypes.LARGE}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.LARGE}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio2">
			<fb-radio value="1">Option 1</fb-radio>
			<fb-radio value="2">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio3">
			<fb-radio value="1" size="${ComponentSizeTypes.SMALL}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.SMALL}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio3" disabled>
			<fb-radio value="1" size="${ComponentSizeTypes.SMALL}">Option 1</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.SMALL}">Option 2</fb-radio>
		</fb-radio-group>
	</div>
</div>`,
	}),
};

export const Disabled: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-radio v-model="radio" disabled value="disabled">Option A</fb-radio>
	<fb-radio v-model="radio" disabled value="selected and disabled">Option B</fb-radio>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const radio = ref('selected and disabled');
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbRadio },
		setup: () => {
			const radio = ref('selected and disabled');

			return {
				radio,
			};
		},
		template: `
<div class="fb-radio-story-block">
	<fb-radio v-model="radio" disabled value="disabled">Option A</fb-radio>
	<fb-radio v-model="radio" disabled value="selected and disabled">Option B</fb-radio>
</div>`,
	}),
};

export const RadioGroup: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-radio-group v-model="radio">
		<fb-radio :value="3">Option A</fb-radio>
		<fb-radio :value="6">Option B</fb-radio>
		<fb-radio :value="9">Option C</fb-radio>
	</fb-radio-group>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const radio = ref(3)
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbRadio, FbRadioGroup },
		setup: () => {
			const radio = ref(3);

			return {
				radio,
			};
		},
		template: `
<div class="fb-radio-story-block">
	<fb-radio-group v-model="radio">
		<fb-radio :value="3">Option A</fb-radio>
		<fb-radio :value="6">Option B</fb-radio>
		<fb-radio :value="9">Option C</fb-radio>
	</fb-radio-group>
</div>`,
	}),
};

export const Button: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div>
		<fb-radio-group v-model="radio1" size="${ComponentSizeTypes.LARGE}">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio2">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio3" size="${ComponentSizeTypes.SMALL}">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" disabled />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const radio1 = ref('New York');
const radio2 = ref('New York');
const radio3 = ref('New York');
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbRadio, FbRadioGroup, FbRadioButton },
		setup: () => {
			const radio1 = ref('New York');
			const radio2 = ref('New York');
			const radio3 = ref('New York');

			return {
				radio1,
				radio2,
				radio3,
			};
		},
		template: `
<div class="fb-radio-story-block">
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio1" size="${ComponentSizeTypes.LARGE}">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio2">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio3" size="${ComponentSizeTypes.SMALL}">
			<fb-radio-button label="New York" value="New York" />
			<fb-radio-button label="Washington" value="Washington" disabled />
			<fb-radio-button label="Los Angeles" value="Los Angeles" />
			<fb-radio-button label="Chicago" value="Chicago" />
		</fb-radio-group>
	</div>
</div>`,
	}),
};

export const Bordered: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<div>
		<fb-radio-group v-model="radio1">
			<fb-radio value="1" size="${ComponentSizeTypes.LARGE}" border>Option A</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.LARGE}" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio2">
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio3" size="${ComponentSizeTypes.SMALL}">
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border disabled>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div>
		<fb-radio-group v-model="radio4" size="${ComponentSizeTypes.SMALL}" disabled>
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const radio1 = ref('1');
const radio2 = ref('1');
const radio3 = ref('1');
const radio4 = ref('1');
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbRadio, FbRadioGroup },
		setup: () => {
			const radio1 = ref('1');
			const radio2 = ref('1');
			const radio3 = ref('1');
			const radio4 = ref('1');

			return {
				radio1,
				radio2,
				radio3,
				radio4,
			};
		},
		template: `
<div class="fb-radio-story-block">
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio1">
			<fb-radio value="1" size="${ComponentSizeTypes.LARGE}" border>Option A</fb-radio>
			<fb-radio value="2" size="${ComponentSizeTypes.LARGE}" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio2">
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio3" size="${ComponentSizeTypes.SMALL}">
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border disabled>Option B</fb-radio>
		</fb-radio-group>
	</div>
	<div class="fb-radio-story-block__item">
		<fb-radio-group v-model="radio4" size="${ComponentSizeTypes.SMALL}" disabled>
			<fb-radio value="1" border>Option A</fb-radio>
			<fb-radio value="2" border>Option B</fb-radio>
		</fb-radio-group>
	</div>
</div>`,
	}),
};
