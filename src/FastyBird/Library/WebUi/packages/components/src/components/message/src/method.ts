import { createVNode, render, VNode } from 'vue';

import { debugWarn, isClient, isElement, isFunction, isNumber, isString, isVNode } from '@fastybird/web-ui-utils';

import { messageConfig } from '../../config-provider';
import MessageConstructor from './message.vue';
import { messageDefaults, MessageHandler, MessageProps, messageTypes } from './message';
import { instances } from './instance';

import type { AppContext } from 'vue';
import type { MessageContext } from './instance';
import type { IMessage, MessageOptions, MessageShortcutMethod, MessageType } from './message';

let seed = 1;

const normalizeOptions = (options: MessageOptions): MessageOptions => {
	const normalized: MessageOptions = {
		...messageDefaults,
		...options,
	};

	if (!normalized.appendTo) {
		normalized.appendTo = document.body;
	}

	return normalized;
};

const getAppendToElement = (props: MessageOptions): Element => {
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
			debugWarn('FbMessage', 'the appendTo option is not an HTMLElement. Falling back to document.body.');

			return document.body;
		}

		return appendTo;
	}

	return document.body;
};

const createMessage = (options: MessageOptions, appContext: AppContext | null = null): MessageContext => {
	const id = `message_${seed++}`;
	const userOnClose = options.onClose;

	const container = document.createElement('div');

	const props = {
		...options,
		id,
		onClose: (): void => {
			userOnClose?.();

			const idx = instances.indexOf(instance);

			if (idx === -1) {
				return;
			}

			instances.splice(idx, 1);

			const { handler } = instance;

			handler.close();
		},

		// clean message element preventing mem leak
		onDestroy: (): void => {
			// since the element is destroyed, then the VNode should be collected by GC as well
			// we do not want cause any mem leak because we have returned vm as a reference to users
			// so that we manually set it to false.
			render(null, container);
		},
	};

	const vnode = createVNode(
		MessageConstructor,
		props,
		isFunction(props.message) || isVNode(props.message)
			? {
					default: isFunction(props.message) ? props.message : (): string | VNode | (() => VNode) => props.message,
				}
			: null
	);

	vnode.appContext = appContext;

	render(vnode, container);

	getAppendToElement(options).appendChild(container.firstElementChild!);

	const vm = vnode.component!;

	const handler: MessageHandler = {
		// instead of calling the onClose function directly, setting this value so that we can have the full lifecycle
		// for out component, so that all closing steps will not be skipped.
		close: () => {
			vm.exposed!.visible.value = false;
		},
	};

	const instance: MessageContext = {
		id,
		vnode,
		vm,
		handler,
		props: vnode.component!.props as MessageProps,
	};

	return instance;
};

const message = (options: MessageOptions | string | VNode, appContext: AppContext | null = null): MessageHandler => {
	if (!isClient) {
		return { close: () => undefined };
	}

	if (isNumber(messageConfig.max) && instances.length >= messageConfig.max) {
		return { close: () => undefined };
	}

	let opt: MessageOptions;

	if (isString(options) || isVNode(options)) {
		opt = normalizeOptions({
			message: options,
		});
	} else {
		opt = normalizeOptions(options);
	}

	if (opt.grouping && instances.length) {
		const instance = instances.find(({ props }) => props?.message === opt.message);

		if (instance) {
			instance.props.repeatNum += 1;
			instance.props.type = opt.type;

			return instance.handler;
		}
	}

	const instance = createMessage(opt, appContext);

	instances.push(instance);

	return instance.handler;
};

const messageFactory = (type: MessageType) => {
	return (msg: string | VNode, options?: MessageOptions, appContext?: AppContext | null): MessageHandler => {
		return message(
			Object.assign(
				{
					message: msg,
				},
				options,
				{
					type,
				}
			),
			appContext
		);
	};
};

messageTypes.forEach((type) => {
	(message as IMessage)[type] = messageFactory(type) as MessageShortcutMethod;
});

message.closeAll = (type?: MessageType): void => {
	for (const instance of instances) {
		if (!type || type === instance.props.type) {
			instance.handler.close();
		}
	}
};

message._context = null;

export default message as IMessage;
