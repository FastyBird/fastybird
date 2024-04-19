<template>
	<fb-popper-trigger
		:id="id"
		:virtual-ref="props.virtualRef"
		:open="open"
		:virtual-triggering="props.virtualTriggering"
		:class="ns.e('trigger')"
		@blur="onBlur"
		@click="onClick"
		@contextmenu="onContextMenu"
		@focus="onFocus"
		@mouseenter="onMouseenter"
		@mouseleave="onMouseleave"
		@keydown="onKeydown"
	>
		<slot />
	</fb-popper-trigger>
</template>

<script lang="ts" setup>
import { inject, ref, toRef, unref } from 'vue';

import { composeEventHandlers } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbPopperTrigger } from '../../popper';
import { TOOLTIP_INJECTION_KEY } from './constants';
import { useTooltipTriggerProps } from './trigger';
import { whenTrigger } from './utils';

import type { OnlyChildExpose } from '../../slot';

defineOptions({
	name: 'FbTooltipTrigger',
});

const props = defineProps(useTooltipTriggerProps);

const ns = useNamespace('tooltip');

const { controlled, id, open, onOpen, onClose, onToggle } = inject(TOOLTIP_INJECTION_KEY, undefined)!;

const triggerRef = ref<OnlyChildExpose | null>(null);

const stopWhenControlledOrDisabled = (): boolean => {
	return !!(unref(controlled) || props.disabled);
};

const trigger = toRef(props, 'trigger');

const onMouseenter = composeEventHandlers(stopWhenControlledOrDisabled, whenTrigger(trigger, 'hover', onOpen));
const onMouseleave = composeEventHandlers(stopWhenControlledOrDisabled, whenTrigger(trigger, 'hover', onClose));

const onClick = composeEventHandlers(
	stopWhenControlledOrDisabled,
	whenTrigger(trigger, 'click', (e) => {
		// distinguish left click
		if ((e as MouseEvent).button === 0) {
			onToggle(e);
		}
	})
);

const onFocus = composeEventHandlers(stopWhenControlledOrDisabled, whenTrigger(trigger, 'focus', onOpen));
const onBlur = composeEventHandlers(stopWhenControlledOrDisabled, whenTrigger(trigger, 'focus', onClose));

const onContextMenu = composeEventHandlers(
	stopWhenControlledOrDisabled,
	whenTrigger(trigger, 'contextmenu', (e: Event) => {
		e.preventDefault();
		onToggle(e);
	})
);

const onKeydown = composeEventHandlers(stopWhenControlledOrDisabled, (e: Event) => {
	const { code } = e as KeyboardEvent;

	if (props.triggerKeys.includes(code)) {
		e.preventDefault();
		onToggle(e);
	}
});

defineExpose({
	/** @description trigger element */
	triggerRef,
});
</script>
