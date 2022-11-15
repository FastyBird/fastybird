<template>
	<div class="fb-theme-ui-media-item__container">
		<div
			v-if="'left' in $slots"
			class="fb-theme-ui-media-item__left"
		>
			<slot name="left" />
		</div>

		<div class="fb-theme-ui-media-item__body">
			<h3>
				<slot name="heading" />
			</h3>

			<p v-if="'description' in $slots">
				<slot name="description" />
			</p>

			<p v-if="'action' in $slots">
				<slot name="action" />
			</p>
		</div>

		<div
			v-if="'right' in $slots"
			class="fb-theme-ui-media-item__right"
		>
			<slot name="right" />
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
	name: 'FbUiMediaItem',
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
