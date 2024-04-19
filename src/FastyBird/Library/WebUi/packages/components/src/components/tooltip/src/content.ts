import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { useDelayedToggleProps } from '@fastybird/web-ui-hooks';

import { popperContentProps } from '../../popper';

import type { ExtractPropTypes } from 'vue';
import type {} from '@popperjs/core/lib/enums';
import type {} from '@popperjs/core/lib/types';

export const useTooltipContentProps = buildProps({
	...useDelayedToggleProps,
	...popperContentProps,
	/**
	 * @description which element the tooltip CONTENT appends to
	 */
	appendTo: {
		type: definePropType<string | HTMLElement>([String, Object]),
	},
	/**
	 * @description display content, can be overridden by `slot#content`
	 */
	content: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description whether `content` is treated as HTML string
	 */
	rawContent: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description when tooltip inactive and `persistent` is `false` , pop-confirm will be destroyed
	 */
	persistent: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description same as `aria-label`
	 */
	ariaLabel: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	// because model toggle prop is generated dynamically
	// so the typing cannot be evaluated by typescript as type:
	// [name]: { type: Boolean, default: null }
	// so we need to declare that again for type checking.
	/**
	 * @description visibility of Tooltip
	 */
	visible: {
		type: definePropType<boolean | null>(Boolean),
		default: null,
	},
	/**
	 * @description animation name
	 */
	transition: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description whether tooltip content is teleported, if `true` it will be teleported to where `append-to` sets
	 */
	teleported: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether Tooltip is disabled
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
} as const);

export type TooltipContentProps = ExtractPropTypes<typeof useTooltipContentProps>;
