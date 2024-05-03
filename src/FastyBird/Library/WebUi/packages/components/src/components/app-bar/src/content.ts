import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const appBarContentProps = buildProps({
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type AppBarContentProps = ExtractPropTypes<typeof appBarContentProps>;
