import { buildProps, definePropType, isBoolean, isNumber, isString } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, ComponentSize, ComponentSizeTypes, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const radioPropsBase = buildProps({
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<number | string | boolean | undefined>([Number, String, Boolean]),
		default: undefined,
	},
	/**
	 * @description the label of Radio
	 */
	label: {
		type: definePropType<number | string | boolean | undefined>([Number, String, Boolean]),
		default: undefined,
	},
	/**
	 * @description the value of Radio
	 */
	value: {
		type: definePropType<number | string | boolean | undefined>([Number, String, Boolean]),
		default: undefined,
	},
	/**
	 * @description whether Radio is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description native `name` attribute
	 */
	name: {
		type: definePropType<string | undefined>(String),
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
	 * @description size of the Radio
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
});

export const radioProps = buildProps({
	...radioPropsBase,
	/**
	 * @description whether to add a border around Radio
	 */
	border: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type RadioProps = ExtractPropTypes<typeof radioProps>;

export const radioEmits = {
	[UPDATE_MODEL_EVENT]: (val: string | number | boolean | undefined): boolean => isString(val) || isNumber(val) || isBoolean(val),
	[CHANGE_EVENT]: (val: string | number | boolean | undefined): boolean => isString(val) || isNumber(val) || isBoolean(val),
};

export type RadioEmits = typeof radioEmits;
