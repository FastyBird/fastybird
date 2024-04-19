import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const iconProps = buildProps({
	/**
	 * @description SVG icon size, size x size
	 */
	size: {
		type: definePropType<number | string>([Number, String]),
	},
	/**
	 * @description SVG tag's fill attribute
	 */
	color: String,
} as const);

export type IconProps = ExtractPropTypes<typeof iconProps>;
