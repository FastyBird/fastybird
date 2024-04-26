<template>
	<div
		:id="`tab-${tabName}`"
		ref="tabNavItemRef"
		:key="`tab-${uid}`"
		:class="[
			ns.e('item'),
			ns.is(rootTabs.props.tabPosition),
			ns.is('active', props.pane.active),
			ns.is('disabled', disabled),
			ns.is('closable', closable),
			ns.is('focus', props.isFocus),
		]"
		:aria-controls="`pane-${tabName}`"
		:aria-selected="props.pane.active"
		:tabindex="!disabled && props.pane.active ? 0 : -1"
		role="tab"
		@focus="() => emit('focus')"
		@blur="() => emit('blur')"
		@click="handleClick"
		@keydown="handleRemove"
	>
		<template v-if="customLabel">
			<template v-for="(label, index) in customLabel" :key="index">
				<component :is="label" />
			</template>
		</template>
		<template v-else>
			{{ props.pane.props.label }}
		</template>

		<fb-icon v-if="closable" class="is-icon-close" @click="handleRemove">
			<close />
		</fb-icon>
	</div>
</template>

<script lang="ts" setup>
import { getCurrentInstance, inject, ref } from 'vue';

import { throwError } from '@fastybird/web-ui-utils';
import { EVENT_CODE } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { CloseComponentsMap } from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import { tabsRootContextKey } from './constants';
import { tabNavItemEmits, tabNavItemProps } from './tab-nav-item';

defineOptions({
	name: 'FbTabNavItem',
});

const props = defineProps(tabNavItemProps);
const emit = defineEmits(tabNavItemEmits);
const instance = getCurrentInstance();

const ns = useNamespace('tabs');

const { close } = CloseComponentsMap;

const rootTabs = inject(tabsRootContextKey);

if (!rootTabs) {
	throwError(instance!.type.name!, `<fb-tabs><fb-tab-nav /></fb-tabs>`);
}

const tabNavItemRef = ref<HTMLElement | undefined>();

const uid = props.pane.uid;
const disabled = props.pane.props.disabled;
const tabName = props.pane.props.name ?? props.pane.index ?? `${props.index}`;
const closable = !disabled && (props.pane.isClosable || props.editable);

const customLabel = props.pane.slots.label?.();

const handleClick = (ev: MouseEvent): void => {
	emit('blur');
	emit('click', ev);
};

const handleRemove = (ev: MouseEvent | KeyboardEvent): void => {
	if (disabled || !closable) {
		return;
	}

	if (!('code' in ev) || ev.code === EVENT_CODE.delete || ev.code === EVENT_CODE.backspace) {
		emit('remove', ev);
	}
};
</script>
