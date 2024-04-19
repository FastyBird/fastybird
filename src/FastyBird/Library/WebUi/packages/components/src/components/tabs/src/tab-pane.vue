<template>
	<div
		v-if="shouldBeRender"
		v-show="active"
		:id="`pane-${paneName}`"
		:class="ns.b()"
		role="tabpanel"
		:aria-hidden="!active"
		:aria-labelledby="`tab-${paneName}`"
	>
		<slot />
	</div>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, inject, onMounted, onUnmounted, reactive, ref, useSlots, watch } from 'vue';

import { eagerComputed } from '@vueuse/core';
import { throwError } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { tabsRootContextKey } from './constants';
import { tabPaneProps } from './tab-pane';

defineOptions({
	name: 'FbTabPane',
});

const props = defineProps(tabPaneProps);

const instance = getCurrentInstance()!;
const slots = useSlots();

const tabsRoot = inject(tabsRootContextKey);

if (!tabsRoot) {
	throwError(instance!.type.name!, 'usage: <fb-tabs><fb-tab-pane /></fb-tabs/>');
}

const ns = useNamespace('tab-pane');

const index = ref<string | undefined>();
const isClosable = computed<boolean>((): boolean => props.closable || tabsRoot.props.closable);
const active = eagerComputed<boolean>((): boolean => tabsRoot.currentName.value === (props.name ?? index.value));
const loaded = ref<boolean>(active.value);
const paneName = computed<string | number | undefined>((): string | number | undefined => props.name ?? index.value);
const shouldBeRender = eagerComputed<boolean>((): boolean => !props.lazy || loaded.value || active.value);

watch(active, (val: boolean): void => {
	if (val) {
		loaded.value = true;
	}
});

const pane = reactive({
	uid: instance.uid,
	slots,
	props,
	paneName,
	active,
	index,
	isClosable,
});

onMounted((): void => {
	tabsRoot.registerPane(pane);
});

onUnmounted((): void => {
	tabsRoot.unregisterPane(pane.uid);
});
</script>
