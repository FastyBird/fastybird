<template>
	<div
		:class="[
			ns.b(),
			ns.m(props.tabPosition),
			{ [ns.m('card')]: props.type === TabNavTypeTypes.CARD, [ns.m('border-card')]: props.type === TabNavTypeTypes.BORDER_CARD },
		]"
	>
		<div :class="[ns.e('header'), ns.is(props.tabPosition)]">
			<span
				v-if="props.editable || props.addable"
				:class="ns.e('new-tab')"
				:tabindex="0"
				@click="handleTabAdd"
				@keydown="
					(ev: KeyboardEvent) => {
						if (ev.code === EVENT_CODE.enter) handleTabAdd();
					}
				"
			>
				<template v-if="'add-icon' in $slots">
					<slot name="add-icon" />
				</template>

				<fb-icon v-else :class="ns.is('icon-plus')">
					<fas-plus />
				</fb-icon>
			</span>

			<fb-tab-nav
				ref="navRef"
				:current-name="currentName"
				:editable="props.editable"
				:type="props.type"
				:panes="panes"
				:stretch="props.stretch"
				@tab-click="handleTabClick"
				@tab-remove="handleTabRemove"
			/>
		</div>

		<div :class="ns.e('content')">
			<slot />
		</div>
	</div>
</template>

<script lang="ts" setup>
import { getCurrentInstance, nextTick, provide, ref, watch } from 'vue';

import { isUndefined } from '@fastybird/web-ui-utils';
import { EVENT_CODE, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { useNamespace, useOrderedChildren } from '@fastybird/web-ui-hooks';
import { FasPlus } from '@fastybird/web-ui-icons';

import { FbIcon } from '../../icon';
import { tabsRootContextKey } from './constants';
import { TabNavTypeTypes } from './tab-nav';
import FbTabNav from './tab-nav.vue';
import { tabsEmits, tabsProps } from './tabs';

import type { FbTabNavInstance } from './instance';
import type { TabsPaneContext } from './constants';
import type { TabPaneName } from './tabs';

defineOptions({
	name: 'FbTabs',
});

const props = defineProps(tabsProps);
const emit = defineEmits(tabsEmits);

const ns = useNamespace('tabs');

const {
	children: panes,
	addChild: registerPane,
	removeChild: unregisterPane,
} = useOrderedChildren<TabsPaneContext>(getCurrentInstance()!, 'FbTabPane');

const navRef = ref<FbTabNavInstance>();
const currentName = ref<TabPaneName>(props.modelValue ?? '0');

const setCurrentName = async (value?: TabPaneName, trigger = false): Promise<void> => {
	// should do nothing.
	if (currentName.value === value || isUndefined(value)) {
		return;
	}

	try {
		const canLeave = await props.beforeLeave?.(value, currentName.value);

		if (canLeave !== false) {
			currentName.value = value;

			if (trigger) {
				emit(UPDATE_MODEL_EVENT, value);
				emit('tabChange', value);
			}

			navRef.value?.removeFocus?.();
		}
	} catch {
		// Just ignore
	}
};

const handleTabClick = (tab: TabsPaneContext, tabName: TabPaneName, event: Event): void => {
	if (tab.props.disabled) {
		return;
	}

	setCurrentName(tabName, true);

	emit('tabClick', tab, event);
};

const handleTabRemove = (pane: TabsPaneContext, ev: Event): void => {
	if (pane.props.disabled || isUndefined(pane.props.name)) {
		return;
	}

	ev.stopPropagation();

	emit('edit', pane.props.name, 'remove');
	emit('tabRemove', pane.props.name);
};

const handleTabAdd = (): void => {
	emit('edit', undefined, 'add');
	emit('tabAdd');
};

watch(
	() => props.modelValue,
	(modelValue) => setCurrentName(modelValue)
);

watch(currentName, async () => {
	await nextTick();

	navRef.value?.scrollToActiveTab();
});

provide(tabsRootContextKey, {
	props,
	currentName,
	registerPane,
	unregisterPane,
});

defineExpose({
	/** @description */
	currentName,
});
</script>
