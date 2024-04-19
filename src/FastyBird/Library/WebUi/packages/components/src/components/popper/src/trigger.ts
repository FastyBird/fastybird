import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { Measurable } from './constants';
import type Trigger from './trigger.vue';

export const popperTriggerProps = buildProps({
	/**
	 * @description
	 */
	virtualRef: {
		type: definePropType<Measurable>(Object),
	},
	/**
	 * @description
	 */
	virtualTriggering: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	onMouseenter: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onMouseleave: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onClick: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onKeydown: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onFocus: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onBlur: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	onContextmenu: {
		type: definePropType<(e: Event) => void>(Function),
	},
	/**
	 * @description
	 */
	id: {
		type: definePropType<string | undefined>(String),
		default: undefined,
	},
	/**
	 * @description
	 */
	open: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type PopperTriggerProps = typeof popperTriggerProps;

export type PopperTriggerInstance = InstanceType<typeof Trigger>;
