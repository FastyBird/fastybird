import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const tabPaneProps = buildProps({
	/**
	 * @description
	 */
	label: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	name: {
		type: definePropType<string | number | undefined>([String, Number]),
		default: undefined,
	},
	/**
	 * @description
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	lazy: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type TabPaneProps = ExtractPropTypes<typeof tabPaneProps>;
