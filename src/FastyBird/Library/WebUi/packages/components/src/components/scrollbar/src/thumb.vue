<template>
	<transition :name="ns.b('fade')">
		<div
			v-show="always || visible"
			ref="wrapperRef"
			:class="[ns.e('bar'), ns.is(bar.key)]"
			@mousedown="clickTrackHandler"
		>
			<div
				ref="thumbRef"
				:class="ns.e('thumb')"
				:style="thumbStyle"
				@mousedown="clickThumbHandler"
			/>
		</div>
	</transition>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, inject, onBeforeUnmount, ref, toRef } from 'vue';

import { useEventListener } from '@vueuse/core';
import { isClient, throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { scrollbarContextKey } from './constants';
import { BAR_MAP, renderThumbStyle } from './util';
import { thumbProps } from './thumb';

defineOptions({
	name: 'FbThumb',
});

const props = defineProps(thumbProps);

const instance = getCurrentInstance();

const scrollbar = inject(scrollbarContextKey);
const ns = useNamespace('scrollbar');

if (!scrollbar) throwError(instance!.type.name!, 'can not inject scrollbar context');

const wrapperRef = ref<HTMLDivElement | undefined>();
const thumbRef = ref<HTMLDivElement | undefined>();

const thumbState = ref<Partial<Record<'X' | 'Y', number>>>({});
const visible = ref(false);

let cursorDown = false;
let cursorLeave = false;
let originalOnSelectStart: ((this: GlobalEventHandlers, ev: Event) => any) | null = isClient ? document.onselectstart : null;

const bar = computed(() => BAR_MAP[props.vertical ? 'vertical' : 'horizontal']);

const thumbStyle = computed(() =>
	renderThumbStyle({
		size: props.size,
		move: props.move,
		bar: bar.value,
	})
);

const offsetRatio = computed(
	() =>
		// offsetRatioX = original width of thumb / current width of thumb / ratioX
		// offsetRatioY = original height of thumb / current height of thumb / ratioY
		// instance height = wrap height - GAP
		wrapperRef.value![bar.value.offset] ** 2 / scrollbar.wrapElement![bar.value.scrollSize] / props.ratio / thumb.value![bar.value.offset]
);

const clickThumbHandler = (e: MouseEvent) => {
	// prevent click event of middle and right button
	e.stopPropagation();
	if (e.ctrlKey || [1, 2].includes(e.button)) {
		return;
	}

	window.getSelection()?.removeAllRanges();
	startDrag(e);

	const el = e.currentTarget as HTMLDivElement;

	if (!el) {
		return;
	}

	thumbState.value[bar.value.axis] = el[bar.value.offset] - (e[bar.value.client] - el.getBoundingClientRect()[bar.value.direction]);
};

const clickTrackHandler = (e: MouseEvent): void => {
	if (!thumbRef.value || !wrapperRef.value || !scrollbar.wrapElement) {
		return;
	}

	const offset = Math.abs((e.target as HTMLElement).getBoundingClientRect()[bar.value.direction] - e[bar.value.client]);
	const thumbHalf = thumbRef.value[bar.value.offset] / 2;
	const thumbPositionPercentage = ((offset - thumbHalf) * 100 * offsetRatio.value) / wrapperRef.value[bar.value.offset];

	scrollbar.wrapElement[bar.value.scroll] = (thumbPositionPercentage * scrollbar.wrapElement[bar.value.scrollSize]) / 100;
};

const startDrag = (e: MouseEvent): void => {
	e.stopImmediatePropagation();
	cursorDown = true;
	document.addEventListener('mousemove', mouseMoveDocumentHandler);
	document.addEventListener('mouseup', mouseUpDocumentHandler);
	originalOnSelectStart = document.onselectstart;
	document.onselectstart = () => false;
};

const mouseMoveDocumentHandler = (e: MouseEvent): void => {
	if (!wrapperRef.value || !thumbRef.value) {
		return;
	}

	if (cursorDown === false) {
		return;
	}

	const prevPage = thumbState.value[bar.value.axis];

	if (!prevPage) {
		return;
	}

	const offset = (wrapperRef.value.getBoundingClientRect()[bar.value.direction] - e[bar.value.client]) * -1;
	const thumbClickPosition = thumbRef.value[bar.value.offset] - prevPage;
	const thumbPositionPercentage = ((offset - thumbClickPosition) * 100 * offsetRatio.value) / wrapperRef.value[bar.value.offset];

	if (scrollbar.wrapElement) {
		scrollbar.wrapElement[bar.value.scroll] = (thumbPositionPercentage * scrollbar.wrapElement[bar.value.scrollSize]) / 100;
	}
};

const mouseUpDocumentHandler = (): void => {
	cursorDown = false;
	thumbState.value[bar.value.axis] = 0;
	document.removeEventListener('mousemove', mouseMoveDocumentHandler);
	document.removeEventListener('mouseup', mouseUpDocumentHandler);
	restoreOnselectstart();
	if (cursorLeave) visible.value = false;
};

const mouseMoveScrollbarHandler = (): void => {
	cursorLeave = false;
	visible.value = !!props.size;
};

const mouseLeaveScrollbarHandler = (): void => {
	cursorLeave = true;
	visible.value = cursorDown;
};

onBeforeUnmount((): void => {
	restoreOnselectstart();
	document.removeEventListener('mouseup', mouseUpDocumentHandler);
});

const restoreOnselectstart = (): void => {
	if (document.onselectstart !== originalOnSelectStart) document.onselectstart = originalOnSelectStart;
};

useEventListener(toRef(scrollbar, 'scrollbarElement'), 'mousemove', mouseMoveScrollbarHandler);
useEventListener(toRef(scrollbar, 'scrollbarElement'), 'mouseleave', mouseLeaveScrollbarHandler);
</script>
