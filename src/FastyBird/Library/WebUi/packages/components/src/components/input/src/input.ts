import { isString } from '@vue/shared';
import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes, StyleValue } from 'vue';

export type InputAutoSize = { minRows?: number; maxRows?: number } | boolean;

export const inputProps = buildProps({
	/**
	 * @description binding value
	 */
	modelValue: {
		type: definePropType<number | string | null | undefined>([Number, String, Object]),
		default: '',
	},
	/**
	 * @description native input id
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description input box size
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
	},
	/**
	 * @description whether to disable
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description same as `maxlength` in native input
	 */
	maxlength: {
		type: definePropType<string | number | undefined>([String, Number]),
	},
	/**
	 * @description same as `minlength` in native input
	 */
	minlength: {
		type: definePropType<string | number | undefined>([String, Number]),
	},
	/**
	 * @description type of input
	 */
	type: {
		type: definePropType<string>(String),
		default: 'text',
	},
	/**
	 * @description control the resizability
	 */
	resize: {
		type: definePropType<string>(String),
		values: ['none', 'both', 'horizontal', 'vertical'],
	},
	/**
	 * @description whether textarea has an adaptive height
	 */
	autosize: {
		type: definePropType<InputAutoSize>([Boolean, Object]),
		default: false,
	},
	/**
	 * @description native input autocomplete
	 */
	autocomplete: {
		type: definePropType<string>(String),
		default: 'off',
	},
	/**
	 * @description format content
	 */
	formatter: {
		type: Function,
	},
	/**
	 * @description parse content
	 */
	parser: {
		type: Function,
	},
	/**
	 * @description placeholder
	 */
	placeholder: {
		type: definePropType<string | undefined>(String),
	},
	/**
	 * @description native input form
	 */
	form: {
		type: definePropType<string | undefined>(String),
	},
	/**
	 * @description native input readonly
	 */
	readonly: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description native input readonly
	 */
	clearable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description toggleable password input
	 */
	showPassword: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description word count
	 */
	showWordLimit: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description suffix icon
	 */
	suffixIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description prefix icon
	 */
	prefixIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description container role, internal properties provided for use by the picker component
	 */
	containerRole: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description native input aria-label
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description input tabindex
	 */
	tabindex: {
		type: definePropType<string | number>([String, Number]),
		default: 0,
	},
	/**
	 * @description whether to trigger form validation
	 */
	validateEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description input or textarea element style
	 */
	inputStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description native input autofocus
	 */
	autofocus: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type InputProps = ExtractPropTypes<typeof inputProps>;

export const inputEmits = {
	[UPDATE_MODEL_EVENT]: (value: string): boolean => isString(value),
	input: (value: string): boolean => isString(value),
	change: (value: string): boolean => isString(value),
	focus: (evt: FocusEvent): boolean => evt instanceof FocusEvent,
	blur: (evt: FocusEvent): boolean => evt instanceof FocusEvent,
	clear: (): boolean => true,
	mouseleave: (evt: MouseEvent): boolean => evt instanceof MouseEvent,
	mouseenter: (evt: MouseEvent): boolean => evt instanceof MouseEvent,
	keydown: (evt: KeyboardEvent | Event): boolean => evt instanceof Event,
	compositionstart: (evt: CompositionEvent): boolean => evt instanceof CompositionEvent,
	compositionupdate: (evt: CompositionEvent): boolean => evt instanceof CompositionEvent,
	compositionend: (evt: CompositionEvent): boolean => evt instanceof CompositionEvent,
};

export type InputEmits = typeof inputEmits;
