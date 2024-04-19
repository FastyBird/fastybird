import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const appBarIconProps = buildProps({
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

export type AppBarIconProps = ExtractPropTypes<typeof appBarIconProps>;
