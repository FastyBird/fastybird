import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { EVENT_CODE } from '@fastybird/web-ui-constants';

import { popperTriggerProps } from '../../popper';

import type { ExtractPropTypes } from 'vue';
import type { Arrayable } from '@fastybird/web-ui-utils';

export type TooltipTriggerType = 'hover' | 'focus' | 'click' | 'contextmenu';

export const useTooltipTriggerProps = buildProps({
	...popperTriggerProps,
	/**
	 * @description whether Tooltip is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description How should the tooltip be triggered (to show)
	 */
	trigger: {
		type: definePropType<Arrayable<TooltipTriggerType>>([String, Array]),
		default: 'hover',
	},
	/**
	 * @description When you click the mouse to focus on the trigger element, you can define a set of keyboard codes to control the display of tooltip through the keyboard
	 */
	triggerKeys: {
		type: definePropType<string[]>(Array),
		default: () => [EVENT_CODE.enter, EVENT_CODE.space],
	},
} as const);

export type TooltipTriggerProps = ExtractPropTypes<typeof useTooltipTriggerProps>;
