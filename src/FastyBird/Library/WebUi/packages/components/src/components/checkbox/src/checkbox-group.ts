import { buildProps, definePropType, isArray } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';
import type { CheckboxValueType } from './checkbox';

export type CheckboxGroupValueType = Exclude<CheckboxValueType, boolean>[];

export const checkboxGroupProps = buildProps({
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<CheckboxGroupValueType>(Array),
		default: () => [],
	},
	/**
	 * @description native `id` attribute
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description native `name` attribute
	 */
	name: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether the nesting checkboxes are disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description minimum number of checkbox checked
	 */
	min: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description maximum number of checkbox checked
	 */
	max: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description size of checkbox
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description label for screen reader
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description border and background color when button is active
	 */
	fill: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description font color when button is active
	 */
	textColor: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description element tag of the checkbox group
	 */
	tag: {
		type: definePropType<string>(String),
		default: 'div',
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type CheckboxGroupProps = ExtractPropTypes<typeof checkboxGroupProps>;

export const checkboxGroupEmits = {
	[UPDATE_MODEL_EVENT]: (val: CheckboxGroupValueType) => isArray(val),
	change: (val: CheckboxValueType[]) => isArray(val),
};

export type CheckboxGroupEmits = typeof checkboxGroupEmits;
