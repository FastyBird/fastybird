import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export enum AppBarIconAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	NONE = 'none',
}

export const appBarIconAlignTypes = [AppBarIconAlignTypes.LEFT, AppBarIconAlignTypes.RIGHT, AppBarIconAlignTypes.NONE] as const;

export type AppBarIconAlign = AppBarIconAlignTypes.LEFT | AppBarIconAlignTypes.RIGHT | AppBarIconAlignTypes.NONE;

export const appBarIconProps = buildProps({
	/**
	 * @description
	 */
	align: {
		type: definePropType<AppBarIconAlign>(String),
		values: appBarIconAlignTypes,
		default: AppBarIconAlignTypes.NONE,
	},
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type AppBarIconProps = ExtractPropTypes<typeof appBarIconProps>;
