import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const appBarButtonProps = buildProps({
	/**
	 * @description
	 */
	small: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	left: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	right: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
});

export type AppBarButtonProps = ExtractPropTypes<typeof appBarButtonProps>;

export const appBarButtonEmits = {
	click: (evt: UIEvent) => evt instanceof UIEvent,
};

export type AppBarButtonEmits = typeof appBarButtonEmits;
