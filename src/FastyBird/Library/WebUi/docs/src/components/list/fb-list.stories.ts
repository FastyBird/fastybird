import { ref } from 'vue';

import { Meta, StoryObj } from '@storybook/vue3';
import { action } from '@storybook/addon-actions';
import { FasGear, FasPlug } from '@fastybird/web-ui-icons';
import { FbList, FbListItem, FbButton, FbSwitch, FbIcon, ItemVariantTypes } from '@fastybird/web-ui-components';
import { ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';

import './fb-list.stories.scss';

const meta: Meta<typeof FbList> = {
	component: FbList,
	subcomponents: { FbListItem },
	title: 'Components/Data/List',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbList>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-list>
		<template #heading>List of awesome items</template>
		<template #subheading>Subheading of awesome items</template>
		<template #buttons>
			<fb-button size="${ComponentSizeTypes.SMALL}" @click.prevent="onClick">Add</fb-button>
			<fb-button size="${ComponentSizeTypes.SMALL}" variant="${VariantTypes.WARNING}" :icon="FasGear" @click.prevent="onClick" />
		</template>
		<fb-list-item @click="onClick">
			<template #title>Temperature</template>
			<template #subtitle>Master room measured temperature</template>
			<template #detail>23 °C</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick">
			<template #title>Humidity</template>
			<template #subtitle>Master room measured humidity</template>
			<template #detail>43 %</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick">
			<template #title>Heater</template>
			<template #subtitle>Master room heater switch</template>
			<template #button>
				<fb-switch v-model="switchState" />
			</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
	</fb-list>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const switchState = ref(true);

const onClick = () => {
	console.log('clicked')
};
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbList, FbListItem, FbButton, FbSwitch, FbIcon, FasGear, FasPlug },
		setup: () => {
			const switchState = ref(true);
			const onClick = action('button-clicked');

			return {
				switchState,
				onClick,
				FasGear,
				FasPlug,
			};
		},
		template: `
<fb-list>
	<template #heading>List of awesome items</template>
	<template #subheading>Subheading of awesome items</template>
	<template #buttons>
		<fb-button size="${ComponentSizeTypes.SMALL}" @click.prevent="onClick">Add</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" variant="${VariantTypes.WARNING}" :icon="FasGear" @click.prevent="onClick" />
	</template>
	<fb-list-item @click="onClick">
		<template #title>Temperature</template>
		<template #subtitle>Master room measured temperature</template>
		<template #detail>23 °C</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick">
		<template #title>Humidity</template>
		<template #subtitle>Master room measured humidity</template>
		<template #detail>43 %</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick">
		<template #title>Heater</template>
		<template #subtitle>Master room heater switch</template>
		<template #button>
			<fb-switch v-model="switchState" />
		</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
</fb-list>`,
	}),
};

export const ListStyle: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-list>
		<template #heading>List of awesome items</template>
		<template #subheading>Subheading of awesome items</template>
		<template #buttons>
			<fb-button size="${ComponentSizeTypes.SMALL}" @click.prevent="onClick">Add</fb-button>
			<fb-button size="${ComponentSizeTypes.SMALL}" variant="${VariantTypes.WARNING}" :icon="FasGear" @click.prevent="onClick" />
		</template>
		<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
			<template #title>Temperature</template>
			<template #subtitle>Master room measured temperature</template>
			<template #detail>23 °C</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
			<template #title>Humidity</template>
			<template #subtitle>Master room measured humidity</template>
			<template #detail>43 %</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
			<template #title>Heater</template>
			<template #subtitle>Master room heater switch</template>
			<template #button>
				<fb-switch v-model="switchState" />
			</template>
			<template #icon>
				<fb-icon>
					<fas-plug />
				</fb-icon>
			</template>
		</fb-list-item>
	</fb-list>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const switchState = ref(true);

const onClick = () => {
	console.log('clicked')
};
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbList, FbListItem, FbButton, FbSwitch, FbIcon, FasGear, FasPlug },
		setup: () => {
			const switchState = ref(true);
			const onClick = action('button-clicked');

			return {
				switchState,
				onClick,
				FasGear,
				FasPlug,
			};
		},
		template: `
<fb-list>
	<template #heading>List of awesome items</template>
	<template #subheading>Subheading of awesome items</template>
	<template #buttons>
		<fb-button size="${ComponentSizeTypes.SMALL}" @click.prevent="onClick">Add</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" variant="${VariantTypes.WARNING}" :icon="FasGear" @click.prevent="onClick" />
	</template>
	<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
		<template #title>Temperature</template>
		<template #subtitle>Master room measured temperature</template>
		<template #detail>23 °C</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
		<template #title>Humidity</template>
		<template #subtitle>Master room measured humidity</template>
		<template #detail>43 %</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick" variant="${ItemVariantTypes.LIST}">
		<template #title>Heater</template>
		<template #subtitle>Master room heater switch</template>
		<template #button>
			<fb-switch v-model="switchState" />
		</template>
		<template #icon>
			<fb-icon>
				<fas-plug />
			</fb-icon>
		</template>
	</fb-list-item>
</fb-list>`,
	}),
};
