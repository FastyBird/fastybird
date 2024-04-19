import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const iconWithChildProps = buildProps({
	/**
	 * @description child icon variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description main icon size
	 */
	size: {
		type: definePropType<number | string>([Number, String]),
		default: 20,
	},
	/**
	 * @description main icon color
	 */
	color: String,
} as const);

export type IconWithChildProps = ExtractPropTypes<typeof iconWithChildProps>;
