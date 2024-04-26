import { EffectTypes } from '@fastybird/web-ui-constants';
import { buildProps, definePropType, isObject, isString, mutable } from '@fastybird/web-ui-utils';

import type { Component, ExtractPropTypes } from 'vue';
import type { NavigationFailure } from 'vue-router';
import type { MenuItemClicked } from './types';

export enum MenuModeTypes {
	HORIZONTAL = 'horizontal',
	VERTICAL = 'vertical',
}

export const modeTypes = [MenuModeTypes.HORIZONTAL, MenuModeTypes.VERTICAL] as const;

export type ModeType = MenuModeTypes.HORIZONTAL | MenuModeTypes.VERTICAL;

export enum MenuTriggerTypes {
	HOVER = 'hover',
	CLICK = 'click',
}

export const triggerTypes = [MenuTriggerTypes.HOVER, MenuTriggerTypes.CLICK] as const;

export type TriggerType = MenuTriggerTypes.HOVER | MenuTriggerTypes.CLICK;

export const menuProps = buildProps({
	/**
	 * @description
	 */
	mode: {
		type: definePropType<ModeType>(String),
		values: modeTypes,
		default: MenuModeTypes.VERTICAL,
	},
	/**
	 * @description
	 */
	defaultActive: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	defaultOpeneds: {
		type: definePropType<string[]>(Array),
		default: () => mutable([] as const),
	},
	/**
	 * @description
	 */
	uniqueOpened: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	router: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	menuTrigger: {
		type: definePropType<TriggerType>(String),
		values: triggerTypes,
		default: MenuTriggerTypes.HOVER,
	},
	/**
	 * @description
	 */
	collapse: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	backgroundColor: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	textColor: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	activeTextColor: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	closeOnClickOutside: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	collapseTransition: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description
	 */
	ellipsis: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description
	 */
	popperOffset: {
		type: definePropType<number>(Number),
		default: 6,
	},
	/**
	 * @description
	 */
	ellipsisIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description
	 */
	popperEffect: {
		type: definePropType<EffectTypes.DARK | EffectTypes.LIGHT>(String),
		values: [EffectTypes.DARK, EffectTypes.LIGHT],
		default: EffectTypes.DARK,
	},
	/**
	 * @description
	 */
	popperClass: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	showTimeout: {
		type: definePropType<number>(Number),
		default: 300,
	},
	/**
	 * @description
	 */
	hideTimeout: {
		type: definePropType<number>(Number),
		default: 300,
	},
} as const);

export type MenuProps = ExtractPropTypes<typeof menuProps>;

export const menuEmits = {
	close: (index: string, indexPath: string[]): boolean => isString(index) && checkIndexPath(indexPath),
	open: (index: string, indexPath: string[]): boolean => isString(index) && checkIndexPath(indexPath),
	select: (index: string, indexPath: string[], item: MenuItemClicked, routerResult?: Promise<void | NavigationFailure>): boolean =>
		isString(index) && checkIndexPath(indexPath) && isObject(item) && (routerResult === undefined || routerResult instanceof Promise),
};

export type MenuEmits = typeof menuEmits;

const checkIndexPath = (indexPath: unknown): indexPath is string[] => Array.isArray(indexPath) && indexPath.every((path) => isString(path));
