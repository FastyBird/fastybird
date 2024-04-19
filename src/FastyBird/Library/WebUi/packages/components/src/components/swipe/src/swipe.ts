import { buildProps, definePropType, isBoolean, isNumber } from '@fastybird/web-ui-utils';

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
	['update:revealed']: (value: { [key: number]: SwipeActionsOutDir }) => isNumber(value),
	active: (value: boolean) => isBoolean(value),
	closed: (value: { index: number; item: any }) => true,
	revealed: (value: { index: number; item: any; side: SwipeActionsOutDir; close: () => void }) => true,
	leftRevealed: (value: { index: number; item: any; close: () => void }) => true,
	rightRevealed: (value: { index: number; item: any; close: () => void }) => true,
};

export type SwipeEmits = typeof swipeEmits;
