import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const badgeProps = buildProps({
	/**
	 * @description display value
	 */
	value: {
		type: definePropType<string | number>([String, Number]),
		default: '',
	},
	/**
	 * @description maximum value, shows `{max}+` when exceeded. Only works if value is a number
	 */
	max: {
		type: definePropType<number>(Number),
		default: 99,
	},
	/**
	 * @description whether a little dot is displayed
	 */
	isDot: Boolean,
	/**
	 * @description whether a badge is hidden
	 */
	hidden: Boolean,
	/**
	 * @description badge variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.DANGER,
	},
	/**
	 * @description whether to show badge when value is zero
	 */
	showZero: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type BadgeProps = ExtractPropTypes<typeof badgeProps>;
