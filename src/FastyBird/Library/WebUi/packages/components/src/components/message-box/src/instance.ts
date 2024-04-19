import { shallowReactive } from 'vue';

import type { ComponentPublicInstance, VNode } from 'vue';
import type { Mutable } from '@fastybird/web-ui-utils';
import type { MessageBoxHandler, MessageBoxProps } from './message-box';

export type MessageBoxContext = {
	id: string;
	vnode: VNode;
	handler: MessageBoxHandler;
	vm: ComponentPublicInstance<any>;
	props: Mutable<MessageBoxProps>;
};

export const instances: MessageBoxContext[] = shallowReactive([]);

export const getInstance = (id: string): { current: MessageBoxContext; prev: MessageBoxContext | undefined } => {
	const idx = instances.findIndex((instance) => instance.id === id);
	const current = instances[idx];
	let prev: MessageBoxContext | undefined;

	if (idx > 0) {
		prev = instances[idx - 1];
	}

	return { current, prev };
};

export const getLastOffset = (id: string): number => {
	const { prev } = getInstance(id);

	if (!prev) {
		return 0;
	}

	return prev.vm.exposed!.bottom.value;
};

export const getOffsetOrSpace = (id: string, offset: number): number => {
	const idx = instances.findIndex((instance) => instance.id === id);

	return idx > 0 ? 16 : offset;
};
