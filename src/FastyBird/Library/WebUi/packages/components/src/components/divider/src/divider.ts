import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes, PropType } from 'vue';

export type BorderStyle = CSSStyleDeclaration['borderStyle'];

export enum DividerDirectionTypes {
	HORIZONTAL = 'horizontal',
	VERTICAL = 'vertical',
}

export const dividerDirections = [DividerDirectionTypes.HORIZONTAL, DividerDirectionTypes.VERTICAL];

export type DividerDirection = DividerDirectionTypes.HORIZONTAL | DividerDirectionTypes.VERTICAL;

export enum DividerContentPositionTypes {
	LEFT = 'left',
	CENTER = 'center',
	RIGHT = 'right',
}

export const dividerContentPositions = [DividerContentPositionTypes.LEFT, DividerContentPositionTypes.CENTER, DividerContentPositionTypes.RIGHT];

export type DividerContentPosition = DividerContentPositionTypes.LEFT | DividerContentPositionTypes.CENTER | DividerContentPositionTypes.RIGHT;

export const dividerProps = buildProps({
	/**
	 * @description set divider's direction
	 */
	direction: {
		type: definePropType<DividerDirection>(String),
		values: dividerDirections,
		default: DividerDirectionTypes.HORIZONTAL,
	},
	/**
	 * @description set the style of divider
	 */
	borderStyle: {
		type: definePropType<BorderStyle>(String),
		default: 'solid',
	},
	/**
	 * @description the position of the customized content on the divider line
	 */
	contentPosition: {
		type: definePropType<DividerContentPosition>(String),
		values: dividerContentPositions,
		default: DividerContentPositionTypes.CENTER,
	},
	/**
	 * @description determine whether show divider with gradient effect
	 */
	gradient: {
		type: Boolean as PropType<boolean>,
		default: false,
	},
} as const);

export type DividerProps = ExtractPropTypes<typeof dividerProps>;
