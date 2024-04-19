import { buildProps, definePropType, mutable } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';
import type { TabsPaneContext } from './constants';

export const tabBarProps = buildProps({
	/**
	 * @description
	 */
	tabs: {
		type: definePropType<TabsPaneContext[]>(Array),
		default: () => mutable([] as const),
	},
	/**
	 * @description
	 */
	activeItemRef: {
		type: definePropType<HTMLElement | undefined>(Object),
		default: undefined,
	},
} as const);

export type TabBarProps = ExtractPropTypes<typeof tabBarProps>;
