import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { Component, ExtractPropTypes } from 'vue';

export const dialogHeaderProps = buildProps({
	/**
	 * @description header layout
	 */
	layout: {
		type: definePropType<'default' | 'phone' | 'tablet'>(String),
		values: ['default', 'phone', 'tablet'],
		default: 'default',
	},
	/**
	 * @description header variant
	 */
	type: {
		type: definePropType<'primary' | 'default' | 'info' | 'success' | 'waring' | 'danger'>(String),
		values: ['primary', 'default', 'info', 'success', 'waring', 'danger'],
		default: 'primary',
	},
	/**
	 * @description title of dialog. Can also be passed with a named slot
	 */
	title: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description subtitle of dialog. Can also be passed with a named slot
	 */
	subtitle: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description icon of dialog. Can also be passed with a named slot
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description whether to show left action button
	 */
	showLeftBtn: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description left action button custom label
	 */
	leftBtnLabel: String,
	/**
	 * @description whether to show right action button
	 */
	showRightBtn: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description right action button custom label
	 */
	rightBtnLabel: String,
	/**
	 * @description whether to show a close button
	 */
	showClose: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description custom close icon, default is Xmark
	 */
	closeIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description header's aria-level attribute
	 */
	ariaLevel: {
		type: definePropType<string>(String),
		default: undefined,
	},
} as const);

export type DialogHeaderProps = ExtractPropTypes<typeof dialogHeaderProps>;

export const dialogHeaderEmits = {
	close: (evt: UIEvent): boolean => evt instanceof UIEvent,
	leftClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
	rightClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type DialogHeaderEmits = typeof dialogHeaderEmits;
