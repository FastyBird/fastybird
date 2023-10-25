<template>
	<transition :name="animation ? 'fb-theme-ui-loading-box-bounce' : 'none'">
		<div
			v-if="animation"
			:data-variant="variant"
			:data-size="size"
			:data-full-screen="fullScreen"
			class="fb-theme-ui-loading-box__container"
		>
			<div class="fb-theme-ui-loading-box__inner">
				<div
					v-if="'icon' in $slots"
					class="fb-theme-ui-loading-box__icon"
				>
					<slot name="icon" />
				</div>

				<fb-ui-spinner
					:size="size"
					:variant="variant"
				/>

				<div
					v-if="'default' in $slots"
					class="fb-theme-ui-loading-box__content"
				>
					<slot />
				</div>
			</div>
		</div>
	</transition>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbSizeTypes, FbUiVariantTypes } from '@/types';
import FbUiSpinner from '@/components/ui/FbSpinner/index.vue';

export default defineComponent({
	name: 'FbUiLoadingBox',

	components: {
		FbUiSpinner,
	},

	props: {
		fullScreen: {
			type: Boolean as PropType<boolean>,
			default: false,
		},

		animation: {
			type: Boolean as PropType<boolean>,
			default: false,
		},

		variant: {
			type: String as PropType<FbUiVariantTypes>,
			default: FbUiVariantTypes.PRIMARY,
			validator: (value: FbUiVariantTypes) => {
				// The value must match one of these strings
				return [
					FbUiVariantTypes.DEFAULT,
					FbUiVariantTypes.PRIMARY,
					FbUiVariantTypes.SUCCESS,
					FbUiVariantTypes.DANGER,
					FbUiVariantTypes.WARNING,
					FbUiVariantTypes.INFO,
				].includes(value);
			},
		},

		size: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.MEDIUM,
			validator: (value: FbSizeTypes) => {
				// The value must match one of these strings
				return [FbSizeTypes.SMALL, FbSizeTypes.MEDIUM, FbSizeTypes.LARGE].includes(value);
			},
		},
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
