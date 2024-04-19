import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { createModelToggleComposable } from '@fastybird/web-ui-hooks';

import { popperArrowProps, popperProps } from '../../popper';
import { useTooltipContentProps } from './content';
import { useTooltipTriggerProps } from './trigger';

import type { ExtractPropTypes } from 'vue';

export const {
	useModelToggleProps: useTooltipModelToggleProps,
	useModelToggleEmits: useTooltipModelToggleEmits,
	useModelToggle: useTooltipModelToggle,
} = createModelToggleComposable('visible' as const);

export const useTooltipProps = buildProps({
	...popperProps,
	...useTooltipModelToggleProps,
	...useTooltipContentProps,
	...useTooltipTriggerProps,
	...popperArrowProps,
	/**
	 * @description whether the tooltip content has an arrow
	 */
	showArrow: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
});

export type TooltipProps = ExtractPropTypes<typeof useTooltipProps>;

export const tooltipEmits = [...useTooltipModelToggleEmits, 'before-show', 'before-hide', 'show', 'hide', 'open', 'close'];
