import { computed } from 'vue';

import type { ComputedRef, ComponentInternalInstance, Ref } from 'vue';

export const useMenu = (
	instance: ComponentInternalInstance,
	currentIndex: Ref<string>
): {
	parentMenu: ComputedRef<ComponentInternalInstance>;
	indexPath: ComputedRef<string[]>;
} => {
	const indexPath = computed<string[]>((): string[] => {
		let parent = instance.parent!;

		const path = [currentIndex.value];

		while (parent.type.name !== 'FbMenu') {
			if (parent.props.index) {
				path.unshift(parent.props.index as string);
			}
			parent = parent.parent!;
		}

		return path;
	});

	const parentMenu = computed(() => {
		let parent = instance.parent;

		while (parent && !['FbMenu', 'FbSubMenu'].includes(parent.type.name!)) {
			parent = parent.parent;
		}

		return parent!;
	});

	return {
		parentMenu,
		indexPath,
	};
};
