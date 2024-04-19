import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes } from 'vue';

export const drawerHeaderProps = buildProps({
	/**
	 * @description header variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description title of drawer. Can also be passed with a named slot
	 */
	title: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description subtitle of drawer. Can also be passed with a named slot
	 */
	subtitle: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description icon of drawer. Can also be passed with a named slot
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description custom close icon, default is Xmark
	 */
	closeIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description whether drawer window is closable with close button
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description header's aria-level attribute
	 */
	ariaLevel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const);

export type DrawerHeaderProps = ExtractPropTypes<typeof drawerHeaderProps>;

export const drawerHeaderEmits = {
	close: (evt: UIEvent) => evt instanceof UIEvent,
};

export type DrawerHeaderEmits = typeof drawerHeaderEmits;
