import { buildProps, definePropType, isNumber, isObject, isString, mutable } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';
import type { TabsPaneContext } from './constants';
import type { TabPaneName } from './tabs';

export enum TabNavTypeTypes {
	CARD = 'card',
	BORDER_CARD = 'border-card',
}

export const tabNavTypes = [TabNavTypeTypes.CARD, TabNavTypeTypes.BORDER_CARD] as const;

export type TabNavType = TabNavTypeTypes.CARD | TabNavTypeTypes.BORDER_CARD;

export interface Scrollable {
	next?: boolean;
	prev?: number;
}

export const tabNavProps = buildProps({
	/**
	 * @description
	 */
	panes: {
		type: definePropType<TabsPaneContext[]>(Array),
		default: () => mutable([] as const),
	},
	/**
	 * @description
	 */
	currentName: {
		type: definePropType<string | number>([String, Number]),
		default: '',
	},
	/**
	 * @description
	 */
	editable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	type: {
		type: definePropType<TabNavType | undefined>(String),
		values: tabNavTypes,
		default: undefined,
	},
	/**
	 * @description
	 */
	stretch: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type TabNavProps = ExtractPropTypes<typeof tabNavProps>;

export const tabNavEmits = {
	tabClick: (tab: TabsPaneContext, tabName: TabPaneName, ev: Event): boolean =>
		ev instanceof Event && isObject(tab) && (isString(tabName) || isNumber(tabName)),
	tabRemove: (tab: TabsPaneContext, ev: Event): boolean => ev instanceof Event && isObject(tab),
};

export type TabNavEmits = typeof tabNavEmits;
