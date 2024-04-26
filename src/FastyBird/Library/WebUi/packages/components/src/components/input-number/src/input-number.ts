import { isNil } from 'lodash';

import { buildProps, definePropType, isNumber } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, ComponentSize, ComponentSizeTypes, INPUT_EVENT, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export enum InputNumberControlPositionTypes {
	LEFT = 'left',
	RIGHT = 'right',
}

export const inputNumberControlPositions = [InputNumberControlPositionTypes.LEFT, InputNumberControlPositionTypes.RIGHT] as const;

export type InputNumberControlPosition = InputNumberControlPositionTypes.LEFT | InputNumberControlPositionTypes.RIGHT;

export const inputNumberProps = buildProps({
	/**
	 * @description same as `id` in native input
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description incremental step
	 */
	step: {
		type: definePropType<number>(Number),
		default: 1,
	},
	/**
	 * @description whether input value can only be multiple of step
	 */
	stepStrictly: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description the maximum allowed value
	 */
	max: {
		type: definePropType<number>(Number),
		default: Number.POSITIVE_INFINITY,
	},
	/**
	 * @description the minimum allowed value
	 */
	min: {
		type: definePropType<number>(Number),
		default: Number.NEGATIVE_INFINITY,
	},
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description same as `readonly` in native input
	 */
	readonly: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether the component is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description size of the component
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
	},
	/**
	 * @description whether to enable the control buttons
	 */
	controls: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description position of the control buttons
	 */
	controlsPosition: {
		type: definePropType<InputNumberControlPosition>(String),
		values: inputNumberControlPositions,
		default: InputNumberControlPositionTypes.LEFT,
	},
	/**
	 * @description value should be set when input box is cleared
	 */
	valueOnClear: {
		type: [String, Number, null],
		validator: (val: 'min' | 'max' | number | null) => val === null || isNumber(val) || ['min', 'max'].includes(val),
		default: null,
	},
	/**
	 * @description same as `name` in native input
	 */
	name: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description same as `label` in native input
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description same as `placeholder` in native input
	 */
	placeholder: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description precision of input value
	 */
	precision: {
		type: definePropType<number | undefined>(Number),
		validator: (val: number) => val >= 0 && val === Number.parseInt(`${val}`, 10),
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type InputNumberProps = ExtractPropTypes<typeof inputNumberProps>;

export const inputNumberEmits = {
	[CHANGE_EVENT]: (cur: number | undefined, prev: number | undefined): boolean => prev !== cur,
	blur: (e: FocusEvent): boolean => e instanceof FocusEvent,
	focus: (e: FocusEvent): boolean => e instanceof FocusEvent,
	[INPUT_EVENT]: (val: number | null | undefined): boolean => isNumber(val) || isNil(val),
	[UPDATE_MODEL_EVENT]: (val: number | undefined): boolean => isNumber(val) || isNil(val),
};

export type InputNumberEmits = typeof inputNumberEmits;
