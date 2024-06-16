import { buildProps, definePropType, isBoolean, isObject } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export type SwipeActionsOutDir = 'left' | 'right';

export const swipeProps = buildProps({
	items: {
		type: definePropType<any[]>(Array),
		required: true,
	},
	threshold: {
		type: definePropType<number>(Number),
		default: 45,
	},
	revealed: {
		type: definePropType<{ [key: number]: SwipeActionsOutDir }>(Object),
		default: () => {
			return {};
		},
	},
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	itemDisabled: {
		type: definePropType<(item: any) => boolean>(Function),
		default: () => false,
	},
} as const);

export type SwipeProps = ExtractPropTypes<typeof swipeProps>;

export const swipeEmits = {
	['update:revealed']: (value: { [key: number]: SwipeActionsOutDir }): boolean => isObject(value),
	active: (value: boolean): boolean => isBoolean(value),
	closed: (value: { index: number; item: any }): boolean => isObject(value),
	revealed: (value: { index: number; item: any; side: SwipeActionsOutDir; close: () => void }): boolean => isObject(value),
	leftRevealed: (value: { index: number; item: any; close: () => void }): boolean => isObject(value),
	rightRevealed: (value: { index: number; item: any; close: () => void }): boolean => isObject(value),
};

export type SwipeEmits = typeof swipeEmits;
