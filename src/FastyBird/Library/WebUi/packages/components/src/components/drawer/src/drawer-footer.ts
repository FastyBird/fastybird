import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const drawerFooterProps = buildProps({
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
	leftBtnLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
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
	rightBtnLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const);

export type DrawerFooterProps = ExtractPropTypes<typeof drawerFooterProps>;

export const drawerFooterEmits = {
	leftClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
	rightClick: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type DrawerFooterEmits = typeof drawerFooterEmits;
