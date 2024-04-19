import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes, Effect, EffectTypes, Variant, VariantTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const tagProps = buildProps({
	/**
	 * @description tag variant
	 */
	variant: {
		type: definePropType<Variant>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: VariantTypes.PRIMARY,
	},
	/**
	 * @description whether Tag can be removed
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether to disable animations
	 */
	disableTransitions: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description whether Tag has a highlighted border
	 */
	hit: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description background color of the Tag
	 */
	color: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description size of Tag
	 */
	size: {
		type: definePropType<ComponentSize | undefined>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: undefined,
	},
	/**
	 * @description theme of Tag
	 */
	effect: {
		type: definePropType<Effect>(String),
		values: [EffectTypes.LIGHT, EffectTypes.DARK],
		default: EffectTypes.LIGHT,
	},
	/**
	 * @description whether Tag is rounded
	 */
	round: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type TagProps = ExtractPropTypes<typeof tagProps>;

export const tagEmits = {
	close: (evt: UIEvent) => evt instanceof UIEvent,
	click: (evt: UIEvent) => evt instanceof UIEvent,
};

export type TagEmits = typeof tagEmits;
