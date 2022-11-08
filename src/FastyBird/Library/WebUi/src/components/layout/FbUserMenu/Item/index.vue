<template>
	<li class="fb-theme-layout-user-menu-item__container">
		<template v-if="actionType === menuItemTypes.LINK">
			<a
				:href="`${action}`"
				@click.prevent="$emit('click', $event)"
			>
				{{ label }}
			</a>
		</template>

		<template v-else-if="actionType === menuItemTypes.VUE_LINK">
			<router-link
				:to="action"
				active-class="fb-theme-layout-user-menu-item__active"
				@click.prevent="$emit('click', $event)"
			>
				{{ label }}
			</router-link>
		</template>

		<template v-else-if="actionType === menuItemTypes.BUTTON">
			<button @click.prevent="$emit('click', $event)">
				{{ label }}
			</button>
		</template>

		<span v-else>
			{{ label }}
		</span>
	</li>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbMenuItemTypes } from '@/types';

export default defineComponent({
	name: 'FbLayoutUserMenuItem',

	props: {
		action: {
			type: [String, Object] as PropType<string | { [key: string]: any } | null>,
			default: null,
		},

		actionType: {
			type: String as PropType<FbMenuItemTypes>,
			default: FbMenuItemTypes.BUTTON,
			validator: (value: FbMenuItemTypes) => {
				// The value must match one of these strings
				return [FbMenuItemTypes.BUTTON, FbMenuItemTypes.LINK, FbMenuItemTypes.VUE_LINK, FbMenuItemTypes.TEXT].includes(value);
			},
		},

		label: {
			type: String as PropType<string>,
			required: true,
		},
	},

	emits: ['click'],

	setup() {
		return {
			menuItemTypes: FbMenuItemTypes,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
