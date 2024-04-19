import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const headingProps = buildProps({
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type HeadingProps = ExtractPropTypes<typeof headingProps>;
