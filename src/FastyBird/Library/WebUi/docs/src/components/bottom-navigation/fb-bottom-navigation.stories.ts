import { Meta, StoryObj } from '@storybook/vue3';
import { action } from '@storybook/addon-actions';
import { FasTrash, FasXmark, FasHeart, FasPenToSquare } from '@fastybird/web-ui-icons';
import { FbBottomNavigation, FbBottomNavigationHeading, FbButton, FbIcon } from '@fastybird/web-ui-components';

import './fb-bottom-navigation.stories.scss';
import { ref } from 'vue';

const meta: Meta<typeof FbBottomNavigation> = {
	component: FbBottomNavigation,
	title: 'Components/Navigation/Bottom navigation',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbBottomNavigation>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
</template>

<script lang="ts" setup>
</script>

<style scoped>
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbBottomNavigation, FbBottomNavigationHeading, FbButton },
		setup: () => {
			const show = ref<boolean>(false);
			const onClick = action('button-clicked');

			return {
				show,
				onClick,
			};
		},
		template: `
<div class="fb-bottom-navigation-story-block">
	<fb-button @click="show = true">Open menu</fb-button>

	<fb-bottom-navigation
		:show="show"
		@close="show = false"
	>
		<template #heading>
			<fb-bottom-navigation-heading :teleport="false">
				Select one from items
			</fb-bottom-navigation-heading>
		</template>

		<fb-bottom-navigation-content :teleport="false">
			<fb-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option one
			</fb-button>
		</fb-bottom-navigation-content>

		<fb-bottom-navigation-content :teleport="false">
			<fb-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option two
			</fb-button>
		</fb-bottom-navigation-content>

		<fb-bottom-navigation-content :teleport="false">
			<fb-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option three
			</fb-button>
		</fb-bottom-navigation-content>
	</fb-bottom-navigation>
</div>`,
	}),
};
