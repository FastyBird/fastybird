<template>
	<div
		ref="elRef"
		:class="[ns.e('nav-wrap'), ns.is('scrollable', !!scrollable), ns.is(rootTabs.props.tabPosition)]"
	>
		<template v-if="scrollable">
			<span
				:class="[ns.e('nav-prev'), ns.is('disabled', !scrollable.prev)]"
				@click="scrollPrev"
			>
				<fb-icon>
					<arrow-left />
				</fb-icon>
			</span>

			<span
				:class="[ns.e('nav-next'), ns.is('disabled', !scrollable.next)]"
				@click="scrollNext"
			>
				<fb-icon>
					<arrow-right />
				</fb-icon>
			</span>
		</template>

		<div
			ref="navScrollRef"
			:class="ns.e('nav-scroll')"
		>
			<div
				ref="navRef"
				:class="[
					ns.e('nav'),
					ns.is(rootTabs.props.tabPosition),
					ns.is('stretch', props.stretch && [TabPositionTypes.TOP, TabPositionTypes.BOTTOM].includes(rootTabs.props.tabPosition)),
				]"
				:style="navStyle"
				role="tablist"
				@keydown="changeTab"
			>
				<fb-tab-bar
					v-if="!props.type"
					ref="tabBarRef"
					:tabs="props.panes"
					:active-item-ref="activeItemRef"
				/>

				<fb-tab-nav-item
					v-for="(pane, index) in props.panes"
					:ref="(el) => checkMenuItem(el as ComponentPublicInstance, pane)"
					:index="index"
					:pane="pane"
					:is-focus="isFocus"
					:editable="props.editable"
					@focus="() => setFocus()"
					@blur="() => removeFocus()"
					@click="(ev: Event) => emit('tabClick', pane, pane.props.name ?? pane.index ?? `${index}`, ev)"
					@remove="(ev: Event) => emit('tabRemove', pane, ev)"
				/>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { ComponentPublicInstance, computed, getCurrentInstance, inject, nextTick, onMounted, onUpdated, ref, watch } from 'vue';

import { useDocumentVisibility, useResizeObserver, useWindowFocus } from '@vueuse/core';
import { capitalize, throwError } from '@fastybird/web-ui-utils';
import { EVENT_CODE } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { ArrowComponentsMap } from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import FbTabBar from './tab-bar.vue';
import { type TabsPaneContext, tabsRootContextKey } from './constants';
import { Scrollable, tabNavEmits, tabNavProps } from './tab-nav';

import type { CSSProperties } from 'vue';
import FbTabNavItem from './tab-nav-item.vue';
import { TabPositionTypes } from './tabs';

defineOptions({
	name: 'FbTabNav',
});

const props = defineProps(tabNavProps);
const emit = defineEmits(tabNavEmits);

const instance = getCurrentInstance();

const { left: ArrowLeft, right: ArrowRight } = ArrowComponentsMap;

const vm = getCurrentInstance()!;

const rootTabs = inject(tabsRootContextKey);

if (!rootTabs) {
	throwError(instance!.type.name!, `<fb-tabs><fb-tab-nav /></fb-tabs>`);
}

const ns = useNamespace('tabs');
const visibility = useDocumentVisibility();
const focused = useWindowFocus();

const navScrollRef = ref<HTMLDivElement | undefined>();
const navRef = ref<HTMLDivElement | undefined>();
const navItemsRef = ref<ComponentPublicInstance[]>([]);
const elRef = ref<HTMLDivElement | undefined>();

const tabBarRef = ref<InstanceType<typeof FbTabBar>>();

const scrollable = ref<false | Scrollable>(false);
const navOffset = ref(0);
const isFocus = ref(false);
const focusable = ref(true);

const sizeName = computed(() => ([TabPositionTypes.TOP, TabPositionTypes.BOTTOM].includes(rootTabs.props.tabPosition) ? 'width' : 'height'));

const navStyle = computed<CSSProperties>(() => {
	const dir = sizeName.value === 'width' ? 'X' : 'Y';

	return {
		transform: `translate${dir}(-${navOffset.value}px)`,
	};
});

const checkMenuItem = (el: ComponentPublicInstance, pane: TabsPaneContext): void => {
	navItemsRef.value[pane.uid] = el;
};

const activeItemRef = computed<HTMLElement | undefined>((): HTMLElement | undefined => {
	for (const pane of props.panes) {
		if (pane.active && pane.uid in navItemsRef.value) {
			return navItemsRef.value[pane.uid].$el;
		}
	}
});

const scrollPrev = () => {
	if (!navScrollRef.value) {
		return;
	}

	const containerSize = navScrollRef.value[`offset${capitalize(sizeName.value)}`];
	const currentOffset = navOffset.value;

	if (!currentOffset) {
		return;
	}

	navOffset.value = currentOffset > containerSize ? currentOffset - containerSize : 0;
};

const scrollNext = () => {
	if (!navScrollRef.value || !navRef.value) {
		return;
	}

	const navSize = navRef.value[`offset${capitalize(sizeName.value)}`];
	const containerSize = navScrollRef.value[`offset${capitalize(sizeName.value)}`];
	const currentOffset = navOffset.value;

	if (navSize - currentOffset <= containerSize) {
		return;
	}

	navOffset.value = navSize - currentOffset > containerSize * 2 ? currentOffset + containerSize : navSize - containerSize;
};

const scrollToActiveTab = async () => {
	const nav = navRef.value;

	if (!scrollable.value || !elRef.value || !navScrollRef.value || !nav) {
		return;
	}

	await nextTick();

	const activeTab = elRef.value.querySelector('.is-active');

	if (!activeTab) {
		return;
	}

	const navScroll = navScrollRef.value;
	const isHorizontal = [TabPositionTypes.TOP, TabPositionTypes.BOTTOM].includes(rootTabs.props.tabPosition);
	const activeTabBounding = activeTab.getBoundingClientRect();
	const navScrollBounding = navScroll.getBoundingClientRect();
	const maxOffset = isHorizontal ? nav.offsetWidth - navScrollBounding.width : nav.offsetHeight - navScrollBounding.height;
	const currentOffset = navOffset.value;

	let newOffset = currentOffset;

	if (isHorizontal) {
		if (activeTabBounding.left < navScrollBounding.left) {
			newOffset = currentOffset - (navScrollBounding.left - activeTabBounding.left);
		}

		if (activeTabBounding.right > navScrollBounding.right) {
			newOffset = currentOffset + activeTabBounding.right - navScrollBounding.right;
		}
	} else {
		if (activeTabBounding.top < navScrollBounding.top) {
			newOffset = currentOffset - (navScrollBounding.top - activeTabBounding.top);
		}

		if (activeTabBounding.bottom > navScrollBounding.bottom) {
			newOffset = currentOffset + (activeTabBounding.bottom - navScrollBounding.bottom);
		}
	}

	newOffset = Math.max(newOffset, 0);
	navOffset.value = Math.min(newOffset, maxOffset);
};

const update = () => {
	if (!navRef.value || !navScrollRef.value) {
		return;
	}

	props.stretch && tabBarRef.value?.update();

	const navSize = navRef.value[`offset${capitalize(sizeName.value)}`];
	const containerSize = navScrollRef.value[`offset${capitalize(sizeName.value)}`];
	const currentOffset = navOffset.value;

	if (containerSize < navSize) {
		scrollable.value = scrollable.value || {};
		scrollable.value.prev = currentOffset;
		scrollable.value.next = currentOffset + containerSize < navSize;
		if (navSize - currentOffset < containerSize) {
			navOffset.value = navSize - containerSize;
		}
	} else {
		scrollable.value = false;
		if (currentOffset > 0) {
			navOffset.value = 0;
		}
	}
};

const changeTab = (e: KeyboardEvent) => {
	const code = e.code;

	const { up, down, left, right } = EVENT_CODE;

	if (![up, down, left, right].includes(code)) {
		return;
	}

	const tabList = Array.from((e.currentTarget as HTMLDivElement).querySelectorAll<HTMLDivElement>('[role=tab]:not(.is-disabled)'));
	const currentIndex = tabList.indexOf(e.target as HTMLDivElement);

	let nextIndex: number;

	if (code === left || code === up) {
		// left
		if (currentIndex === 0) {
			// first
			nextIndex = tabList.length - 1;
		} else {
			nextIndex = currentIndex - 1;
		}
	} else {
		// right
		if (currentIndex < tabList.length - 1) {
			// not last
			nextIndex = currentIndex + 1;
		} else {
			nextIndex = 0;
		}
	}

	tabList[nextIndex].focus({ preventScroll: true }); // 改变焦点元素
	tabList[nextIndex].click(); // 选中下一个tab

	setFocus();
};

const setFocus = () => {
	if (focusable.value) {
		isFocus.value = true;
	}
};

const removeFocus = () => (isFocus.value = false);

useResizeObserver(elRef, update);

onMounted((): void => {
	setTimeout(() => scrollToActiveTab(), 0);
});

onUpdated((): void => {
	update();
});

watch(visibility, (visibility) => {
	if (visibility === 'hidden') {
		focusable.value = false;
	} else if (visibility === 'visible') {
		setTimeout(() => (focusable.value = true), 50);
	}
});

watch(focused, (focused: boolean): void => {
	if (focused) {
		setTimeout(() => (focusable.value = true), 50);
	} else {
		focusable.value = false;
	}
});

watch(
	(): TabsPaneContext[] => props.panes,
	(): void => vm.update(),
	{ flush: 'post', deep: true }
);

defineExpose({
	/** @description */
	scrollToActiveTab,
	/** @description */
	removeFocus,
});
</script>
