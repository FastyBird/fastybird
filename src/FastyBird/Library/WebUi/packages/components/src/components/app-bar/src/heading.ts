import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { Component, ExtractPropTypes } from 'vue';

export enum AppBarHeadingAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	CENTER = 'center',
}

export const appBarHeadingAlignTypes = [AppBarHeadingAlignTypes.LEFT, AppBarHeadingAlignTypes.RIGHT, AppBarHeadingAlignTypes.CENTER] as const;

export type AppBarHeadingAlign = AppBarHeadingAlignTypes.LEFT | AppBarHeadingAlignTypes.RIGHT | AppBarHeadingAlignTypes.CENTER;

export const appBarHeadingProps = buildProps({
	/**
	 * @description
	 */
	align: {
		type: definePropType<AppBarHeadingAlign>(String),
		values: appBarHeadingAlignTypes,
		default: AppBarHeadingAlignTypes.LEFT,
	},
	/**
	 * @description icon of dialog. Can also be passed with a named slot
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type AppBarHeadingProps = ExtractPropTypes<typeof appBarHeadingProps>;
