import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { VariantTypes, ComponentSizeTypes, ComponentSize, Variant } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const spinnerProps = buildProps({
	/**
	 * @description spinner variant
	 */
	variant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description spinner size
	 */
	size: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: ComponentSizeTypes.DEFAULT,
	},
	/**
	 * @description determine whether the spinner background color is always on
	 */
	bg: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
});

export type SpinnerProps = ExtractPropTypes<typeof spinnerProps>;
