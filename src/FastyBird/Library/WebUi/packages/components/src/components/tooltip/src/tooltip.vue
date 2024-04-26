<template>
	<fb-popper ref="popperRef" :role="props.role">
		<fb-tooltip-trigger
			:disabled="props.disabled"
			:trigger="props.trigger"
			:trigger-keys="props.triggerKeys"
			:virtual-ref="props.virtualRef"
			:virtual-triggering="props.virtualTriggering"
		>
			<slot v-if="'default' in $slots" />
		</fb-tooltip-trigger>

		<fb-tooltip-content
			ref="contentRef"
			:aria-label="props.ariaLabel"
			:boundaries-padding="props.boundariesPadding"
			:content="props.content"
			:disabled="props.disabled"
			:effect="props.effect"
			:enterable="props.enterable"
			:fallback-placements="props.fallbackPlacements"
			:hide-after="props.hideAfter"
			:gpu-acceleration="props.gpuAcceleration"
			:offset="props.offset"
			:persistent="props.persistent"
			:popper-class="props.popperClass"
			:popper-style="props.popperStyle"
			:placement="props.placement"
			:popper-options="props.popperOptions"
			:pure="props.pure"
			:raw-content="props.rawContent"
			:reference-el="props.referenceEl"
			:trigger-target-el="props.triggerTargetEl"
			:show-after="props.showAfter"
			:strategy="props.strategy"
			:teleported="props.teleported"
			:transition="props.transition"
			:virtual-triggering="props.virtualTriggering"
			:z-index="props.zIndex"
			:append-to="props.appendTo"
		>
			<slot name="content">
				<span v-if="props.rawContent" v-html="props.content" />
				<span v-else>{{ props.content }}</span>
			</slot>

			<fb-popper-arrow v-if="props.showArrow" :arrow-offset="props.arrowOffset" />
		</fb-tooltip-content>
	</fb-popper>
</template>

<script lang="ts" setup>
import { computed, onDeactivated, provide, readonly, ref, toRef, unref, watch } from 'vue';

import { isBoolean } from '@fastybird/web-ui-utils';
import { useDelayedToggle, useId, usePopperContainer } from '@fastybird/web-ui-hooks';

import { FbPopper, FbPopperArrow } from '../../popper';
import { TOOLTIP_INJECTION_KEY } from './constants';
import { TooltipContentInstance } from './content';
import { tooltipEmits, useTooltipModelToggle, useTooltipProps } from './tooltip';
import FbTooltipContent from './content.vue';
import FbTooltipTrigger from './trigger.vue';

import type { PopperInstance } from '../../popper';

defineOptions({
	name: 'FbTooltip',
});

const props = defineProps(useTooltipProps);
const emit = defineEmits(tooltipEmits);

usePopperContainer();

const id = useId();
const popperRef = ref<PopperInstance | undefined>();
const contentRef = ref<TooltipContentInstance | undefined>();

const updatePopper = (): void => {
	const popperComponent = unref(popperRef);

	if (popperComponent) {
		popperComponent.popperInstanceRef?.update();
	}
};

const open = ref<boolean>(false);
const toggleReason = ref<Event | undefined>();

const { show, hide, hasUpdateHandler } = useTooltipModelToggle({
	indicator: open,
	toggleReason,
});

const { onOpen, onClose } = useDelayedToggle({
	showAfter: toRef(props, 'showAfter'),
	hideAfter: toRef(props, 'hideAfter'),
	autoClose: toRef(props, 'autoClose'),
	open: show,
	close: hide,
});

const controlled = computed<boolean>((): boolean => isBoolean(props.visible) && !hasUpdateHandler.value);

provide(TOOLTIP_INJECTION_KEY, {
	controlled,
	id,
	open: readonly(open),
	trigger: toRef(props, 'trigger'),
	onOpen: (event?: Event): void => {
		onOpen(event);
	},
	onClose: (event?: Event): void => {
		onClose(event);
	},
	onToggle: (event?: Event): void => {
		if (unref(open)) {
			onClose(event);
		} else {
			onOpen(event);
		}
	},
	onShow: (): void => {
		emit('show', toggleReason.value);
	},
	onHide: (): void => {
		emit('hide', toggleReason.value);
	},
	onBeforeShow: (): void => {
		emit('before-show', toggleReason.value);
	},
	onBeforeHide: (): void => {
		emit('before-hide', toggleReason.value);
	},
	updatePopper,
});

watch(
	(): boolean => props.disabled,
	(disabled: boolean): void => {
		if (disabled && open.value) {
			open.value = false;
		}
	}
);

const isFocusInsideContent = (event?: FocusEvent): boolean | undefined => {
	const popperContent: HTMLElement | undefined = contentRef.value?.contentRef?.popperContentRef;
	const activeElement = (event?.relatedTarget as Node) || document.activeElement;

	return popperContent && popperContent.contains(activeElement);
};

onDeactivated((): boolean => {
	hide();
	return open.value;
});

defineExpose({
	/** @description fb-popper component instance */
	popperRef,
	/** @description fb-tooltip-content component instance */
	contentRef,
	/** @description validate current focus event is trigger inside fb-tooltip-content */
	isFocusInsideContent,
	/** @description update fb-popper component instance */
	updatePopper,
	/** @description expose onOpen function to manage fb-tooltip open state */
	onOpen,
	/** @description expose onOpen function to manage fb-tooltip open state */
	onClose,
	/** @description expose hide function */
	hide,
});
</script>
