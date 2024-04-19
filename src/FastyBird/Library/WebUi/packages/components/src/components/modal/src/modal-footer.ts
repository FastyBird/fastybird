import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Layout, LayoutTypes, Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const modalFooterProps = buildProps({
	/**
	 * @description footer layout
	 */
	layout: {
		type: definePropType<Layout>(String),
		values: [LayoutTypes.DEFAULT, LayoutTypes.PHONE, LayoutTypes.TABLET],
		default: LayoutTypes.DEFAULT,
	},
	/**
	 * @description footer variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
		default: VariantTypes.PRIMARY,
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

export type ModalFooterProps = ExtractPropTypes<typeof modalFooterProps>;

export const modalFooterEmits = {
	leftClick: (evt: UIEvent) => evt instanceof UIEvent,
	rightClick: (evt: UIEvent) => evt instanceof UIEvent,
};

export type ModalFooterEmits = typeof modalFooterEmits;
