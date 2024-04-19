import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const barProps = buildProps({
	always: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	minSize: {
		type: definePropType<number>(Number),
		required: true,
	},
} as const);

export type BarProps = ExtractPropTypes<typeof barProps>;
