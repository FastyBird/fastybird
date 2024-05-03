<template>
	<ul v-show="visible" ref="groupRef" :class="ns.be('group', 'wrap')">
		<li :class="ns.be('group', 'title')">{{ props.label }}</li>
		<li>
			<ul :class="ns.b('group')">
				<slot />
			</ul>
		</li>
	</ul>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, onMounted, provide, reactive, ref, toRefs } from 'vue';

import { isArray } from '@vue/shared';
import { useMutationObserver } from '@vueuse/core';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbOptionInstance } from './instance';
import { optionGroupProps } from './option-group';
import { selectGroupContextKey } from './constants';

import type { VNode } from 'vue';

defineOptions({
	name: 'FbOptionGroup',
});

const props = defineProps(optionGroupProps);

const ns = useNamespace('select');

const groupRef = ref<HTMLElement | undefined>();
const children = ref<FbOptionInstance[]>([]);

const instance = getCurrentInstance();

provide(
	selectGroupContextKey,
	reactive({
		...toRefs(props),
	})
);

const visible = computed<boolean>((): boolean => children.value.some((option: FbOptionInstance) => option.state.visible === true));

// get all instances of options
const flattedChildren = (node: VNode): FbOptionInstance[] => {
	const children: FbOptionInstance[] = [];

	if (isArray(node.children)) {
		(node.children as any[]).forEach((child) => {
			if (child.type && child.type.name === 'FbOption' && child.component && child.component.proxy) {
				children.push(child.component.proxy as FbOptionInstance);
			} else if (child.children?.length) {
				children.push(...flattedChildren(child));
			} else if (child.component?.subTree) {
				children.push(...flattedChildren(child.component.subTree));
			}
		});
	}

	return children;
};

const updateChildren = (): void => {
	children.value = flattedChildren(instance!.subTree);
};

onMounted((): void => {
	updateChildren();
});

useMutationObserver(groupRef, updateChildren, {
	attributes: true,
	subtree: true,
	childList: true,
});
</script>
