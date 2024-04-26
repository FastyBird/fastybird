import { buildProps, definePropType, isBoolean, isNumber, isString } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, ComponentSizeTypes, INPUT_EVENT, UPDATE_MODEL_EVENT, Variant, VariantTypes } from '@fastybird/web-ui-constants';
import type { ComponentSize } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes } from 'vue';

export const switchProps = buildProps({
	/**
	 * @description binding value, it should be equivalent to either `active-value` or `inactive-value`, by default it's `boolean` type
	 */
	modelValue: {
		type: definePropType<number | string | boolean>([Number, String, Boolean]),
		default: false,
	},
	/**
	 * @description whether Switch is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether Switch is in loading state
	 */
	loading: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description size of Switch
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
	 * @description width of Switch
	 */
	width: {
		type: definePropType<number | string | undefined>([Number, String]),
		default: undefined,
	},
	/**
	 * @description whether icon or text is displayed inside dot, only the first character will be rendered for text
	 */
	inlinePrompt: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description component of the icon displayed in action when in `off` state
	 */
	inactiveActionIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description component of the icon displayed in action when in `on` state
	 */
	activeActionIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description component of the icon displayed when in `on` state, overrides `active-text`
	 */
	activeIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description component of the icon displayed when in `off` state, overrides `inactive-text`
	 */
	inactiveIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description text displayed when in `on` state
	 */
	activeText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description text displayed when in `off` state
	 */
	inactiveText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description switch value when in `on` state
	 */
	activeValue: {
		type: definePropType<number | string | boolean>([Number, String, Boolean]),
		default: true,
	},
	/**
	 * @description switch value when in `off` state
	 */
	inactiveValue: {
		type: definePropType<number | string | boolean>([Number, String, Boolean]),
		default: false,
	},
	/**
	 * @description input name of Switch
	 */
	name: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description before-change hook before the switch state changes. If `false` is returned or a `Promise` is returned and then is rejected, will stop switching
	 */
	beforeChange: {
		type: definePropType<() => Promise<boolean> | boolean>(Function),
	},
	/**
	 * @description id for input
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description tabindex for input
	 */
	tabindex: {
		type: definePropType<number | string | undefined>([Number, String]),
		default: undefined,
	},
	/**
	 * @description native input aria-label
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const);

export type SwitchProps = ExtractPropTypes<typeof switchProps>;

export const switchEmits = {
	[UPDATE_MODEL_EVENT]: (val: boolean | string | number): boolean => isBoolean(val) || isString(val) || isNumber(val),
	[CHANGE_EVENT]: (val: boolean | string | number): boolean => isBoolean(val) || isString(val) || isNumber(val),
	[INPUT_EVENT]: (val: boolean | string | number): boolean => isBoolean(val) || isString(val) || isNumber(val),
};

export type SwitchEmits = typeof switchEmits;
