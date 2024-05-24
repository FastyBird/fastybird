import { useSizeProp } from 'element-plus';
import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const spinnerProps = buildProps({
	/**
	 * @description spinner variant
	 */
	type: {
		type: definePropType<'primary' | 'default' | 'info' | 'success' | 'waring' | 'danger' | undefined>(String),
		values: ['primary', 'default', 'info', 'success', 'waring', 'danger'],
		default: undefined,
	},
	/**
	 * @description spinner size
	 */
	size: useSizeProp,
	/**
	 * @description determine whether the spinner background color is always on
	 */
	bg: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
});

export type SpinnerProps = ExtractPropTypes<typeof spinnerProps>;
