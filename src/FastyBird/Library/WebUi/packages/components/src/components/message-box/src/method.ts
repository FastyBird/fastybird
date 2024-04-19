import { debugWarn, isClient, isElement, isFunction, isObject, isString, isUndefined, isVNode } from '@fastybird/web-ui-utils';
import type { AppContext, VNode } from 'vue';
import { ComponentPublicInstance, createVNode, render } from 'vue';

import type { MessageBoxContext } from './instance';
import { instances } from './instance';
import type { MessageBoxAction, MessageBoxOptions, MessageBoxShortcutMethod, MessageBoxType } from './message-box';
import { IMessageBox, messageBoxDefaults, MessageBoxHandler, messageBoxTypes, MessageBoxTypeTypes } from './message-box';
import MessageBoxConstructor from './message-box.vue';

let seed = 1;

const normalizeOptions = (options: MessageBoxOptions): MessageBoxOptions => {
	const normalized: MessageBoxOptions = {
		...messageBoxDefaults,
		...options,
	};

	if (!normalized.appendTo) {
		normalized.appendTo = document.body;
	}

	return normalized;
};

const getAppendToElement = (props: MessageBoxOptions): Element => {
	if (!props.appendTo) {
		return document.body;
	}

	if (isElement(props.appendTo)) {
		return props.appendTo;
	}

	let appendTo: HTMLElement | null = document.body;

	if (isString(props.appendTo)) {
		appendTo = document.querySelector<HTMLElement>(props.appendTo);

		// fallback to default value with a warning when configured element is missing
		if (!isElement(appendTo)) {
			debugWarn('FbMessageBox', 'the appendTo option is not an HTMLElement. Falling back to document.body.');

			return document.body;
		}

		return appendTo;
	}

	return document.body;
};

const createMessageBox = (options: MessageBoxOptions, appContext: AppContext | null = null): MessageBoxContext => {
	const id = `message_box_${seed++}`;
	const userOnAction = options.onAction;

	const container = document.createElement('div');

	const props = {
		...options,
		onAction: (action: MessageBoxAction): void => {
			userOnAction?.(action, vm.exposed);
		},
		// Adding destruct method.
		// when transition leaves emitting `vanish` evt. so that we can do the clean job.
		onVanish: () => {
			// not sure if this causes mem leak, need proof to verify that.
			// maybe calling out like 1000 msg-box then close them all.
			render(null, container);

			const idx = instances.indexOf(instance);

			if (idx === -1) {
				return;
			}

			instances.splice(idx, 1);

			const { handler } = instance;

			handler.close();
		},
	};

	const vnode = createVNode(
		MessageBoxConstructor,
		props,
		isFunction(props.message) || isVNode(props.message)
			? {
					default: isFunction(props.message) ? props.message : () => props.message,
				}
			: null
	);

	vnode.appContext = appContext;

	render(vnode, container);

	getAppendToElement(options).appendChild(container.firstElementChild!);

	const vm = vnode.component!;

	const handler: MessageBoxHandler = {
		// instead of calling the onClose function directly, setting this value so that we can have the full lifecycle
		// for out component, so that all closing steps will not be skipped.
		close: () => {
			vm.exposed!.visible.value = false;
		},
	};

	const instance: MessageBoxContext = {
		id,
		vnode,
		vm,
		handler,
		props: (vnode.component as ComponentPublicInstance<any> | null)?.props,
	};

	return instance;
};

const messageBox = (options: MessageBoxOptions | string | VNode, appContext: AppContext | null = null): MessageBoxHandler => {
	if (!isClient) {
		return { close: () => undefined };
	}

	let opt: MessageBoxOptions;

	if (isString(options) || isVNode(options)) {
		opt = normalizeOptions({
			message: options,
		});
	} else {
		opt = normalizeOptions(options);
	}

	const instance = createMessageBox(opt, appContext);

	instances.push(instance);

	return instance.handler;
};

const MESSAGE_BOX_DEFAULT_OPTS: Record<MessageBoxType, Partial<MessageBoxOptions>> = {
	[MessageBoxTypeTypes.ALERT]: { closeOnPressEscape: false, closeOnClickBackdrop: false },
	[MessageBoxTypeTypes.CONFIRM]: { showCancelButton: true },
	[MessageBoxTypeTypes.PROMPT]: { showCancelButton: true, showInput: true },
};

const messageBoxFactory = (boxType: MessageBoxType) => {
	return (message: string | VNode, title: string | MessageBoxOptions, options?: MessageBoxOptions, appContext?: AppContext | null) => {
		let titleOrOpts: string;

		if (isObject(title)) {
			options = title as MessageBoxOptions;
			titleOrOpts = '';
		} else if (isUndefined(title)) {
			titleOrOpts = '';
		} else {
			titleOrOpts = title as string;
		}

		return messageBox(
			Object.assign(
				{
					title: titleOrOpts,
					message,
					...MESSAGE_BOX_DEFAULT_OPTS[boxType],
				},
				options,
				{
					boxType,
				}
			),
			appContext
		);
	};
};

messageBoxTypes.forEach((boxType) => {
	(messageBox as IMessageBox)[boxType] = messageBoxFactory(boxType) as MessageBoxShortcutMethod;
});

messageBox.closeAll = (): void => {
	for (const instance of instances) {
		instance.handler.close();
	}
};

messageBox._context = null;

export default messageBox as IMessageBox;
