import { ComponentSize, ComponentSizeTypes, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { definePropType, isBoolean, isNumber, isString } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export type CheckboxValueType = string | number | boolean;

export const checkboxProps = {
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<number | string | boolean | undefined>([Number, String, Boolean]),
		default: undefined,
	},
	/**
	 * @description label of the Checkbox when used inside a `checkbox-group`
	 */
	label: {
		type: definePropType<number | string | boolean | object | undefined>([Number, String, Boolean, Object]),
		default: undefined,
	},
	/**
	 * @description value of the Checkbox when used inside a `checkbox-group`
	 */
	value: {
		type: definePropType<number | string | boolean | object | undefined>([Number, String, Boolean, Object]),
		default: undefined,
	},
	/**
	 * @description Set indeterminate state, only responsible for style control
	 */
	indeterminate: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether the Checkbox is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description if the Checkbox is checked
	 */
	checked: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description native 'name' attribute
	 */
	name: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description value of the Checkbox if it's checked
	 */
	trueValue: {
		type: definePropType<number | string | undefined>([Number, String]),
		default: undefined,
	},
	/**
	 * @description value of the Checkbox if it's not checked
	 */
	falseValue: {
		type: definePropType<number | string | undefined>([Number, String]),
		default: undefined,
	},
	/**
	 * @description input id
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description same as [aria-controls](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-controls), takes effect when `indeterminate` is `true`
	 */
	controls: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether to add a border around Checkbox
	 */
	border: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description size of the Checkbox
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description input tabindex
	 */
	tabindex: {
		type: definePropType<number | string | undefined>([Number, String]),
		default: undefined,
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
};

export type CheckboxProps = ExtractPropTypes<typeof checkboxProps>;

export const checkboxEmits = {
	[UPDATE_MODEL_EVENT]: (val: CheckboxValueType) => isString(val) || isNumber(val) || isBoolean(val),
	change: (val: CheckboxValueType) => isString(val) || isNumber(val) || isBoolean(val),
};

export type CheckboxEmits = typeof checkboxEmits;
