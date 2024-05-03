import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { Component, ExtractPropTypes } from 'vue';

export const subMenuProps = buildProps({
	/**
	 * @description
	 */
	index: {
		type: definePropType<string>(String),
		required: true,
	},
	/**
	 * @description
	 */
	showTimeout: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description
	 */
	hideTimeout: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description
	 */
	popperClass: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	teleported: {
		type: definePropType<boolean | undefined>(Boolean),
		default: undefined,
	},
	/**
	 * @description
	 */
	popperOffset: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	/**
	 * @description
	 */
	expandCloseIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description
	 */
	expandOpenIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description
	 */
	collapseCloseIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description
	 */
	collapseOpenIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
} as const);

export type SubMenuProps = ExtractPropTypes<typeof subMenuProps>;
