import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const dialogFooterProps = buildProps({
	/**
	 * @description footer layout
	 */
	layout: {
		type: definePropType<'default' | 'phone' | 'tablet'>(String),
		values: ['default', 'phone', 'tablet'],
		default: 'default',
	},
	/**
	 * @description footer variant
	 */
	type: {
		type: definePropType<'primary' | 'default' | 'info' | 'success' | 'waring' | 'danger'>(String),
		values: ['primary', 'default', 'info', 'success', 'waring', 'danger'],
		default: 'primary',
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
} as const);

export type DialogFooterProps = ExtractPropTypes<typeof dialogFooterProps>;

export const dialogFooterEmits = {
	leftClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
	rightClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type DialogFooterEmits = typeof dialogFooterEmits;
