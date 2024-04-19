import { buildProps, definePropType, isClient, mutable } from '@fastybird/web-ui-utils';
import { VariantTypes } from '@fastybird/web-ui-constants';

import type { AppContext, Component, ExtractPropTypes, VNode } from 'vue';
import type { Mutable } from '@fastybird/web-ui-utils';

export const messageTypes = [VariantTypes.SUCCESS, VariantTypes.INFO, VariantTypes.WARNING, VariantTypes.ERROR] as const;

export type MessageType = VariantTypes.SUCCESS | VariantTypes.INFO | VariantTypes.WARNING | VariantTypes.ERROR;

export interface MessageConfigContext {
	max?: number;
}

export const messageDefaults = mutable({
	customClass: undefined,
	center: false,
	dangerouslyUseHTMLString: false,
	duration: 3000,
	icon: undefined,
	id: undefined,
	message: undefined,
	showClose: false,
	type: VariantTypes.INFO,
	offset: 16,
	zIndex: 0,
	grouping: false,
	repeatNum: 1,
	appendTo: isClient ? document.body : (undefined as never),
	onClose: undefined,
} as const);

export const messageProps = buildProps({
	/**
	 * @description custom class name for Message
	 */
	customClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description whether to center the text
	 */
	center: {
		type: definePropType<boolean>(Boolean),
		default: messageDefaults.center,
	},
	/**
	 * @description whether `message` is treated as HTML string
	 */
	dangerouslyUseHTMLString: {
		type: definePropType<boolean>(Boolean),
		default: messageDefaults.dangerouslyUseHTMLString,
	},
	/**
	 * @description display duration, millisecond. If set to 0, it will not turn off automatically
	 */
	duration: {
		type: definePropType<number>(Number),
		default: messageDefaults.duration,
	},
	/**
	 * @description custom icon component, overrides `type`
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: messageDefaults.icon,
	},
	/**
	 * @description message dom id
	 */
	id: {
		type: definePropType<string>(String),
		required: true,
	},
	/**
	 * @description message text
	 */
	message: {
		type: definePropType<string | VNode | (() => VNode)>([String, Object, Function]),
		required: true,
	},
	/**
	 * @description callback function when closed with the message instance as the parameter
	 */
	onClose: {
		type: definePropType<() => void | undefined>(Function),
		default: undefined,
	},
	/**
	 * @description whether to show a close button
	 */
	showClose: {
		type: definePropType<boolean>(Boolean),
		default: messageDefaults.showClose,
	},
	/**
	 * @description message type
	 */
	type: {
		type: definePropType<MessageType>(String),
		values: messageTypes,
		default: messageDefaults.type,
		required: true,
	},
	/**
	 * @description set the distance to the top of viewport
	 */
	offset: {
		type: definePropType<number>(Number),
		default: messageDefaults.offset,
	},
	/**
	 * @description input box size
	 */
	zIndex: {
		type: definePropType<number>(Number),
		default: messageDefaults.zIndex,
	},
	/**
	 * @description merge messages with the same content, type of VNode message is not supported
	 */
	grouping: {
		type: definePropType<boolean>(Boolean),
		default: messageDefaults.grouping,
	},
	/**
	 * @description The number of repetitions, similar to badge, is used as the initial number when used with `grouping`
	 */
	repeatNum: {
		type: definePropType<number>(Number),
		default: messageDefaults.repeatNum,
	},
} as const);

export type MessageProps = ExtractPropTypes<typeof messageProps>;

export const messageEmits = {
	close: () => true,
	destroy: () => true,
};

export type MessageEmits = typeof messageEmits;

export type MessageOptions = Partial<
	Mutable<
		Omit<MessageProps, 'id'> & {
			appendTo?: HTMLElement | string;
		}
	>
>;

export interface MessageHandler {
	/** @description close the message */
	close: () => void;
}

export type MessageShortcutMethod = ((
	message: MessageOptions['message'],
	options?: MessageOptions,
	appContext?: AppContext | null
) => MessageHandler) &
	((options?: MessageOptions, appContext?: AppContext | null) => MessageHandler);

export interface IMessage {
	_context: AppContext | null;

	/** Show a message */
	(options?: MessageOptions, appContext?: AppContext | null): MessageHandler;

	/** Show a success message */
	success: MessageShortcutMethod;

	/** Show a warning message */
	warning: MessageShortcutMethod;

	/** Show a info message */
	info: MessageShortcutMethod;

	/** Show a error message */
	error: MessageShortcutMethod;

	/** Close all messages */
	closeAll(type?: MessageType): void;
}
