<template>
	<div
		:data-size="size"
		class="fb-theme-ui-result-ok__container"
	>
		<svg
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 130.2 130.2"
		>
			<circle
				cx="65.1"
				cy="65.1"
				r="62.1"
			/>
			<polyline points="100.2,40.2 51.5,88.8 29.8,67.5" />
		</svg>

		<div
			v-if="'default' in $slots"
			class="fb-theme-ui-result-ok__content"
		>
			<slot />
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbSizeTypes } from '@/types';

export default defineComponent({
	name: 'FbUiResultOk',

	props: {
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
