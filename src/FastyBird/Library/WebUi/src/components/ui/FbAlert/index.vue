<template>
	<div
		:data-variant="variant"
		class="fb-theme-ui-alert__container"
		role="alert"
	>
		<div
			v-if="'icon' in $slots"
			class="fb-theme-ui-alert__with-icon"
		>
			<div class="fb-theme-ui-alert__with-icon-icon">
				<slot name="icon" />
			</div>

			<div class="fb-theme-ui-alert__with-icon-content">
				<slot />
			</div>
		</div>

		<template v-else>
			<slot />
		</template>
	</div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

import { FbUiVariantTypes } from '@/types';

export default defineComponent({
	name: 'FbUiAlert',

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
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
