import { buildProps, definePropType, isBoolean, isObject, isString } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';
import type { SwipeActionsOutDir } from './swipe';

export const swipeItemProps = buildProps({
	threshold: {
		type: definePropType<number>(Number),
		default: 45,
	},
	revealed: {
		type: definePropType<boolean | SwipeActionsOutDir>([String, Boolean]),
		default: false,
	},
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type SwipeItemProps = ExtractPropTypes<typeof swipeItemProps>;

export const swipeItemEmits = {
	['update:revealed']: (value: SwipeActionsOutDir | boolean): boolean => isString(value) || isBoolean(value),
	active: (value: boolean): boolean => isBoolean(value),
	closed: (): boolean => true,
	revealed: (value: { side: SwipeActionsOutDir; close: () => void }): boolean => isObject(value),
	leftRevealed: (value: { close: () => void }): boolean => isObject(value),
	rightRevealed: (value: { close: () => void }): boolean => isObject(value),
};

export type SwipeItemEmits = typeof swipeItemEmits;
