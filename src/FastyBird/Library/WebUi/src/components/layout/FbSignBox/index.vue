<template>
	<div class="fb-theme-layout-sign-box__container">
		<div class="fb-theme-layout-sign-box__container-inner">
			<slot name="logo" />

			<slot />
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'FbLayoutSignBox',
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
