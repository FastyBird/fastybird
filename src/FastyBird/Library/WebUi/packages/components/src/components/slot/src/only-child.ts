import { Comment, Fragment, Text, cloneVNode, defineComponent, inject, withDirectives, h, RendererNode, RendererElement } from 'vue';

import { NOOP, isObject } from '@vue/shared';
import { FORWARD_REF_INJECTION_KEY, useForwardRefDirective, useNamespace } from '@fastybird/web-ui-hooks';
import { debugWarn } from '@fastybird/web-ui-utils';

import type { Ref, VNode } from 'vue';

const NAME = 'FbOnlyChild';

export const OnlyChild = defineComponent({
	name: NAME,
	setup(_, { slots, attrs }) {
		const forwardRefInjection = inject(FORWARD_REF_INJECTION_KEY);
		const forwardRefDirective = useForwardRefDirective(forwardRefInjection?.setForwardRef ?? NOOP);

		return (): VNode<RendererNode, RendererElement, { [key: string]: any }> | null => {
			const defaultSlot = slots.default?.(attrs);

			if (!defaultSlot) {
				return null;
			}

			if (defaultSlot.length > 1) {
				debugWarn(NAME, 'requires exact only one valid child.');

				return null;
			}

			const firstLegitNode = findFirstLegitChild(defaultSlot);

			if (!firstLegitNode) {
				debugWarn(NAME, 'no valid child node found');

				return null;
			}

			return withDirectives(cloneVNode(firstLegitNode!, attrs), [[forwardRefDirective]]);
		};
	},
});

const findFirstLegitChild = (node: VNode[] | undefined): VNode | null => {
	if (!node) {
		return null;
	}

	const children = node as VNode[];

	for (const child of children) {
		/**
		 * when user uses h(Fragment, [text]) to render plain string,
		 * this switch case just cannot handle, when the value is primitives
		 * we should just return the wrapped string
		 */
		if (isObject(child)) {
			switch (child.type) {
				case Comment:
					continue;
				case Text:
				case 'svg':
					return wrapTextContent(child);
				case Fragment:
					return findFirstLegitChild(child.children as VNode[]);
				default:
					return child;
			}
		}

		return wrapTextContent(child);
	}

	return null;
};

const wrapTextContent = (s: string | VNode): VNode => {
	const ns = useNamespace('only-child');

	return h('span', { class: [ns.e('content')] }, [s]);
};

export type OnlyChildExpose = {
	forwardRef: Ref<HTMLElement>;
};
