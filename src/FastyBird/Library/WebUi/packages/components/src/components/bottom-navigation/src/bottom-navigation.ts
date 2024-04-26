import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const bottomNavigationProps = buildProps({
	/**
	 * @description bottomNavigation variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description
	 */
	show: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	showClose: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description
	 */
	closeBtnText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const);

export type BottomNavigationProps = ExtractPropTypes<typeof bottomNavigationProps>;

export const bottomNavigationEmits = {
	close: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type BottomNavigationEmits = typeof bottomNavigationEmits;
