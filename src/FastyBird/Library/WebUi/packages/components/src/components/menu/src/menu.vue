<template>
	<fb-menu-collapse-transition v-if="props.collapseTransition && props.mode === MenuModeTypes.VERTICAL">
		<ul
			ref="menu"
			v-click-outside="handleClickOutside"
			role="menubar"
			:style="ulStyle"
			:class="{ [nsMenu.b()]: true, [nsMenu.m(props.mode)]: true, [nsMenu.m('collapse')]: props.collapse }"
		>
			<slot />
		</ul>
	</fb-menu-collapse-transition>

	<template v-else>
		<ul
			ref="menu"
			v-click-outside="handleClickOutside"
			role="menubar"
			:style="ulStyle"
			:class="{ [nsMenu.b()]: true, [nsMenu.m(props.mode)]: true, [nsMenu.m('collapse')]: props.collapse }"
		>
			<component :is="item" v-for="(item, index) in slotDefault" :key="index" />

			<fb-sub-menu
				v-if="props.mode === MenuModeTypes.HORIZONTAL && slotMore?.length"
				index="sub-menu-more"
				:class="nsSubMenu.e('hide-arrow')"
				:popper-offset="props.popperOffset"
			>
				<template #title>
					<fb-icon :class="nsSubMenu.e('icon-more')">
						<component :is="props.ellipsisIcon || FasEllipsis" />
					</fb-icon>
				</template>

				<component :is="item" v-for="(item, index) in slotMore" :key="index" />
			</fb-sub-menu>
		</ul>
	</template>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, nextTick, onMounted, provide, reactive, ref, useSlots, watch, watchEffect } from 'vue';
import { isNil } from 'lodash';

import { useResizeObserver } from '@vueuse/core';
import { flattedChildren } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { FasEllipsis } from '@fastybird/web-ui-icons';
import { ClickOutside as vClickOutside } from '@fastybird/web-ui-directives';

import { FbIcon } from '../../icon';
import { MenuContext, menuContextKey, subMenuContextKey } from './constants';
import { menuEmits, MenuModeTypes, menuProps } from './menu';
import FbSubMenu from './sub-menu.vue';
import FbMenuCollapseTransition from './menu-collapse-transition.vue';
import Menubar from './utils/menu-bar';
import { useMenuCssVar } from './hooks';

import type { VNodeArrayChildren } from 'vue';
import type { Router } from 'vue-router';
import type { UseResizeObserverReturn } from '@vueuse/core';
import type { MenuItemRegistered } from './types';

defineOptions({
	name: 'FbMenu',
});

const props = defineProps(menuProps);
const emit = defineEmits(menuEmits);
const slots = useSlots();

const instance = getCurrentInstance()!;
const router = instance.appContext.config.globalProperties.$router as Router;
const menu = ref<HTMLUListElement>();
const nsMenu = useNamespace('menu');
const nsSubMenu = useNamespace('sub-menu');

const ulStyle = useMenuCssVar(props, 0);

const sliceIndex = ref<number>(-1);

const openedMenus = ref<MenuContext['openedMenus']>(props.defaultOpeneds && !props.collapse ? props.defaultOpeneds.slice(0) : []);
const activeIndex = ref<MenuContext['activeIndex']>(props.defaultActive);
const items = ref<MenuContext['items']>({});
const subMenus = ref<MenuContext['subMenus']>({});

const isMenuPopup = computed<MenuContext['isMenuPopup']>(() => {
	return props.mode === MenuModeTypes.HORIZONTAL || (props.mode === MenuModeTypes.VERTICAL && props.collapse);
});

const initMenu = (): void => {
	const activeItem = activeIndex.value && items.value[activeIndex.value];

	if (!activeItem || props.mode === MenuModeTypes.HORIZONTAL || props.collapse) {
		return;
	}

	const indexPath = activeItem.indexPath;

	// expand all subMenus of the menu item
	indexPath.forEach((index: string): void => {
		const subMenu = subMenus.value[index];

		subMenu && openMenu(index, subMenu.indexPath);
	});
};

const openMenu: MenuContext['openMenu'] = (index: string, indexPath: string[]): void => {
	if (openedMenus.value.includes(index)) {
		return;
	}

	// collapse all menu that are not under current menu item
	if (props.uniqueOpened) {
		openedMenus.value = openedMenus.value.filter((index: string) => indexPath.includes(index));
	}

	openedMenus.value.push(index);

	emit('open', index, indexPath);
};

const close = (index: string): void => {
	const i = openedMenus.value.indexOf(index);

	if (i !== -1) {
		openedMenus.value.splice(i, 1);
	}
};

const closeMenu: MenuContext['closeMenu'] = (index: string, indexPath: string[]): void => {
	close(index);

	emit('close', index, indexPath);
};

const handleSubMenuClick: MenuContext['handleSubMenuClick'] = ({ index, indexPath }): void => {
	const isOpened = openedMenus.value.includes(index);

	if (isOpened) {
		closeMenu(index, indexPath);
	} else {
		openMenu(index, indexPath);
	}
};

const handleMenuItemClick: MenuContext['handleMenuItemClick'] = (menuItem): void => {
	if (props.mode === MenuModeTypes.HORIZONTAL || props.collapse) {
		openedMenus.value = [];
	}

	const { index, indexPath } = menuItem;

	if (isNil(index) || isNil(indexPath)) {
		return;
	}

	if (props.router && router) {
		const route = menuItem.route || index;

		const routerResult = router.push(route).then((res) => {
			if (!res) {
				activeIndex.value = index;
			}

			return res;
		});

		emit('select', index, indexPath, { index, indexPath, route }, routerResult);
	} else {
		activeIndex.value = index;
		emit('select', index, indexPath, { index, indexPath });
	}
};

const updateActiveIndex = (val: string): void => {
	const itemsInData = items.value;
	const item = itemsInData[val] || (activeIndex.value && itemsInData[activeIndex.value]) || itemsInData[props.defaultActive || ''];

	if (item) {
		activeIndex.value = item.index;
	} else {
		activeIndex.value = val;
	}
};

const calcMenuItemWidth = (menuItem: HTMLElement): number => {
	const computedStyle = getComputedStyle(menuItem);
	const marginLeft = Number.parseInt(computedStyle.marginLeft, 10);
	const marginRight = Number.parseInt(computedStyle.marginRight, 10);

	return menuItem.offsetWidth + marginLeft + marginRight || 0;
};

const calcSliceIndex = (): number => {
	if (!menu.value) {
		return -1;
	}

	const items = Array.from(menu.value?.childNodes ?? []).filter(
		(item) =>
			// remove comment type node #12634
			item.nodeName !== '#comment' && (item.nodeName !== '#text' || item.nodeValue)
	) as HTMLElement[];

	const moreItemWidth = 64;
	const computedMenuStyle = getComputedStyle(menu.value!);
	const paddingLeft = Number.parseInt(computedMenuStyle.paddingLeft, 10);
	const paddingRight = Number.parseInt(computedMenuStyle.paddingRight, 10);
	const menuWidth = menu.value!.clientWidth - paddingLeft - paddingRight;

	let calcWidth = 0;
	let sliceIndex = 0;

	items.forEach((item, index): void => {
		calcWidth += calcMenuItemWidth(item);
		if (calcWidth <= menuWidth - moreItemWidth) {
			sliceIndex = index + 1;
		}
	});

	return sliceIndex === items.length ? -1 : sliceIndex;
};

const getIndexPath = (index: string): string[] => subMenus.value[index].indexPath;

// Common computer monitor FPS is 60Hz, which means 60 redraws per second. Calculation formula: 1000ms/60 ≈ 16.67ms, In order to avoid a certain chance of repeated triggering when `resize`, set wait to 16.67 * 2 = 33.34
const debounce = (fn: () => void, wait = 33.34): (() => void) => {
	let timer: ReturnType<typeof setTimeout> | null;

	return (): void => {
		timer && clearTimeout(timer);
		timer = setTimeout((): void => {
			fn();
		}, wait);
	};
};

let isFirstTimeRender = true;

const handleResize = (): void => {
	if (sliceIndex.value === calcSliceIndex()) {
		return;
	}

	const callback = (): void => {
		sliceIndex.value = -1;

		nextTick((): void => {
			sliceIndex.value = calcSliceIndex();
		});
	};

	// execute callback directly when first time resize to avoid shaking
	isFirstTimeRender ? callback() : debounce(callback)();
	isFirstTimeRender = false;
};

watch(
	(): string | undefined => props.defaultActive,
	(currentActive: string | undefined): void => {
		if (!currentActive || !items.value[currentActive]) {
			activeIndex.value = '';

			return;
		}

		updateActiveIndex(currentActive);
	}
);

watch(
	(): boolean => props.collapse,
	(value: boolean): void => {
		if (value) {
			openedMenus.value = [];
		}
	}
);

watch(items.value, initMenu);

let resizeStopper: UseResizeObserverReturn['stop'];

watchEffect((): void => {
	if (props.mode === MenuModeTypes.HORIZONTAL && props.ellipsis) {
		resizeStopper = useResizeObserver(menu, handleResize).stop;
	} else {
		resizeStopper?.();
	}
});

const mouseInChild = ref<boolean>(false);

const addSubMenu: MenuContext['addSubMenu'] = (item: MenuItemRegistered): void => {
	subMenus.value[item.index] = item;
};

const removeSubMenu: MenuContext['removeSubMenu'] = (item: MenuItemRegistered): void => {
	delete subMenus.value[item.index];
};

const addMenuItem: MenuContext['addMenuItem'] = (item: MenuItemRegistered): void => {
	items.value[item.index] = item;
};

const removeMenuItem: MenuContext['removeMenuItem'] = (item: MenuItemRegistered): void => {
	delete items.value[item.index];
};

provide(
	menuContextKey,
	reactive({
		props,
		openedMenus,
		items,
		subMenus,
		activeIndex,
		isMenuPopup,

		addMenuItem,
		removeMenuItem,
		addSubMenu,
		removeSubMenu,
		openMenu,
		closeMenu,
		handleMenuItemClick,
		handleSubMenuClick,
	})
);

provide(subMenuContextKey, {
	addSubMenu,
	removeSubMenu,
	mouseInChild,
	level: 0,
});

onMounted((): void => {
	if (props.mode === MenuModeTypes.HORIZONTAL) {
		new Menubar(instance.vnode.el!, nsMenu.namespace.value);
	}
});

const open = (index: string): void => {
	const { indexPath } = subMenus.value[index];

	indexPath.forEach((i) => openMenu(i, indexPath));
};

defineExpose({
	/** @description */
	open,
	/** @description */
	close,
	/** @description */
	handleResize,
});

const slot: VNodeArrayChildren = slots.default?.() ?? [];

const originalSlot = flattedChildren(slot) as VNodeArrayChildren;

const slotDefault = computed<VNodeArrayChildren | undefined>((): VNodeArrayChildren | undefined => {
	if (props.mode === MenuModeTypes.HORIZONTAL && menu.value) {
		return sliceIndex.value === -1 ? originalSlot : originalSlot.slice(0, sliceIndex.value);
	}

	return originalSlot;
});

const slotMore = computed<VNodeArrayChildren | undefined>((): VNodeArrayChildren | undefined => {
	if (props.mode === MenuModeTypes.HORIZONTAL && menu.value) {
		return sliceIndex.value === -1 ? [] : originalSlot.slice(sliceIndex.value);
	}

	return undefined;
});

const handleClickOutside = (): void => {
	if (!props.closeOnClickOutside) {
		return;
	}

	if (!openedMenus.value.length) {
		return;
	}

	if (!mouseInChild.value) {
		openedMenus.value.forEach((openedMenu) => emit('close', openedMenu, getIndexPath(openedMenu)));

		openedMenus.value = [];
	}
};
</script>
