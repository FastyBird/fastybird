import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const buttonProps = buildProps({
	/**
	 * @description
	 */
	teleport: {
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

export type ButtonProps = ExtractPropTypes<typeof buttonProps>;
