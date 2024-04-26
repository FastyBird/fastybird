<template>
	<div ref="scrollbarRef" :class="ns.b()">
		<div ref="wrapperRef" :class="wrapperClass" :style="wrapperStyle" @scroll="handleScroll">
			<component
				:is="props.tag"
				:id="props.id"
				ref="resizeRef"
				:class="resizeClass"
				:style="props.viewStyle"
				:role="props.role"
				:aria-label="props.ariaLabel"
				:aria-orientation="props.ariaOrientation"
			>
				<slot />
			</component>

			<span v-if="props.shadow" :class="[ns.e('shadow-top'), { [ns.em('shadow-top', 'active')]: shadow.top }, ns.em('shadow-top', props.shadow)]" />
			<span
				v-if="props.shadow"
				:class="[ns.e('shadow-right'), { [ns.em('shadow-right', 'active')]: shadow.right }, ns.em('shadow-right', props.shadow)]"
			/>
			<span
				v-if="props.shadow"
				:class="[ns.e('shadow-bottom'), { [ns.em('shadow-bottom', 'active')]: shadow.bottom }, ns.em('shadow-bottom', props.shadow)]"
			/>
			<span
				v-if="props.shadow"
				:class="[ns.e('shadow-left'), { [ns.em('shadow-left', 'active')]: shadow.left }, ns.em('shadow-left', props.shadow)]"
			/>
		</div>
		<template v-if="!props.native">
			<fb-bar ref="barRef" :always="props.always" :min-size="props.minSize" />
		</template>
	</div>
</template>

<script lang="ts" setup>
import { computed, nextTick, onMounted, onUnmounted, onUpdated, provide, reactive, ref, watch } from 'vue';

import { useEventListener, useResizeObserver } from '@vueuse/core';
import { addUnit, debugWarn, isNumber, isObject } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import FbBar from './bar.vue';
import { scrollbarContextKey } from './constants';
import { scrollbarEmits, scrollbarProps } from './scrollbar';

import type { BarInstance } from './bar';
import type { CSSProperties, StyleValue } from 'vue';

const COMPONENT_NAME = 'FbScrollbar';

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

defineOptions({
	name: COMPONENT_NAME,
});

const props = defineProps(scrollbarProps);
const emit = defineEmits(scrollbarEmits);

const ns = useNamespace('scrollbar');

let stopResizeObserver: (() => void) | undefined = undefined;
let stopResizeListener: (() => void) | undefined = undefined;

const scrollbarRef = ref<HTMLDivElement | undefined>();
const wrapperRef = ref<HTMLDivElement | undefined>();
const resizeRef = ref<HTMLElement | undefined>();
const barRef = ref<BarInstance | undefined>();

const shadow = reactive({
	top: false,
	right: false,
	bottom: false,
	left: false,
});

let shadowScrollContainerInnerObserver: ResizeObserver | undefined;

const wrapperStyle = computed<StyleValue>((): StyleValue => {
	const style: CSSProperties = {};

	if (props.height) style.height = addUnit(props.height);

	if (props.maxHeight) style.maxHeight = addUnit(props.maxHeight);

	return [props.wrapStyle, style];
});

const wrapperClass = computed<(string | { [x: string]: boolean } | string[] | undefined)[]>(
	(): (string | { [x: string]: boolean } | string[] | undefined)[] => {
		return [props.wrapClass, ns.e('wrap'), { [ns.em('wrap', 'hidden-default')]: !props.native }];
	}
);

const resizeClass = computed<(string | { [x: string]: boolean } | string[] | undefined)[]>(
	(): (string | { [x: string]: boolean } | string[] | undefined)[] => {
		return [ns.e('view'), props.viewClass];
	}
);

const onToggleShadow = (): void => {
	if (wrapperRef.value) {
		const hasHorizontalScrollbar = wrapperRef.value.clientWidth < wrapperRef.value.scrollWidth;
		const hasVerticalScrollbar = wrapperRef.value.clientHeight < wrapperRef.value.scrollHeight;

		const scrolledFromLeft = wrapperRef.value.offsetWidth + wrapperRef.value.scrollLeft;
		const scrolledFromTop = wrapperRef.value.offsetHeight + wrapperRef.value.scrollTop;

		const scrolledToTop = wrapperRef.value.scrollTop === 0;
		const scrolledToRight = scrolledFromLeft >= wrapperRef.value.scrollWidth;
		const scrolledToBottom = scrolledFromTop >= wrapperRef.value.scrollHeight;
		const scrolledToLeft = wrapperRef.value.scrollLeft === 0;

		shadow.top = hasVerticalScrollbar && !scrolledToTop;
		shadow.right = hasHorizontalScrollbar && !scrolledToRight;
		shadow.bottom = hasVerticalScrollbar && !scrolledToBottom;
		shadow.left = hasHorizontalScrollbar && !scrolledToLeft;
	}
};

const handleScroll = (): void => {
	if (wrapperRef.value) {
		barRef.value?.handleScroll(wrapperRef.value);

		emit('scroll', {
			scrollTop: wrapperRef.value.scrollTop,
			scrollLeft: wrapperRef.value.scrollLeft,
		});

		onToggleShadow();
	}
};

const scrollTo = (arg1: ScrollToOptions | number, arg2?: number): void => {
	if (isObject(arg1)) {
		wrapperRef.value!.scrollTo(arg1);
	} else if (isNumber(arg1) && isNumber(arg2)) {
		wrapperRef.value!.scrollTo(arg1, arg2);
	}
};

const setScrollTop = (value: number): void => {
	if (!isNumber(value)) {
		debugWarn(COMPONENT_NAME, 'value must be a number');

		return;
	}

	wrapperRef.value!.scrollTop = value;
};

const setScrollLeft = (value: number): void => {
	if (!isNumber(value)) {
		debugWarn(COMPONENT_NAME, 'value must be a number');

		return;
	}

	wrapperRef.value!.scrollLeft = value;
};

const update = (): void => {
	barRef.value?.update();
};

onMounted((): void => {
	// Check if shadows are necessary after the element is resized.
	shadowScrollContainerInnerObserver = newResizeObserver(onToggleShadow);

	if (shadowScrollContainerInnerObserver && wrapperRef.value) {
		shadowScrollContainerInnerObserver.observe(wrapperRef.value);
	}

	if (!props.native)
		nextTick(() => {
			update();
		});
});

onUnmounted((): void => {
	if (shadowScrollContainerInnerObserver && wrapperRef.value) {
		// Cleanup when the component is destroyed
		shadowScrollContainerInnerObserver.disconnect();
	}
});

onUpdated((): void => update());

watch(
	(): boolean => props.noresize,
	(noresize: boolean) => {
		if (noresize) {
			stopResizeObserver?.();
			stopResizeListener?.();
		} else {
			({ stop: stopResizeObserver } = useResizeObserver(resizeRef, update));
			stopResizeListener = useEventListener('resize', update);
		}
	},
	{ immediate: true }
);

watch(
	() => [props.maxHeight, props.height],
	() => {
		if (!props.native)
			nextTick(() => {
				update();
				if (wrapperRef.value) {
					barRef.value?.handleScroll(wrapperRef.value);
				}
			});
	}
);

provide(
	scrollbarContextKey,
	reactive({
		scrollbarElement: scrollbarRef,
		wrapElement: wrapperRef,
	})
);

defineExpose({
	/** @description scrollbar wrap ref */
	wrapperRef,
	/** @description update scrollbar state manually */
	update,
	/** @description scrolls to a particular set of coordinates */
	scrollTo,
	/** @description set distance to scroll top */
	setScrollTop,
	/** @description set distance to scroll left */
	setScrollLeft,
	/** @description handle scroll event */
	handleScroll,
});
</script>
