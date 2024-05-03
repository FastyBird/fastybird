import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';
import type { TabsPaneContext } from './constants';

export const tabNavItemProps = buildProps({
	/**
	 * @description
	 */
	index: {
		type: definePropType<number>(Number),
		required: true,
	},
	/**
	 * @description
	 */
	pane: {
		type: definePropType<TabsPaneContext>(Object),
		required: true,
	},
	/**
	 * @description
	 */
	isFocus: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	editable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type TabNavItemProps = ExtractPropTypes<typeof tabNavItemProps>;

export const tabNavItemEmits = {
	click: (ev: Event): boolean => ev instanceof Event,
	remove: (ev: Event): boolean => ev instanceof Event,
	focus: (): boolean => true,
	blur: (): boolean => true,
};

export type TabNavItemEmits = typeof tabNavItemEmits;
