import { placements } from '@popperjs/core';
import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes, StyleValue } from 'vue';
import type { Options, Placement } from '@popperjs/core';
import type { Measurable } from './constants';
import type Content from './content.vue';

const POSITIONING_STRATEGIES = ['fixed', 'absolute'] as const;

export interface CreatePopperInstanceParams {
	referenceEl: Measurable;
	popperContentEl: HTMLElement;
	arrowEl: HTMLElement | undefined;
}

export const popperCoreConfigProps = buildProps({
	/**
	 * @description
	 */
	boundariesPadding: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description
	 */
	fallbackPlacements: {
		type: definePropType<Placement[] | undefined>(Array),
		default: undefined,
	},
	/**
	 * @description
	 */
	gpuAcceleration: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description offset of the Tooltip
	 */
	offset: {
		type: definePropType<number>(Number),
		default: 12,
	},
	/**
	 * @description position of Tooltip
	 */
	placement: {
		type: definePropType<string>(String),
		values: placements,
		default: 'bottom',
	},
	/**
	 * @description [popper.js](https://popper.js.org/docs/v2/) parameters
	 */
	popperOptions: {
		type: definePropType<Partial<Options>>(Object),
		default: () => ({}),
	},
	/**
	 * @description
	 */
	strategy: {
		type: definePropType<string>(String),
		values: POSITIONING_STRATEGIES,
		default: 'absolute',
	},
} as const);

export type PopperCoreConfigProps = ExtractPropTypes<typeof popperCoreConfigProps>;

export const popperContentProps = buildProps({
	...popperCoreConfigProps,
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	style: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	className: {
		type: definePropType<string>(String),
	},
	effect: {
		type: definePropType<string>(String),
		default: 'dark',
	},
	visible: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	enterable: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	pure: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	focusOnShow: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	trapping: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	popperClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	popperStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: undefined,
	},
	referenceEl: {
		type: definePropType<HTMLElement>(Object),
	},
	triggerTargetEl: {
		type: definePropType<HTMLElement>(Object),
	},
	stopPopperMouseEvent: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	ariaLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	virtualTriggering: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	zIndex: {
		type: definePropType<number | undefined>(Number),
		default: undefined,
	},
} as const);

export type PopperContentProps = ExtractPropTypes<typeof popperContentProps>;

export const popperContentEmits = {
	mouseenter: (evt: MouseEvent): boolean => evt instanceof MouseEvent,
	mouseleave: (evt: MouseEvent): boolean => evt instanceof MouseEvent,
	focus: (): boolean => true,
	blur: (): boolean => true,
	close: (): boolean => true,
};

export type PopperContentEmits = typeof popperContentEmits;

export type PopperContentInstance = InstanceType<typeof Content>;
