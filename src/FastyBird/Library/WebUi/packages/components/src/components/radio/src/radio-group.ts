import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes } from '@fastybird/web-ui-constants';

import { radioEmits } from './radio';

import type { ExtractPropTypes } from 'vue';

export const radioGroupProps = buildProps({
	/**
	 * @description binding value
	 */
	modelValue: {
		type: [String, Number, Boolean],
		default: undefined,
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
	 * @description whether the nesting radios are disabled	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description the size of radio buttons or bordered radios
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description same as `aria-label` in RadioGroup
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

export type RadioGroupProps = ExtractPropTypes<typeof radioGroupProps>;

export const radioGroupEmits = radioEmits;

export type RadioGroupEmits = typeof radioGroupEmits;
