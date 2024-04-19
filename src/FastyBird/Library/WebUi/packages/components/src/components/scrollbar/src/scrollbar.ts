import { buildProps, definePropType, isNumber } from '@fastybird/web-ui-utils';
import { EffectTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes, StyleValue } from 'vue';

export const scrollbarProps = buildProps({
	/**
	 * @description height of scrollbar
	 */
	height: {
		type: definePropType<string | number | undefined>([String, Number]),
		default: undefined,
	},
	/**
	 * @description max height of scrollbar
	 */
	maxHeight: {
		type: definePropType<string | number | undefined>([String, Number]),
		default: undefined,
	},
	/**
	 * @description whether to use the native scrollbar
	 */
	native: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description style of wrap
	 */
	wrapStyle: {
		type: definePropType<StyleValue | undefined>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description class of wrap
	 */
	wrapClass: {
		type: definePropType<string | string[] | undefined>([String, Array]),
		default: undefined,
	},
	/**
	 * @description class of view
	 */
	viewClass: {
		type: definePropType<string | string[] | undefined>([String, Array]),
		default: undefined,
	},
	/**
	 * @description style of view
	 */
	viewStyle: {
		type: definePropType<StyleValue | undefined>([String, Object, Array]),
		default: undefined,
	},
	/**
	 * @description do not respond to container size changes, if the container size does not change, it is better to set it to optimize performance
	 */
	noresize: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description element tag of the view
	 */
	tag: {
		type: definePropType<string>(String),
		default: 'div',
	},
	/**
	 * @description always show
	 */
	always: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description minimum size of scrollbar
	 */
	minSize: {
		type: definePropType<number>(Number),
		default: 20,
	},
	/**
	 * @description id of view
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description role of view
	 */
	role: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description aria-label of view
	 */
	ariaLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description aria-orientation of view
	 */
	ariaOrientation: {
		type: definePropType<string>(String),
		values: ['horizontal', 'vertical'],
	},
	/**
	 * @description
	 */
	shadow: {
		type: definePropType<string | undefined>(String),
		values: [EffectTypes.LIGHT, EffectTypes.DARK, EffectTypes.PLAIN],
		default: undefined,
	},
} as const);

export type ScrollbarProps = ExtractPropTypes<typeof scrollbarProps>;

export const scrollbarEmits = {
	scroll: ({ scrollTop, scrollLeft }: { scrollTop: number; scrollLeft: number }) => [scrollTop, scrollLeft].every(isNumber),
};

export type ScrollbarEmits = typeof scrollbarEmits;
