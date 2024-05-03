<template>
	<li
		:class="[nsSubMenu.b(), nsSubMenu.is('active', active), nsSubMenu.is('opened', opened), nsSubMenu.is('disabled', props.disabled)]"
		:aria-haspopup="true"
		:aria-expanded="opened"
		role="menuitem"
		@mouseenter="handleMouseenter"
		@mouseleave="() => handleMouseleave(true)"
		@focus="handleMouseenter"
	>
		<fb-tooltip
			v-if="rootMenu.isMenuPopup"
			ref="vPopper"
			:visible="opened"
			:effect="EffectTypes.LIGHT"
			:pure="true"
			:offset="subMenuPopperOffset"
			:show-arrow="false"
			:persistent="true"
			:popper-class="subMenuPopperClass"
			:placement="currentPlacement"
			:teleported="appendToBody"
			:fallback-placements="fallbackPlacements"
			:transition="menuTransitionName"
			:gpu-acceleration="false"
		>
			<template #content>
				<div
					:class="[nsMenu.m(mode), nsMenu.m('popup-container'), subMenuPopperClass]"
					@mouseenter="(evt: MouseEvent) => handleMouseenter(evt, 100)"
					@mouseleave="() => handleMouseleave(true)"
					@focus="(evt: FocusEvent) => handleMouseenter(evt, 100)"
				>
					<ul :class="[nsMenu.b(), nsMenu.m('popup'), nsMenu.m(`popup-${currentPlacement}`)]" :style="ulStyle">
						<slot />
					</ul>
				</div>
			</template>

			<div :class="[nsSubMenu.e('title')]" @click="handleClick">
				<slot name="title" />

				<fb-icon
					:class="[nsSubMenu.e('icon-arrow')]"
					:style="{
						transform: opened
							? (props.expandCloseIcon && props.expandOpenIcon) || (props.collapseCloseIcon && props.collapseOpenIcon && rootMenu.props.collapse)
								? 'none'
								: 'rotateZ(180deg)'
							: 'none',
					}"
				>
					<component :is="subMenuTitleIcon" />
				</fb-icon>
			</div>
		</fb-tooltip>

		<template v-else>
			<div ref="verticalTitleRef" :class="[nsSubMenu.e('title')]" @click="handleClick">
				<slot name="title" />

				<fb-icon
					:class="[nsSubMenu.e('icon-arrow')]"
					:style="{
						transform: opened
							? (props.expandCloseIcon && props.expandOpenIcon) || (props.collapseCloseIcon && props.collapseOpenIcon && rootMenu.props.collapse)
								? 'none'
								: 'rotateZ(180deg)'
							: 'none',
					}"
				>
					<component :is="subMenuTitleIcon" />
				</fb-icon>
			</div>

			<fb-collapse-transition v-show="opened">
				<ul role="menu" :class="[nsMenu.b(), nsMenu.m('inline')]" :style="ulStyle">
					<slot />
				</ul>
			</fb-collapse-transition>
		</template>
	</li>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, inject, onBeforeUnmount, onMounted, provide, reactive, ref, watch } from 'vue';

import { useTimeoutFn } from '@vueuse/core';
import { throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { FasChevronDown, FasChevronRight } from '@fastybird/web-ui-icons';
import { EffectTypes } from '@fastybird/web-ui-constants';
import { MenuContext, menuContextKey, SubMenuContext, subMenuContextKey } from './constants';

import { useMenu, useMenuCssVar } from './hooks';
import { FbTooltip } from '../../tooltip';
import { FbIcon } from '../../icon';
import { FbCollapseTransition } from '../../collapse-transition';
import { MenuModeTypes, MenuTriggerTypes } from './menu';
import { subMenuProps } from './sub-menu';

import type { Component } from 'vue';
import type { Placement } from '../../popper';

defineOptions({
	name: 'FbSubMenu',
});

const props = defineProps(subMenuProps);

const instance = getCurrentInstance()!;

const { indexPath, parentMenu } = useMenu(
	instance,
	computed(() => props.index)
);

const nsMenu = useNamespace('menu');
const nsSubMenu = useNamespace('sub-menu');

const rootMenu = inject(menuContextKey);

if (!rootMenu) {
	throwError(instance!.type.name!, 'can not inject root menu');
}

const subMenu = inject(subMenuContextKey);

if (!subMenu) {
	throwError(instance!.type.name!, 'can not inject sub menu');
}

const items = ref<MenuContext['items']>({});
const subMenus = ref<MenuContext['subMenus']>({});

let timeout: (() => void) | undefined;

const mouseInChild = ref<boolean>(false);
const verticalTitleRef = ref<HTMLDivElement | undefined>();
const vPopper = ref<InstanceType<typeof FbTooltip> | null>(null);

const currentPlacement = computed<Placement>(
	(): Placement => (mode.value === MenuModeTypes.HORIZONTAL && isFirstLevel.value ? 'bottom-start' : 'right-start')
);

const subMenuTitleIcon = computed<string | Component>((): string | Component => {
	return (mode.value === MenuModeTypes.HORIZONTAL && isFirstLevel.value) || (mode.value === MenuModeTypes.VERTICAL && !rootMenu.props.collapse)
		? props.expandCloseIcon && props.expandOpenIcon
			? opened.value
				? props.expandOpenIcon
				: props.expandCloseIcon
			: FasChevronDown
		: props.collapseCloseIcon && props.collapseOpenIcon
			? opened.value
				? props.collapseOpenIcon
				: props.collapseCloseIcon
			: FasChevronRight;
});

const isFirstLevel = computed<boolean>((): boolean => {
	return subMenu.level === 0;
});

const appendToBody = computed<boolean>((): boolean => {
	const value = props.teleported;

	return value === undefined ? isFirstLevel.value : value;
});

const menuTransitionName = computed<string>((): string =>
	rootMenu.props.collapse ? `${nsMenu.namespace.value}-zoom-in-left` : `${nsMenu.namespace.value}-zoom-in-top`
);

const fallbackPlacements = computed<Placement[]>((): Placement[] =>
	mode.value === MenuModeTypes.HORIZONTAL && isFirstLevel.value
		? ['bottom-start', 'bottom-end', 'top-start', 'top-end', 'right-start', 'left-start']
		: ['right-start', 'right', 'right-end', 'left-start', 'bottom-start', 'bottom-end', 'top-start', 'top-end']
);

const opened = computed<boolean>((): boolean => rootMenu.openedMenus.includes(props.index));

const active = computed<boolean>((): boolean => {
	let isActive = false;

	Object.values(items.value).forEach((item) => {
		if (item.active) {
			isActive = true;
		}
	});

	Object.values(subMenus.value).forEach((subItem) => {
		if (subItem.active) {
			isActive = true;
		}
	});

	return isActive;
});

const mode = computed<string>((): string => rootMenu.props.mode);

const item = reactive({
	index: props.index,
	indexPath,
	active,
});

const ulStyle = useMenuCssVar(rootMenu.props, subMenu.level + 1);

const subMenuPopperOffset = computed<number>((): number => {
	return props.popperOffset ?? rootMenu.props.popperOffset;
});

const subMenuPopperClass = computed<string | undefined>((): string | undefined => {
	return props.popperClass ?? rootMenu.props.popperClass;
});

const subMenuShowTimeout = computed<number>((): number => {
	return props.showTimeout ?? rootMenu.props.showTimeout;
});

const subMenuHideTimeout = computed<number>((): number => {
	return props.hideTimeout ?? rootMenu.props.hideTimeout;
});

// methods
const doDestroy = (): void => vPopper.value?.popperRef?.popperInstanceRef?.destroy();

const handleCollapseToggle = (value: boolean): void => {
	if (!value) {
		doDestroy();
	}
};

const handleClick = (): void => {
	if (
		(rootMenu.props.menuTrigger === MenuTriggerTypes.HOVER && rootMenu.props.mode === MenuModeTypes.HORIZONTAL) ||
		(rootMenu.props.collapse && rootMenu.props.mode === MenuModeTypes.VERTICAL) ||
		props.disabled
	) {
		return;
	}

	rootMenu.handleSubMenuClick({
		index: props.index,
		indexPath: indexPath.value,
		active: active.value,
	});
};

const handleMouseenter = (event: MouseEvent | FocusEvent, showTimeout = subMenuShowTimeout.value): void => {
	if (event.type === 'focus') {
		return;
	}

	if (
		(rootMenu.props.menuTrigger === MenuTriggerTypes.CLICK && rootMenu.props.mode === MenuModeTypes.HORIZONTAL) ||
		(!rootMenu.props.collapse && rootMenu.props.mode === MenuModeTypes.VERTICAL) ||
		props.disabled
	) {
		subMenu.mouseInChild.value = true;

		return;
	}

	subMenu.mouseInChild.value = true;

	timeout?.();

	({ stop: timeout } = useTimeoutFn(() => {
		rootMenu.openMenu(props.index, indexPath.value);
	}, showTimeout));

	if (appendToBody.value) {
		parentMenu.value.vnode.el?.dispatchEvent(new MouseEvent('mouseenter'));
	}
};

const handleMouseleave = (deepDispatch = false): void => {
	if (
		(rootMenu.props.menuTrigger === MenuTriggerTypes.CLICK && rootMenu.props.mode === MenuModeTypes.HORIZONTAL) ||
		(!rootMenu.props.collapse && rootMenu.props.mode === MenuModeTypes.VERTICAL)
	) {
		subMenu.mouseInChild.value = false;
		return;
	}

	timeout?.();

	subMenu.mouseInChild.value = false;

	({ stop: timeout } = useTimeoutFn(() => !mouseInChild.value && rootMenu.closeMenu(props.index, indexPath.value), subMenuHideTimeout.value));

	if (appendToBody.value && deepDispatch) {
		subMenu.handleMouseleave?.(true);
	}
};

const addSubMenu: SubMenuContext['addSubMenu'] = (item) => {
	subMenus.value[item.index] = item;
};

const removeSubMenu: SubMenuContext['removeSubMenu'] = (item) => {
	delete subMenus.value[item.index];
};

onMounted(() => {
	rootMenu.addSubMenu(item);
	subMenu.addSubMenu(item);
});

onBeforeUnmount(() => {
	subMenu.removeSubMenu(item);
	rootMenu.removeSubMenu(item);
});

watch(
	() => rootMenu.props.collapse,
	(value) => handleCollapseToggle(Boolean(value))
);

provide(subMenuContextKey, {
	addSubMenu,
	removeSubMenu,
	handleMouseleave,
	mouseInChild,
	level: subMenu.level + 1,
});

defineExpose({
	/** @description */
	opened,
});
</script>
