<template>
	<template v-if="teleport">
		<teleport to="#fb-layout-header-sub-content">
			<div class="fb-theme-layout-phone-header-content__container">
				<slot />
			</div>
		</teleport>
	</template>

	<template v-else>
		<div class="fb-theme-layout-phone-header-content__container">
			<slot />
		</div>
	</template>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

export default defineComponent({
	name: 'FbLayoutHeaderContent',

	props: {
		teleport: {
			type: Boolean as PropType<boolean>,
			default: true,
		},
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
