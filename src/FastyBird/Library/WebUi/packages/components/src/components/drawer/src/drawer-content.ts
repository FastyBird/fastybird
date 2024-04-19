import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import { drawerFooterEmits, drawerFooterProps } from './drawer-footer';
import { drawerHeaderEmits, drawerHeaderProps } from './drawer-header';

import type { ExtractPropTypes } from 'vue';

export const drawerContentProps = buildProps({
	...drawerHeaderProps,
	...drawerFooterProps,
	/**
	 * @description
	 */
	direction: {
		type: definePropType<string>(String),
		default: 'rtl',
		values: ['ltr', 'rtl', 'ttb', 'btt'],
	},
	/**
	 * @description
	 */
	size: {
		type: definePropType<string | number>([String, Number]),
		default: '30%',
	},
	/**
	 * @description
	 */
	visible: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether to show drawer header
	 */
	showHeader: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether to show drawer footer
	 */
	showFooter: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type DrawerContentProps = ExtractPropTypes<typeof drawerContentProps>;

export const drawerContentEmits = {
	...drawerHeaderEmits,
	...drawerFooterEmits,
};

export type DrawerContentEmits = typeof drawerContentEmits;
