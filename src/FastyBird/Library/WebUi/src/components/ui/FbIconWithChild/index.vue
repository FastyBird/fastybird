w
<template>
	<span
		:data-variant="variant"
		:data-size="size"
		class="fb-theme-ui-icon-with-child__container"
	>
		<span class="fb-theme-ui-icon-with-child__icon">
			<slot name="main" />
		</span>

		<span class="fb-theme-ui-icon-with-child__child-icon">
			<slot name="child" />
		</span>
	</span>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbSizeTypes, FbUiVariantTypes } from '@/types';

export default defineComponent({
	name: 'FbUiIconWithChild',

	props: {
		variant: {
			type: String as PropType<FbUiVariantTypes>,
			default: FbUiVariantTypes.DEFAULT,
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
