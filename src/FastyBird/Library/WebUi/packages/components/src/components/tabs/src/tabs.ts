import { buildProps, definePropType, isNumber, isString } from '@fastybird/web-ui-utils';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { tabNavTypes } from './tab-nav';

import type { ExtractPropTypes } from 'vue';
import type { Awaitable } from '@fastybird/web-ui-utils';
import type { TabsPaneContext } from './constants';
import type { TabNavType } from './tab-nav';

export enum TabPositionTypes {
	TOP = 'top',
	RIGHT = 'right',
	BOTTOM = 'bottom',
	LEFT = 'left',
}

export const tabPositions = [TabPositionTypes.TOP, TabPositionTypes.RIGHT, TabPositionTypes.BOTTOM, TabPositionTypes.LEFT] as const;

export type TabPosition = TabPositionTypes.TOP | TabPositionTypes.RIGHT | TabPositionTypes.BOTTOM | TabPositionTypes.LEFT;

export type TabPaneName = string | number;

export const tabsProps = buildProps({
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
	closable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	addable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	modelValue: {
		type: definePropType<string | number | undefined>([String, Number]),
		default: undefined,
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
	tabPosition: {
		type: definePropType<TabPosition>(String),
		values: tabPositions,
		default: TabPositionTypes.TOP,
	},
	/**
	 * @description
	 */
	beforeLeave: {
		type: definePropType<(newName: TabPaneName, oldName: TabPaneName) => Awaitable<void | boolean>>(Function),
		default: () => true,
	},
	/**
	 * @description
	 */
	stretch: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type TabsProps = ExtractPropTypes<typeof tabsProps>;

export const tabsEmits = {
	[UPDATE_MODEL_EVENT]: (name: TabPaneName) => isPaneName(name),
	tabClick: (pane: TabsPaneContext, ev: Event) => ev instanceof Event,
	tabChange: (name: TabPaneName) => isPaneName(name),
	edit: (paneName: TabPaneName | undefined, action: 'remove' | 'add') => ['remove', 'add'].includes(action),
	tabRemove: (name: TabPaneName) => isPaneName(name),
	tabAdd: () => true,
};

export type TabsEmits = typeof tabsEmits;

export type TabsPanes = Record<number, TabsPaneContext>;

const isPaneName = (value: unknown): value is string | number => isString(value) || isNumber(value);
