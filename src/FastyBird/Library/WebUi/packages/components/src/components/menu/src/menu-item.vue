<template>
	<li
		:class="[nsMenuItem.b(), nsMenuItem.is('active', active), nsMenuItem.is('disabled', props.disabled)]"
		role="menuitem"
		tabindex="-1"
		@click="handleClick"
	>
		<fb-tooltip
			v-if="parentMenu.type.name === 'FbMenu' && rootMenu.props.collapse && $slots.title"
			:effect="rootMenu.props.popperEffect"
			placement="right"
			:fallback-placements="['left']"
			persistent
		>
			<template #content>
				<slot name="title" />
			</template>

			<div :class="nsMenu.be('tooltip', 'trigger')">
				<slot />
			</div>
		</fb-tooltip>

		<template v-else>
			<slot />
			<slot name="title" />
		</template>
	</li>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, inject, onBeforeUnmount, onMounted, reactive, toRef } from 'vue';

import { throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { menuContextKey, subMenuContextKey } from './constants';

import { useMenu } from './hooks';
import { menuItemEmits, menuItemProps } from './menu-item';
import { FbTooltip } from '../../tooltip';

import type { MenuItemRegistered } from './types';

defineOptions({
	name: 'FbMenuItem',
});

const props = defineProps(menuItemProps);
const emit = defineEmits(menuItemEmits);

const instance = getCurrentInstance()!;
const nsMenu = useNamespace('menu');
const nsMenuItem = useNamespace('menu-item');

const rootMenu = inject(menuContextKey);

if (!rootMenu) {
	throwError(instance!.type.name!, 'can not inject root menu');
}

const { parentMenu, indexPath } = useMenu(instance, toRef(props, 'index'));

const subMenu = inject(subMenuContextKey);

if (!subMenu) {
	throwError(instance!.type.name!, 'can not inject sub menu');
}

const active = computed<boolean>((): boolean => props.index === rootMenu.activeIndex);

const item: MenuItemRegistered = reactive({
	index: props.index,
	indexPath,
	active,
});

const handleClick = (): void => {
	if (!props.disabled) {
		rootMenu.handleMenuItemClick({
			index: props.index,
			indexPath: indexPath.value,
			route: props.route,
		});

		emit('click', item);
	}
};

onMounted((): void => {
	subMenu.addSubMenu(item);
	rootMenu.addMenuItem(item);
});

onBeforeUnmount((): void => {
	subMenu.removeSubMenu(item);
	rootMenu.removeMenuItem(item);
});
</script>
