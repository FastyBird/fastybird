<template>
	<transition
		name="fb-ui-transition-expand-expand"
		@enter="enter"
		@after-enter="afterEnter"
		@leave="leave"
	>
		<slot v-if="show" />
	</transition>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

export default defineComponent({
	name: 'FbUiTransitionExpand',

	props: {
		show: {
			type: Boolean as PropType<boolean>,
			default: true,
		},
	},

	setup() {
		const enter = (element: HTMLElement): void => {
			const computedStyle = getComputedStyle(element);

			Object.assign(element.style, { width: computedStyle.width });
			Object.assign(element.style, { position: 'absolute' });
			Object.assign(element.style, { visibility: 'hidden' });
			Object.assign(element.style, { height: 'auto' });

			const reComputedStyle = getComputedStyle(element);

			const height = reComputedStyle.height;

			Object.assign(element.style, { width: null });
			Object.assign(element.style, { position: null });
			Object.assign(element.style, { visibility: null });
			Object.assign(element.style, { height: 0 });

			// Trigger the animation.
			// We use `requestAnimationFrame` because we need
			// to make sure the browser has finished
			// painting after setting the `height`
			// to `0` in the line above.
			requestAnimationFrame(() => {
				Object.assign(element.style, { height });
			});
		};

		const afterEnter = (element: HTMLElement): void => {
			Object.assign(element.style, { height: 'auto' });
		};

		const leave = (element: HTMLElement): void => {
			const computedStyle = getComputedStyle(element);

			Object.assign(element.style, { height: computedStyle.height });

			requestAnimationFrame(() => {
				Object.assign(element.style, { height: 0 });
			});
		};

		return {
			enter,
			afterEnter,
			leave,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>

<style rel="stylesheet/scss" lang="scss" scoped>
* {
	will-change: height;
	transform: translateZ(0);
	backface-visibility: hidden;
	perspective: 1000px;
}
</style>
