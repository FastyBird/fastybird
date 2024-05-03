import { buildProps, definePropType, isClient, isObject, mutable } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';

import type { AppContext, Component, ExtractPropTypes, StyleValue, VNode } from 'vue';
import type { Mutable } from '@fastybird/web-ui-utils';

export const messageBoxMessages = [VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR] as const;

export type MessageBoxMessage = VariantTypes.INFO | VariantTypes.SUCCESS | VariantTypes.WARNING | VariantTypes.ERROR;

export enum MessageBoxTypeTypes {
	PROMPT = 'prompt',
	ALERT = 'alert',
	CONFIRM = 'confirm',
}

export const messageBoxTypes = [MessageBoxTypeTypes.PROMPT, MessageBoxTypeTypes.ALERT, MessageBoxTypeTypes.CONFIRM] as const;

export type MessageBoxType = MessageBoxTypeTypes.PROMPT | MessageBoxTypeTypes.ALERT | MessageBoxTypeTypes.CONFIRM;

export enum MessageBoxActionTypes {
	CONFIRM = 'confirm',
	CLOSE = 'close',
	CANCEL = 'cancel',
}

export const messageBoxActions = [MessageBoxActionTypes.CONFIRM, MessageBoxActionTypes.CLOSE, MessageBoxActionTypes.CANCEL] as const;

export type MessageBoxAction = MessageBoxActionTypes.CONFIRM | MessageBoxActionTypes.CLOSE | MessageBoxActionTypes.CANCEL;

export interface MessageBoxInputValidator {
	(value: string): boolean | string;
}

export const messageBoxDefaults = mutable({
	message: undefined,
	title: undefined,
	buttonSize: ComponentSizeTypes.DEFAULT,
	roundButton: false,
	backdrop: true,
	lockScroll: true,
	closable: true,
	closeOnClickBackdrop: true,
	closeOnPressEscape: true,
	closeOnHashChange: true,
	center: false,
	draggable: false,
	overflow: false,
	appendTo: isClient ? document.body : (undefined as never),
	type: VariantTypes.INFO,
	boxType: undefined,
	autofocus: true,
	customClass: undefined,
	customStyle: undefined,
	cancelButtonText: undefined,
	confirmButtonText: undefined,
	cancelButtonClass: undefined,
	confirmButtonClass: undefined,
	icon: undefined,
	dangerouslyUseHTMLString: false,
	distinguishCancelAndClose: false,
	showCancelButton: false,
	showConfirmButton: true,
	showInput: false,
	inputPlaceholder: undefined,
	inputValue: '',
	inputPattern: undefined,
	inputType: 'text',
	inputValidator: undefined,
	inputErrorMessage: undefined,
	beforeClose: undefined,
} as const);

export const messageBoxProps = buildProps({
	/**
	 * @description button size
	 */
	buttonSize: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: messageBoxDefaults.buttonSize,
	},
	/**
	 * @description
	 */
	roundButton: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.roundButton,
	},
	/**
	 * @description whether a mask is displayed
	 */
	backdrop: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.backdrop,
	},
	/**
	 * @description whether scroll of body is disabled while modal is displayed
	 */
	lockScroll: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.lockScroll,
	},
	/**
	 * @description whether the modal could be closed by close action
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.closable,
	},
	/**
	 * @description whether the modal can be closed by clicking the mask
	 */
	closeOnClickBackdrop: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.closeOnClickBackdrop,
	},
	/**
	 * @description whether the modal can be closed by pressing ESC
	 */
	closeOnPressEscape: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.closeOnPressEscape,
	},
	/**
	 * @description
	 */
	closeOnHashChange: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.closeOnHashChange,
	},
	/**
	 * @description whether to align the header and footer in center
	 */
	center: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.center,
	},
	/**
	 * @description enable dragging feature for Modal
	 */
	draggable: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.draggable,
	},
	/**
	 * @description draggable Modal can overflow the viewport
	 */
	overflow: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.overflow,
	},
	/**
	 * @description
	 */
	boxType: {
		type: definePropType<MessageBoxType | undefined>(String),
		values: messageBoxTypes,
		default: messageBoxDefaults.boxType,
	},

	/**
	 * @description Content of the MessageBox
	 */
	message: {
		type: definePropType<string | VNode | (() => VNode)>([String, Object, Function]),
		required: true,
	},
	/**
	 * @description Title of the MessageBox
	 */
	title: String,
	/**
	 * @description
	 */
	type: {
		type: definePropType<MessageBoxMessage | undefined>(String),
		values: messageBoxMessages,
		default: messageBoxDefaults.type,
	},
	/**
	 * @description Autofocus when open message-box
	 */
	autofocus: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.autofocus,
	},
	/**
	 * @description Custom class name for MessageBox
	 */
	customClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description Custom inline style for MessageBox
	 */
	customStyle: {
		type: definePropType<StyleValue>([String, Object, Array]),
		default: messageBoxDefaults.customStyle,
	},
	/**
	 * @description Callback before MessageBox closes, and it will prevent MessageBox from closing
	 */
	beforeClose: {
		type: definePropType<(action: MessageBoxAction, instance: any, done: () => void) => void | undefined>(Function),
		default: messageBoxDefaults.beforeClose,
	},
	/**
	 * @description Text content of cancel button
	 */
	cancelButtonText: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.cancelButtonText,
	},
	/**
	 * @description Text content of confirm button
	 */
	confirmButtonText: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.confirmButtonText,
	},
	/**
	 * @description Custom class name of cancel button
	 */
	cancelButtonClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description Custom class name of confirm button
	 */
	confirmButtonClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description Custom icon component
	 */
	icon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: messageBoxDefaults.icon,
	},
	/**
	 * @description Whether message is treated as HTML string
	 */
	dangerouslyUseHTMLString: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.dangerouslyUseHTMLString,
	},
	/**
	 * @description Whether to distinguish canceling and closing
	 */
	distinguishCancelAndClose: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.distinguishCancelAndClose,
	},
	/**
	 * @description Whether to show a cancel button
	 */
	showCancelButton: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.showCancelButton,
	},
	/**
	 * @description Whether to show a confirm button
	 */
	showConfirmButton: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.showConfirmButton,
	},
	/**
	 * @description Whether to show an input
	 */
	showInput: {
		type: definePropType<boolean>(Boolean),
		default: messageBoxDefaults.showInput,
	},
	/**
	 * @description Placeholder of input
	 */
	inputPlaceholder: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.inputPlaceholder,
	},
	/**
	 * @description Initial value of input
	 */
	inputValue: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.inputValue,
	},
	/**
	 * @description Regexp for the input
	 */
	inputPattern: {
		type: definePropType<RegExp | undefined>(Object),
		default: messageBoxDefaults.inputPattern,
	},
	/**
	 * @description Input Type: text, textArea, password or number
	 */
	inputType: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.inputType,
	},
	/**
	 * @description Validation function for the input. Should return a boolean or string. If a string is returned, it will be assigned to inputErrorMessage
	 */
	inputValidator: {
		type: definePropType<MessageBoxInputValidator | undefined>(Function),
		default: messageBoxDefaults.inputValidator,
	},
	/**
	 * @description Error message when validation fails
	 */
	inputErrorMessage: {
		type: definePropType<string | undefined>(String),
		default: messageBoxDefaults.inputErrorMessage,
	},
});

export type MessageBoxProps = ExtractPropTypes<typeof messageBoxProps>;

export const messageBoxEmits = {
	action: (action: MessageBoxAction): boolean => isObject(action),
	vanish: (): boolean => true,
};

export type MessageBoxEmits = typeof messageBoxEmits;

export type MessageBoxOptions = Partial<
	Mutable<
		Omit<MessageBoxProps, 'backdrop'> & {
			appendTo?: HTMLElement | string;
			onAction?: (action: MessageBoxAction, instance: any, value?: string) => void;
		}
	>
>;

export interface MessageBoxHandler {
	/** @description close the message box */
	close: () => void;
}

export type MessageBoxShortcutMethod = ((
	message: MessageBoxOptions['message'],
	title: MessageBoxOptions['title'],
	options?: MessageBoxOptions,
	appContext?: AppContext | null
) => MessageBoxHandler) &
	((message: MessageBoxOptions['message'], options?: MessageBoxOptions, appContext?: AppContext | null) => MessageBoxHandler);

export interface IMessageBox {
	_context: AppContext | null;

	/** Show a message box */
	(options: MessageBoxOptions, appContext?: AppContext | null): MessageBoxHandler;

	/** Show an alert message box */
	alert: MessageBoxShortcutMethod;

	/** Show a confirmation message box */
	confirm: MessageBoxShortcutMethod;

	/** Show a prompt message box */
	prompt: MessageBoxShortcutMethod;

	/** Close all messages boxes */
	closeAll: () => void;
}
