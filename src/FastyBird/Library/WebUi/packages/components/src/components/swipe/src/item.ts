import { buildProps, definePropType, isBoolean, isString } from '@fastybird/web-ui-utils';

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
	['update:revealed']: (value: SwipeActionsOutDir | boolean) => isString(value) || isBoolean(value),
	active: (value: boolean) => isBoolean(value),
	closed: () => true,
	revealed: (value: { side: SwipeActionsOutDir; close: () => void }) => true,
	leftRevealed: (value: { close: () => void }) => true,
	rightRevealed: (value: { close: () => void }) => true,
};

export type SwipeItemEmits = typeof swipeItemEmits;
