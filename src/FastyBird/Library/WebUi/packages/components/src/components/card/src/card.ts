import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { VariantTypes, EffectTypes, Effect, Variant } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes, StyleValue } from 'vue';

export enum CardShadowTypes {
	ALWAYS = 'always',
	HOVER = 'hover',
	NEVER = 'never',
}

export const cardShadows = [CardShadowTypes.ALWAYS, CardShadowTypes.HOVER, CardShadowTypes.NEVER] as const;

export type CardShadow = CardShadowTypes.ALWAYS | CardShadowTypes.HOVER | CardShadowTypes.NEVER;

export const cardProps = buildProps({
	/**
	 * @description title of the card
	 */
	header: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description footer of the card
	 */
	footer: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description extra of the card header
	 */
	extra: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description cover of the card
	 */
	cover: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description card variant
	 */
	variant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description card theme style
	 */
	effect: {
		type: definePropType<Effect>(String),
		values: [EffectTypes.LIGHT, EffectTypes.DARK],
		default: EffectTypes.LIGHT,
	},
	/**
	 * @description card header variant
	 */
	headerVariant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description card body variant
	 */
	bodyVariant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description card footer variant
	 */
	footerVariant: {
		type: definePropType<Variant | undefined>(String),
		values: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
		default: undefined,
	},
	/**
	 * @description CSS style of card header
	 */
	headerStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description custom class name of card header
	 */
	headerClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description CSS style of card body
	 */
	bodyStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description custom class name of card body
	 */
	bodyClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description CSS style of card footer
	 */
	footerStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description custom class name of card footer
	 */
	footerClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description when to show card shadows
	 */
	shadow: {
		type: definePropType<CardShadow>(String),
		values: [CardShadowTypes.ALWAYS, CardShadowTypes.HOVER, CardShadowTypes.NEVER],
		default: CardShadowTypes.ALWAYS,
	},
	/**
	 * @description when to show card border
	 */
	bordered: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description when to show card loading animation
	 */
	loading: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type CardProps = ExtractPropTypes<typeof cardProps>;
