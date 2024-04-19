import { buildProps, definePropType, isBoolean } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const checkTagProps = buildProps({
	/**
	 * @description is checked
	 */
	checked: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description type of Tag
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.PRIMARY,
	},
} as const);

export type CheckTagProps = ExtractPropTypes<typeof checkTagProps>;

export const checkTagEmits = {
	'update:checked': (value: boolean) => isBoolean(value),
	[CHANGE_EVENT]: (value: boolean) => isBoolean(value),
};

export type CheckTagEmits = typeof checkTagEmits;
