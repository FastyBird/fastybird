import { type Component, ExtractPropTypes } from 'vue';

import { placements } from '@popperjs/core';
import { CHANGE_EVENT, ComponentSize, ComponentSizeTypes, Effect, EffectTypes, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { buildProps, definePropType, isArray, isBoolean, isNumber, isObject, isString, isUndefined } from '@fastybird/web-ui-utils';

import { useTooltipContentProps } from '../../tooltip';
import { tagProps } from '../../tag';

import type { Options, Placement } from '../../popper';

export const selectProps = buildProps({
	/**
	 * @description the name attribute of select input
	 */
	name: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description native input id
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<string | number | boolean | object | (string | number | boolean | object)[] | undefined>([
			Array,
			String,
			Number,
			Boolean,
			Object,
		]),
		default: undefined,
	},
	/**
	 * @description the autocomplete attribute of select input
	 */
	autocomplete: {
		type: definePropType<'on' | 'off'>(String),
		default: 'off',
	},
	/**
	 * @description for non-filterable Select, this prop decides if the option menu pops up when the input is focused
	 */
	automaticDropdown: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description size of Input
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description tooltip theme, built-in theme: `dark` | `light`
	 */
	effect: {
		type: definePropType<Effect>(String),
		default: EffectTypes.LIGHT,
	},
	/**
	 * @description whether Select is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether select can be cleared
	 */
	clearable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether Select is filterable
	 */
	filterable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether creating new items is allowed. To use this, `filterable` must be true
	 */
	allowCreate: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether Select is loading data from server
	 */
	loading: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description custom class name for Select's dropdown
	 */
	popperClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description [popper.js](https://popper.js.org/docs/v2/) parameters
	 */
	popperOptions: {
		type: definePropType<Partial<Options>>(Object),
		default: () => ({}) as Partial<Options>,
	},
	/**
	 * @description whether options are loaded from server
	 */
	remote: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description displayed text while loading data from server, default is 'Loading'
	 */
	loadingText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description displayed text when no data matches the filtering query, you can also use slot `empty`, default is 'No matching data'
	 */
	noMatchText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description displayed text when there is no options, you can also use slot `empty`, default is 'No data'
	 */
	noDataText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description custom remote search method
	 */
	remoteMethod: Function,
	/**
	 * @description custom filter method
	 */
	filterMethod: Function,
	/**
	 * @description whether multiple-select is activated
	 */
	multiple: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description maximum number of options user can select when `multiple` is `true`. No limit when set to 0
	 */
	multipleLimit: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description placeholder, default is 'Select'
	 */
	placeholder: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description select first matching option on enter key. Use with `filterable` or `remote`
	 */
	defaultFirstOption: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description when `multiple` and `filter` is true, whether to reserve current keyword after selecting an option
	 */
	reserveKeyword: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description unique identity key name for value, required when value is an object
	 */
	valueKey: {
		type: definePropType<string>(String),
		default: 'value',
	},
	/**
	 * @description whether to collapse tags to a text when multiple selecting
	 */
	collapseTags: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether show all selected tags when mouse hover text of collapse-tags. To use this, `collapse-tags` must be true
	 */
	collapseTagsTooltip: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description the max tags number to be shown. To use this, `collapse-tags` must be true
	 */
	maxCollapseTags: {
		type: definePropType<number>(Number),
		default: 1,
	},
	/**
	 * @description whether select dropdown is teleported to the body
	 */
	teleported: useTooltipContentProps.teleported,
	/**
	 * @description when select dropdown is inactive and `persistent` is `false`, select dropdown will be destroyed
	 */
	persistent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description custom clear icon component
	 */
	clearIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description whether the width of the dropdown is the same as the input
	 */
	fitInputWidth: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description custom suffix icon component
	 */
	suffixIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description tag type
	 */
	tagVariant: { ...tagProps.variant, default: 'primary' },
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description in remote search method show suffix icon
	 */
	remoteShowSuffix: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description position of dropdown
	 */
	placement: {
		type: definePropType<Placement>(String),
		values: placements,
		default: 'bottom-start',
	},
	/**
	 * @description list of possible positions for dropdown
	 */
	fallbackPlacements: {
		type: definePropType<Placement[]>(Array),
		default: ['bottom-start', 'top-start', 'right', 'left'],
	},
	/**
	 * @description native input aria-label
	 */
	ariaLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const);

export type SelectProps = ExtractPropTypes<typeof selectProps>;

export const selectEmits = {
	[UPDATE_MODEL_EVENT]: (val: string | number | boolean | object | (string | number | boolean | object)[] | undefined): boolean =>
		isString(val) || isNumber(val) || isBoolean(val) || isObject(val) || isArray(val) || isUndefined(val),
	[CHANGE_EVENT]: (val: string | number | boolean | object | (string | number | boolean | object)[] | undefined): boolean =>
		isString(val) || isNumber(val) || isBoolean(val) || isObject(val) || isArray(val) || isUndefined(val),
	['visible-change']: (val: boolean): boolean => isBoolean(val),
	['remove-tag']: (val: string | number | boolean | object): boolean => isString(val) || isNumber(val) || isBoolean(val) || isObject(val),
	clear: (): boolean => true,
	focus: (evt: FocusEvent): boolean => evt instanceof FocusEvent,
	blur: (evt: FocusEvent): boolean => evt instanceof FocusEvent,
};

export type SelectEmits = typeof selectEmits;

export interface SelectOption {
	value: string | number | boolean | object | (string | number | boolean | object)[] | undefined;
	currentLabel: string;
	isDisabled?: boolean;
}
