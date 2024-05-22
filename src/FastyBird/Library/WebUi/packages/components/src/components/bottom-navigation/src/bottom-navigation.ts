import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const bottomNavigationProps = buildProps({
	/**
	 * @description bottomNavigation variant
	 */
	type: {
		type: definePropType<'primary' | 'info' | 'success' | 'waring' | 'danger'>(String),
		values: ['primary', 'info', 'success', 'waring', 'danger'],
		default: 'primary',
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
