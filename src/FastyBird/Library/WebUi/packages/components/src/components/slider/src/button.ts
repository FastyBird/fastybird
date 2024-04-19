import { placements } from '@popperjs/core';
import { buildProps, definePropType, isNumber } from '@fastybird/web-ui-utils';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes, Ref } from 'vue';
import type { FbSliderButtonInstance } from './instance';

export const sliderButtonProps = buildProps({
	modelValue: {
		type: definePropType<number>(Number),
		default: 0,
	},
	vertical: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	tooltipClass: {
		type: definePropType<string | undefined>(String),
	},
	placement: {
		type: definePropType<string>(String),
		values: placements,
		default: 'top',
	},
} as const);

export type SliderButtonProps = ExtractPropTypes<typeof sliderButtonProps>;

export const sliderButtonEmits = {
	[UPDATE_MODEL_EVENT]: (value: number) => isNumber(value),
};

export type SliderButtonEmits = typeof sliderButtonEmits;

export type ButtonRefs = Record<'firstButton' | 'secondButton', Ref<FbSliderButtonInstance | undefined>>;

export interface SliderButtonInitData {
	hovering: boolean;
	dragging: boolean;
	isClick: boolean;
	startX: number;
	currentX: number;
	startY: number;
	currentY: number;
	startPosition: number;
	newPosition: number;
	oldValue: number;
}
