import { ShallowRef, shallowRef } from 'vue';

import { flattedChildren, isVNode } from '@fastybird/web-ui-utils';

import type { ComponentInternalInstance, VNode } from 'vue';

const getOrderedChildren = <T>(vm: ComponentInternalInstance, childComponentName: string, children: Record<number, T>): T[] => {
	const nodes = flattedChildren(vm.subTree).filter((n): n is VNode => isVNode(n) && (n.type as any)?.name === childComponentName && !!n.component);
	const uids = nodes.map((n) => n.component!.uid);

	return uids.map((uid) => children[uid]).filter((p) => !!p);
};

export const useOrderedChildren = <T extends { uid: number }>(
	vm: ComponentInternalInstance,
	childComponentName: string
): {
	children: ShallowRef<T[]>;
	addChild: (child: T) => void;
	removeChild: (uid: number) => void;
} => {
	const children: Record<number, T> = {};
	const orderedChildren = shallowRef<T[]>([]);

	const addChild = (child: T): void => {
		children[child.uid] = child;
		orderedChildren.value = getOrderedChildren(vm, childComponentName, children);
	};

	const removeChild = (uid: number): void => {
		delete children[uid];
		orderedChildren.value = orderedChildren.value.filter((children) => children.uid !== uid);
	};

	return {
		children: orderedChildren,
		addChild,
		removeChild,
	};
};
