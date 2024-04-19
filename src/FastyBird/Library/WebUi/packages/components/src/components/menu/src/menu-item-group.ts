import { definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const menuItemGroupProps = {
	title: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
} as const;

export type MenuItemGroupProps = ExtractPropTypes<typeof menuItemGroupProps>;
