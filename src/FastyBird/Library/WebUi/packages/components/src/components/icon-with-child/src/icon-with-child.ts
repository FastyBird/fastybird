import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const iconWithChildProps = buildProps({
	/**
	 * @description child icon variant
	 */
	type: {
		type: definePropType<'primary' | 'default' | 'info' | 'success' | 'waring' | 'danger'>(String),
		values: ['primary', 'default', 'info', 'success', 'waring', 'danger'],
		default: 'primary',
	},
	/**
	 * @description main icon size
	 */
	size: {
		type: definePropType<number | string>([Number, String]),
		default: 20,
	},
	/**
	 * @description main icon color
	 */
	color: String,
} as const);

export type IconWithChildProps = ExtractPropTypes<typeof iconWithChildProps>;
