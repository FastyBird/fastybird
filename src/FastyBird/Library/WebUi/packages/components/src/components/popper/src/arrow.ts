import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';
import type Arrow from './arrow.vue';

export const popperArrowProps = buildProps({
	/**
	 * @description
	 */
	arrowOffset: {
		type: definePropType<number>(Number),
		default: 5,
	},
} as const);

export type PopperArrowProps = ExtractPropTypes<typeof popperArrowProps>;

export type PopperArrowInstance = InstanceType<typeof Arrow>;
