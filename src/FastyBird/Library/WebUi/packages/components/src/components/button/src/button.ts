import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { VariantTypes, ComponentSizeTypes, Variant, ComponentSize } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes } from 'vue';

export enum ButtonButtonTypes {
	BUTTON = 'button',
	SUBMIT = 'submit',
	RESET = 'reset',
}

export const buttonTypes = [ButtonButtonTypes.BUTTON, ButtonButtonTypes.SUBMIT, ButtonButtonTypes.RESET] as const;

export type ButtonType = ButtonButtonTypes.BUTTON | ButtonButtonTypes.SUBMIT | ButtonButtonTypes.RESET;

export const buttonProps = buildProps({
	/**
	 * @description button size
	 */
	size: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: ComponentSizeTypes.DEFAULT,
	},
	/**
	 * @description button variant
	 */
	variant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description same as native button's `type`
	 */
	type: {
		type: definePropType<ButtonType>(String),
		values: buttonTypes,
		default: ButtonButtonTypes.BUTTON,
	},
	/**
	 * @description whether button is stretched to full width
	 */
	block: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether button text is in uppercase
	 */
	uppercase: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's a link button
	 */
	link: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's a round button
	 */
	round: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's a circle button
	 */
	circle: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's a text button
	 */
	text: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's a plain button
	 */
	plain: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether the text button background color is always on
	 */
	bg: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description custom button color, automatically calculate `hover` and `active` color
	 */
	color: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether it's an active button
	 */
	active: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether button is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description same as native button's `autofocus`
	 */
	autofocus: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether it's loading
	 */
	loading: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description customize loading icon component
	 */
	loadingIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description icon component
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description custom element tag
	 */
	tag: {
		type: definePropType<string | Component>([String, Object]),
		default: 'button',
	},
	/**
	 * @description dark mode, which automatically converts `color` to dark mode colors
	 */
	dark: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type ButtonProps = ExtractPropTypes<typeof buttonProps>;

export const buttonEmits = {
	click: (evt: UIEvent) => evt instanceof UIEvent,
};

export type ButtonEmits = typeof buttonEmits;
