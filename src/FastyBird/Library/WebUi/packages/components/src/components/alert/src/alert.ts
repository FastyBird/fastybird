import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { VariantTypes, EffectTypes, Variant, Effect } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const alertProps = buildProps({
	/**
	 * @description alert variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
		default: VariantTypes.DEFAULT,
	},
	/**
	 * @description alert theme style
	 */
	effect: {
		type: definePropType<Effect>(String),
		values: [EffectTypes.LIGHT, EffectTypes.DARK],
		default: EffectTypes.LIGHT,
	},
	/**
	 * @description whether content is placed in the center
	 */
	center: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether alert can be dismissed
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description customized close button text
	 */
	closeText: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether a type icon is displayed
	 */
	icon: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
});

export type AlertProps = ExtractPropTypes<typeof alertProps>;

export const alertEmits = {
	close: (evt: UIEvent) => evt instanceof UIEvent,
};

export type AlertEmits = typeof alertEmits;
