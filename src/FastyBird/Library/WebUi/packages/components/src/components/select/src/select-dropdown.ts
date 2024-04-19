import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const selectDropdownProps = buildProps({
	/**
	 * @description custom class name
	 */
	popperClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description whether multiple-select is activated
	 */
	multiple: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether the width of the dropdown is the same as the input
	 */
	fitInputWidth: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type SelectDropdownProps = ExtractPropTypes<typeof selectDropdownProps>;
