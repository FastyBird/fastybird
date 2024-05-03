import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import { buttonEmits, buttonProps } from '../../button';

import type { ExtractPropTypes } from 'vue';

export enum AppBarButtonAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	NONE = 'none',
}

export const appBarButtonAlignTypes = [AppBarButtonAlignTypes.LEFT, AppBarButtonAlignTypes.RIGHT, AppBarButtonAlignTypes.NONE] as const;

export type AppBarButtonAlign = AppBarButtonAlignTypes.LEFT | AppBarButtonAlignTypes.RIGHT | AppBarButtonAlignTypes.NONE;

export const appBarButtonProps = buildProps({
	...buttonProps,
	/**
	 * @description
	 */
	align: {
		type: definePropType<AppBarButtonAlign>(String),
		values: appBarButtonAlignTypes,
		default: AppBarButtonAlignTypes.NONE,
	},
	/**
	 * @description
	 */
	small: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	left: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	right: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type AppBarButtonProps = ExtractPropTypes<typeof appBarButtonProps>;

export const appBarButtonEmits = {
	...buttonEmits,
};

export type AppBarButtonEmits = typeof appBarButtonEmits;
