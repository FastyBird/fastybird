<template>
	<div
		:id="props.range ? inputId : undefined"
		ref="sliderWrapper"
		:class="sliderClass"
		:role="props.range ? 'group' : undefined"
		:aria-label="props.range && !isLabeledByFormItem ? groupLabel : undefined"
		:aria-labelledby="props.range && isLabeledByFormItem ? formItem?.labelId : undefined"
		@touchstart="onSliderWrapperPrevent"
		@touchmove="onSliderWrapperPrevent"
	>
		<div
			ref="slider"
			:class="[ns.e('runway'), { 'show-input': props.showInput && !props.range }, ns.is('disabled', sliderDisabled)]"
			:style="runwayStyle"
			@mousedown="onSliderDown"
			@touchstart="onSliderDown"
		>
			<div :class="ns.e('bar')" :style="barStyle" />

			<fb-slider-button
				:id="!props.range ? inputId : undefined"
				ref="firstButton"
				:model-value="firstValue"
				:vertical="props.vertical"
				:tooltip-class="props.tooltipClass"
				:placement="props.placement"
				role="slider"
				:aria-label="props.range || !isLabeledByFormItem ? firstButtonLabel : undefined"
				:aria-labelledby="!props.range && isLabeledByFormItem ? formItem?.labelId : undefined"
				:aria-valuemin="props.min"
				:aria-valuemax="props.range ? secondValue : props.max"
				:aria-valuenow="firstValue"
				:aria-valuetext="firstValueText"
				:aria-orientation="props.vertical ? 'vertical' : 'horizontal'"
				:aria-disabled="sliderDisabled"
				@update:model-value="setFirstValue"
			/>

			<fb-slider-button
				v-if="range"
				ref="secondButton"
				:model-value="secondValue"
				:vertical="props.vertical"
				:tooltip-class="props.tooltipClass"
				:placement="props.placement"
				role="slider"
				:aria-label="secondButtonLabel"
				:aria-valuemin="firstValue"
				:aria-valuemax="props.max"
				:aria-valuenow="secondValue"
				:aria-valuetext="secondValueText"
				:aria-orientation="props.vertical ? 'vertical' : 'horizontal'"
				:aria-disabled="sliderDisabled"
				@update:model-value="setSecondValue"
			/>
			<div v-if="props.showStops">
				<div v-for="(item, key) in stops" :key="key" :class="ns.e('stop')" :style="getStopStyle(item)" />
			</div>
			<template v-if="markList.length > 0">
				<div>
					<div v-for="(item, key) in markList" :key="key" :style="getStopStyle(item.position)" :class="[ns.e('stop'), ns.e('marks-stop')]" />
				</div>
				<div :class="ns.e('marks')">
					<slider-marker v-for="(item, key) in markList" :key="key" :mark="item.mark" :style="getStopStyle(item.position)" />
				</div>
			</template>
		</div>

		<fb-input-number
			v-if="props.showInput && !props.range"
			ref="input"
			:model-value="firstValue"
			:class="ns.e('input')"
			:step="step"
			:disabled="sliderDisabled"
			:controls="props.showInputControls"
			:min="props.min"
			:max="props.max"
			:debounce="props.debounce"
			:size="sliderInputSize"
			@update:model-value="setFirstValue"
			@change="emitChange"
		/>
	</div>
</template>

<script lang="ts" setup>
import { computed, provide, reactive, toRefs } from 'vue';

import { useLocale, useNamespace } from '@fastybird/web-ui-hooks';

import { useFormSize } from '../../form';
import { useFormItemInputId } from '../../form-item';
import { FbInputNumber } from '../../input-number';
import { sliderContextKey } from './constants';
import { sliderEmits, sliderProps } from './slider';
import FbSliderButton from './button.vue';
import SliderMarker from './marker';
import { useLifecycle, useMarks, useSlide, useStops, useWatch } from './hooks';

import type { SliderInitData } from './slider';

defineOptions({
	name: 'FbSlider',
});

const props = defineProps(sliderProps);
const emit = defineEmits(sliderEmits);

const ns = useNamespace('slider');
const { t } = useLocale();

const initData = reactive<SliderInitData>({
	firstValue: 0,
	secondValue: 0,
	oldValue: 0,
	dragging: false,
	sliderSize: 1,
});

const {
	formItem,
	slider,
	firstButton,
	secondButton,
	sliderDisabled,
	minValue,
	maxValue,
	runwayStyle,
	barStyle,
	resetSize,
	emitChange,
	onSliderWrapperPrevent,
	onSliderClick,
	onSliderDown,
	setFirstValue,
	setSecondValue,
} = useSlide(props, initData, emit);

const { stops, getStopStyle } = useStops(props, initData, minValue, maxValue);

const { inputId, isLabeledByFormItem } = useFormItemInputId(props, {
	formItemContext: formItem,
});

const sliderWrapperSize = useFormSize();
const sliderInputSize = computed(() => props.inputSize || props.size || sliderWrapperSize.value);

const groupLabel = computed<string>(() => {
	return (
		props.label ||
		t('component.slider.defaultLabel', {
			min: props.min,
			max: props.max,
		})
	);
});

const firstButtonLabel = computed<string>(() => {
	if (props.range) {
		return props.rangeStartLabel || t('component.slider.defaultRangeStartLabel');
	} else {
		return groupLabel.value;
	}
});

const firstValueText = computed<string | undefined>((): string | undefined => {
	return props.formatValueText ? props.formatValueText(firstValue.value) : `${firstValue.value}`;
});

const secondButtonLabel = computed<string>((): string => {
	return props.rangeEndLabel || t('component.slider.defaultRangeEndLabel');
});

const secondValueText = computed<string | undefined>((): string | undefined => {
	return props.formatValueText ? props.formatValueText(secondValue.value) : `${secondValue.value}`;
});

const sliderClass = computed(() => [
	ns.b(),
	ns.m('size-' + sliderWrapperSize.value),
	ns.m('variant-' + props.variant),
	ns.is('vertical', props.vertical),
	{ [ns.m('with-input')]: props.showInput },
]);

const markList = useMarks(props);

useWatch(props, initData, minValue, maxValue, emit, formItem!);

const precision = computed(() => {
	const precisions = [props.min, props.max, props.step].map((item) => {
		const decimal = `${item}`.split('.')[1];
		return decimal ? decimal.length : 0;
	});
	return Math.max.apply(null, precisions);
});

const { sliderWrapper } = useLifecycle(props, initData, resetSize);

const { firstValue, secondValue, sliderSize } = toRefs(initData);

const updateDragging = (val: boolean): void => {
	initData.dragging = val;
};

provide(sliderContextKey, {
	...toRefs(props),
	sliderSize,
	disabled: sliderDisabled,
	precision,
	emitChange,
	resetSize,
	updateDragging,
});

defineExpose({
	onSliderClick,
});
</script>
