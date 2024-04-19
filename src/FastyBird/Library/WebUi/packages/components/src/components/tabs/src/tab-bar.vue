<template>
	<div
		ref="barRef"
		:class="[ns.e('active-bar'), ns.is(rootTabs.props.tabPosition)]"
		:style="barStyle"
	/>
</template>

<script lang="ts" setup>
import { getCurrentInstance, inject, nextTick, ref, watch } from 'vue';

import { useResizeObserver } from '@vueuse/core';
import { capitalize, throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { tabsRootContextKey } from './constants';
import { tabBarProps } from './tab-bar';

import type { CSSProperties } from 'vue';
import { TabPositionTypes } from './tabs';

defineOptions({
	name: 'FbTabBar',
});

const props = defineProps(tabBarProps);

const instance = getCurrentInstance()!;

const rootTabs = inject(tabsRootContextKey);

if (!rootTabs) {
	throwError(instance!.type.name!, '<fb-tabs><fb-tab-bar /></fb-tabs>');
}

const ns = useNamespace('tabs');

const barRef = ref<HTMLDivElement>();
const barStyle = ref<CSSProperties>();

const getBarStyle = (): CSSProperties => {
	let offset = 0;
	let tabSize = 0;

	const sizeName = [TabPositionTypes.TOP, TabPositionTypes.BOTTOM].includes(rootTabs.props.tabPosition) ? 'width' : 'height';
	const sizeDir = sizeName === 'width' ? 'x' : 'y';
	const position = sizeDir === 'x' ? 'left' : 'top';

	if (props.activeItemRef) {
		offset = props.activeItemRef[`offset${capitalize(position)}`];
		tabSize = props.activeItemRef[`client${capitalize(sizeName)}`];

		const tabStyles = window.getComputedStyle(props.activeItemRef);

		if (sizeName === 'width') {
			if (props.tabs.length > 1) {
				tabSize -= Number.parseFloat(tabStyles.paddingLeft) + Number.parseFloat(tabStyles.paddingRight);
			}

			offset += Number.parseFloat(tabStyles.paddingLeft);
		}
	}

	return {
		[sizeName]: `${tabSize}px`,
		transform: `translate${capitalize(sizeDir)}(${offset}px)`,
	};
};

const update = () => (barStyle.value = getBarStyle());

watch(
	(): HTMLElement | undefined => props.activeItemRef,
	async (): Promise<void> => {
		await nextTick();
		update();
	},
	{ immediate: true }
);

useResizeObserver(barRef, () => update());

defineExpose({
	/** @description tab root html element */
	ref: barRef,
	/** @description method to manually update tab bar style */
	update,
});
</script>
