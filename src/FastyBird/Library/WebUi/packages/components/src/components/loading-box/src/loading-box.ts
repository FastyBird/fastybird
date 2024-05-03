import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { VariantTypes, ComponentSizeTypes, ComponentSize, Variant } from '@fastybird/web-ui-constants';

import type { Component, ExtractPropTypes } from 'vue';

export const loadingBoxProps = buildProps({
	/**
	 * @description loading box size
	 */
	size: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: ComponentSizeTypes.DEFAULT,
	},
	/**
	 * @description loading box variant
	 */
	variant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description determine whether loading box is shown
	 */
	show: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description determine whether loading box is stretched to full screen
	 */
	fullScreen: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description determine whether loading box is shown with animation effect
	 */
	animation: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description customize loading box spinner component
	 */
	spinner: {
		type: definePropType<string | boolean | Component>([String, Boolean, Object]),
		default: true,
	},
});

export type LoadingBoxProps = ExtractPropTypes<typeof loadingBoxProps>;
