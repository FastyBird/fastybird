import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const thumbProps = buildProps({
	vertical: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	size: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	move: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
	ratio: {
		type: definePropType<number>(Number),
		required: true,
	},
	always: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type ThumbProps = ExtractPropTypes<typeof thumbProps>;
