<template>
	<div
		ref="button"
		:class="[ns.e('button-wrapper'), { hover: hovering, dragging }]"
		:style="wrapperStyle"
		:tabindex="disabled ? -1 : 0"
		@mouseenter="handleMouseEnter"
		@mouseleave="handleMouseLeave"
		@mousedown="onButtonDown"
		@touchstart="onButtonDown"
		@focus="handleMouseEnter"
		@blur="handleMouseLeave"
		@keydown="onKeyDown"
	>
		<fb-tooltip
			ref="tooltip"
			:visible="tooltipVisible"
			:placement="props.placement"
			:fallback-placements="['top', 'bottom', 'right', 'left']"
			:stop-popper-mouse-event="false"
			:popper-class="props.tooltipClass"
			:disabled="!showTooltip"
			persistent
		>
			<template #content>
				<span>{{ formatValue }}</span>
			</template>
			<div :class="[ns.e('button'), { hover: hovering, dragging }]" />
		</fb-tooltip>
	</div>
</template>

<script lang="ts" setup>
import { reactive, toRefs } from 'vue';

import { FbTooltip } from '../../tooltip';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { useSliderButton } from './hooks';
import { sliderButtonEmits, sliderButtonProps } from './button';
import type { SliderButtonInitData } from './button';

defineOptions({
	name: 'FbSliderButton',
});

const props = defineProps(sliderButtonProps);
const emit = defineEmits(sliderButtonEmits);

const ns = useNamespace('slider');

const initData = reactive<SliderButtonInitData>({
	hovering: false,
	dragging: false,
	isClick: false,
	startX: 0,
	currentX: 0,
	startY: 0,
	currentY: 0,
	startPosition: 0,
	newPosition: 0,
	oldValue: props.modelValue,
});

const {
	disabled,
	button,
	tooltip,
	showTooltip,
	tooltipVisible,
	wrapperStyle,
	formatValue,
	handleMouseEnter,
	handleMouseLeave,
	onButtonDown,
	onKeyDown,
	setPosition,
} = useSliderButton(props, initData, emit);

const { hovering, dragging } = toRefs(initData);

defineExpose({
	/** @description: */
	onButtonDown,
	/** @description: */
	onKeyDown,
	/** @description: */
	setPosition,
	/** @description: */
	hovering,
	/** @description: */
	dragging,
});
</script>
