<template>
	<div
		ref="shadowScrollContainer"
		class="fb-theme-ui-scroll-shadow__container"
	>
		<div
			ref="shadowScrollContainerInner"
			:style="{ width, height }"
			class="fb-theme-ui-scroll-shadow__inner"
			@scroll.passive="onToggleShadow"
		>
			<slot />

			<span :class="['fb-theme-ui-scroll-shadow__shadow-top', { 'fb-theme-ui-scroll-shadow__shadow-active': shadow.top }]" />
			<span :class="['fb-theme-ui-scroll-shadow__shadow-right', { 'fb-theme-ui-scroll-shadow__shadow-active': shadow.right }]" />
			<span :class="['fb-theme-ui-scroll-shadow__shadow-bottom', { 'fb-theme-ui-scroll-shadow__shadow-active': shadow.bottom }]" />
			<span :class="['fb-theme-ui-scroll-shadow__shadow-left', { 'fb-theme-ui-scroll-shadow__shadow-active': shadow.left }]" />
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';

const newResizeObserver = (callback: () => void): ResizeObserver | undefined => {
	// Skip this feature for browsers which
	// do not support ResizeObserver
	// https://caniuse.com/#search=resizeobserver
	if (typeof ResizeObserver === 'undefined') {
		return undefined;
	}

	// @ts-ignore
	return new ResizeObserver((e: Element[]) => e.map(callback));
};

export default defineComponent({
	name: 'FbUiScrollShadow',

	setup() {
		const width = ref<string | undefined>(undefined);
		const height = ref<string | undefined>(undefined);

		const shadow = reactive({
			top: false,
			right: false,
			bottom: false,
			left: false,
		});

		const shadowScrollContainer = ref<HTMLElement | null>(null);
		const shadowScrollContainerInner = ref<HTMLElement | null>(null);

		let shadowScrollContainerInnerObserver: ResizeObserver | undefined;
		let wrapObserver: ResizeObserver | undefined;

		const onToggleShadow = (): void => {
			if (shadowScrollContainerInner.value !== null) {
				const hasHorizontalScrollbar = shadowScrollContainerInner.value.clientWidth < shadowScrollContainerInner.value.scrollWidth;
				const hasVerticalScrollbar = shadowScrollContainerInner.value.clientHeight < shadowScrollContainerInner.value.scrollHeight;

				const scrolledFromLeft = shadowScrollContainerInner.value.offsetWidth + shadowScrollContainerInner.value.scrollLeft;
				const scrolledFromTop = shadowScrollContainerInner.value.offsetHeight + shadowScrollContainerInner.value.scrollTop;

				const scrolledToTop = shadowScrollContainerInner.value.scrollTop === 0;
				const scrolledToRight = scrolledFromLeft >= shadowScrollContainerInner.value.scrollWidth;
				const scrolledToBottom = scrolledFromTop >= shadowScrollContainerInner.value.scrollHeight;
				const scrolledToLeft = shadowScrollContainerInner.value.scrollLeft === 0;

				shadow.top = hasVerticalScrollbar && !scrolledToTop;
				shadow.right = hasHorizontalScrollbar && !scrolledToRight;
				shadow.bottom = hasVerticalScrollbar && !scrolledToBottom;
				shadow.left = hasHorizontalScrollbar && !scrolledToLeft;
			}
		};

		const onCalcDimensions = async (): Promise<void> => {
			// Reset dimensions for correctly recalculating parent dimensions
			width.value = undefined;
			height.value = undefined;

			await nextTick();

			if (shadowScrollContainer.value !== null) {
				width.value = `${shadowScrollContainer.value.clientWidth}px`;
				height.value = `${shadowScrollContainer.value.clientHeight}px`;
			}
		};

		onMounted((): void => {
			// Check if shadows are necessary after the element is resized.
			shadowScrollContainerInnerObserver = newResizeObserver(onToggleShadow);

			if (shadowScrollContainerInnerObserver && shadowScrollContainerInner.value !== null) {
				shadowScrollContainerInnerObserver.observe(shadowScrollContainerInner.value);
			}

			// Recalculate the container dimensions when the wrapper is resized.
			wrapObserver = newResizeObserver(onCalcDimensions);

			if (wrapObserver && shadowScrollContainer.value !== null) {
				wrapObserver.observe(shadowScrollContainer.value);
			}
		});

		onUnmounted((): void => {
			if (shadowScrollContainerInnerObserver && shadowScrollContainerInner.value !== null) {
				// Cleanup when the component is destroyed
				shadowScrollContainerInnerObserver.disconnect();
			}

			if (wrapObserver && shadowScrollContainer.value !== null) {
				// Cleanup when the component is destroyed
				wrapObserver.disconnect();
			}
		});

		return {
			width,
			height,
			shadow,
			shadowScrollContainer,
			shadowScrollContainerInner,
			onToggleShadow,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
