import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const appBarProps = buildProps({
	/**
	 * @description
	 */
	menuButtonHidden: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	menuCollapsed: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
});

export type AppBarProps = ExtractPropTypes<typeof appBarProps>;

export const appBarEmits = {
	toggleMenu: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type AppBarEmits = typeof appBarEmits;
