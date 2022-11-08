<template>
	<div :class="['fb-theme-layout-tabs-item__container', { 'fb-theme-layout-tabs-item__container-active': active }]">
		<template v-if="type === menuItemTypes.LINK">
			<fb-ui-button
				:href="link"
				:active="active"
				:variant="buttonVariants.LINK"
				:size="sizeTypes.LARGE"
				block
			>
				<span
					v-if="'icon' in $slots"
					class="fb-theme-layout-tabs-item__icon"
				>
					<slot name="icon" />
				</span>
				<span class="fb-theme-layout-tabs-item__label">{{ label }}</span>
			</fb-ui-button>
		</template>

		<template v-else-if="type === menuItemTypes.BUTTON">
			<fb-ui-button
				:variant="buttonVariants.LINK"
				:type="buttonTypes.BUTTON"
				:size="sizeTypes.LARGE"
				block
				@click.prevent="$emit('click', $event)"
			>
				<span
					v-if="'icon' in $slots"
					class="fb-theme-layout-tabs-item__icon"
				>
					<slot name="icon" />
				</span>
				<span class="fb-theme-layout-tabs-item__label">{{ label }}</span>
			</fb-ui-button>
		</template>
	</div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbSizeTypes, FbUiButtonVariantTypes, FbUiButtonButtonTypes, FbMenuItemTypes } from '@/types';
import FbUiButton from '@/components/ui/FbButton/index.vue';

export default defineComponent({
	name: 'FbLayoutTabsItem',

	components: {
		FbUiButton,
	},

	props: {
		type: {
			type: String as PropType<FbMenuItemTypes>,
			required: true,
			validator: (value: FbMenuItemTypes) => {
				// The value must match one of these strings
				return [FbMenuItemTypes.BUTTON, FbMenuItemTypes.LINK, FbMenuItemTypes.VUE_LINK].includes(value);
			},
		},

		label: {
			type: String as PropType<string>,
			default: null,
		},

		link: {
			type: String as PropType<string>,
			default: null,
		},

		active: {
			type: Boolean as PropType<boolean>,
			default: false,
		},
	},

	emits: ['click'],

	setup() {
		return {
			menuItemTypes: FbMenuItemTypes,
			sizeTypes: FbSizeTypes,
			buttonVariants: FbUiButtonVariantTypes,
			buttonTypes: FbUiButtonButtonTypes,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
