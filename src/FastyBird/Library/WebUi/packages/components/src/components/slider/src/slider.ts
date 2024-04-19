import { placements } from '@popperjs/core';
import { buildProps, definePropType, isArray, isNumber } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, ComponentSize, ComponentSizeTypes, INPUT_EVENT, UPDATE_MODEL_EVENT, Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';
import type { Arrayable } from '@fastybird/web-ui-utils';
import type { SliderMarkerProps } from './marker';

type SliderMarks = Record<number, string | SliderMarkerProps['mark']>;

export interface SliderInitData {
	firstValue: number;
	secondValue: number;
	oldValue?: Arrayable<number>;
	dragging: boolean;
	sliderSize: number;
}

export const sliderProps = buildProps({
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<Arrayable<number> | number>([Number, Array]),
		default: 0,
	},
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description minimum value
	 */
	min: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description maximum value
	 */
	max: {
		type: definePropType<number>(Number),
		default: 100,
	},
	/**
	 * @description step size
	 */
	step: {
		type: definePropType<number>(Number),
		default: 1,
	},
	/**
	 * @description whether to display an input box, works when `range` is false
	 */
	showInput: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether to display control buttons when `show-input` is true
	 */
	showInputControls: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description size of the slider wrapper, will not work in vertical mode
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description switch variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description size of the input box, when set `size`, the default is the value of `size`
	 */
	inputSize: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description whether to display breakpoints
	 */
	showStops: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether to display tooltip value
	 */
	showTooltip: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description format to display tooltip value
	 */
	formatTooltip: {
		type: definePropType<(val: number) => number | string>(Function),
		default: undefined,
	},
	/**
	 * @description whether Slider is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether to select a range
	 */
	range: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description vertical mode
	 */
	vertical: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description slider height, required in vertical mode
	 */
	height: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description debounce delay when typing, in milliseconds, works when `show-input` is true
	 */
	debounce: {
		type: definePropType<number>(Number),
		default: 300,
	},
	/**
	 * @description label for screen reader
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description when `range` is true, screen reader label for the start of the range
	 */
	rangeStartLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description when `range` is true, screen reader label for the end of the range
	 */
	rangeEndLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description format to display the `aria-valuenow` attribute for screen readers
	 */
	formatValueText: {
		type: definePropType<(val: number) => string | undefined>(Function),
		default: undefined,
	},
	/**
	 * @description custom class name for the tooltip
	 */
	tooltipClass: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description position of Tooltip
	 */
	placement: {
		type: definePropType<string>(String),
		values: placements,
		default: 'top',
	},
	/**
	 * @description marks, type of key must be `number` and must in closed interval `[min, max]`, each mark can custom style
	 */
	marks: {
		type: definePropType<SliderMarks>(Object),
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type SliderProps = ExtractPropTypes<typeof sliderProps>;

const isValidValue = (value: Arrayable<number>) => isNumber(value) || (isArray(value) && value.every(isNumber));

export const sliderEmits = {
	[UPDATE_MODEL_EVENT]: isValidValue,
	[INPUT_EVENT]: isValidValue,
	[CHANGE_EVENT]: isValidValue,
};

export type SliderEmits = typeof sliderEmits;
