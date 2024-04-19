import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Layout, LayoutTypes, Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes } from 'vue';

export const modalHeaderProps = buildProps({
	/**
	 * @description header layout
	 */
	layout: {
		type: definePropType<Layout>(String),
		values: [LayoutTypes.DEFAULT, LayoutTypes.PHONE, LayoutTypes.TABLET],
		default: LayoutTypes.DEFAULT,
	},
	/**
	 * @description header variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description title of modal. Can also be passed with a named slot
	 */
	title: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description subtitle of modal. Can also be passed with a named slot
	 */
	subtitle: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description icon of modal. Can also be passed with a named slot
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
	 * @description whether modal window is closable with close button
	 */
	closable: Boolean,
	/**
	 * @description header's aria-level attribute
	 */
	ariaLevel: String,
} as const);

export type ModalHeaderProps = ExtractPropTypes<typeof modalHeaderProps>;

export const modalHeaderEmits = {
	close: (evt: UIEvent) => evt instanceof UIEvent,
	leftClick: (evt: UIEvent) => evt instanceof UIEvent,
	rightClick: (evt: UIEvent) => evt instanceof UIEvent,
};

export type ModalHeaderEmits = typeof modalHeaderEmits;
