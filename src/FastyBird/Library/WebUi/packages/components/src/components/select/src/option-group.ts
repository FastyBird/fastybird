import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const optionGroupProps = buildProps({
	/**
	 * @description name of the group
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether to disable all options in this group
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type OptionGroupProps = ExtractPropTypes<typeof optionGroupProps>;
