<template>
	<teleport :disabled="!props.teleported" :to="appendTo">
		<transition
			:name="transitionClass"
			@after-leave="onTransitionLeave"
			@before-enter="onBeforeEnter"
			@after-enter="onAfterShow"
			@before-leave="onBeforeLeave"
		>
			<fb-popper-content
				v-if="shouldRender"
				v-show="shouldShow"
				:id="id"
				ref="contentRef"
				v-bind="$attrs"
				:aria-label="props.ariaLabel"
				:aria-hidden="ariaHidden"
				:boundaries-padding="props.boundariesPadding"
				:fallback-placements="props.fallbackPlacements"
				:gpu-acceleration="props.gpuAcceleration"
				:offset="props.offset"
				:placement="props.placement"
				:popper-options="props.popperOptions"
				:strategy="props.strategy"
				:effect="props.effect"
				:enterable="props.enterable"
				:pure="props.pure"
				:popper-class="props.popperClass"
				:popper-style="[props.popperStyle, contentStyle]"
				:reference-el="props.referenceEl"
				:trigger-target-el="props.triggerTargetEl"
				:visible="shouldShow"
				:z-index="props.zIndex"
				@mouseenter="onContentEnter"
				@mouseleave="onContentLeave"
				@blur="onBlur"
				@close="onClose"
			>
				<template v-if="!destroyed">
					<slot />
				</template>
			</fb-popper-content>
		</transition>
	</teleport>
</template>

<script lang="ts" setup>
import { computed, inject, onBeforeUnmount, ref, type StyleValue, unref, watch } from 'vue';
import { get } from 'lodash';

import { onClickOutside } from '@vueuse/core';
import { useNamespace, usePopperContainerId } from '@fastybird/web-ui-hooks';
import { composeEventHandlers } from '@fastybird/web-ui-utils';

import { FbPopperContent, PopperContentInstance } from '../../popper';
import { TOOLTIP_INJECTION_KEY } from './constants';
import { useTooltipContentProps } from './content';

defineOptions({
	name: 'FbTooltipContent',
	inheritAttrs: false,
});

const props = defineProps(useTooltipContentProps);

const ns = useNamespace('tooltip');
const { selector } = usePopperContainerId();

const contentRef = ref<PopperContentInstance | undefined>();
const destroyed = ref(false);

const { controlled, id, open, trigger, onClose, onOpen, onShow, onHide, onBeforeShow, onBeforeHide } = inject(TOOLTIP_INJECTION_KEY, undefined)!;

const transitionClass = computed<string>((): string => {
	return props.transition || `${ns.namespace.value}-fade-in-linear`;
});

const persistentRef = computed<boolean>((): boolean => {
	// For testing, we would always want the content to be rendered
	// to the DOM, so we need to return true here.
	if (get(import.meta, 'env.NODE_ENV') === 'test') {
		return true;
	}

	return props.persistent;
});

const shouldRender = computed<boolean>((): boolean => {
	return unref(persistentRef) ? true : unref(open);
});

const shouldShow = computed<boolean>((): boolean => {
	return props.disabled ? false : unref(open);
});

const appendTo = computed<HTMLElement | string>((): HTMLElement | string => {
	return props.appendTo || selector.value;
});

const contentStyle = computed<StyleValue>((): StyleValue => props.style ?? {});

const ariaHidden = computed<boolean>((): boolean => !unref(open));

let stopHandle: ReturnType<typeof onClickOutside>;

const onTransitionLeave = (): void => {
	onHide();
};

const stopWhenControlled = (): void => {
	if (unref(controlled)) {
		return;
	}
};

const onContentEnter = composeEventHandlers(stopWhenControlled, (): void => {
	if (props.enterable && unref(trigger) === 'hover') {
		onOpen();
	}
});

const onContentLeave = composeEventHandlers(stopWhenControlled, (): void => {
	if (unref(trigger) === 'hover') {
		onClose();
	}
});

onBeforeUnmount((): void => {
	destroyed.value = true;
});

const onBeforeEnter = (): void => {
	contentRef.value?.updatePopper?.();
	onBeforeShow?.();
};

const onBeforeLeave = (): void => {
	onBeforeHide?.();
};

const onAfterShow = (): void => {
	onShow();

	stopHandle = onClickOutside(
		computed(() => {
			return contentRef.value?.popperContentRef;
		}),
		() => {
			if (unref(controlled)) {
				return;
			}

			const $trigger = unref(trigger);

			if ($trigger !== 'hover') {
				onClose();
			}
		}
	);
};

const onBlur = (): void => {
	if (!props.virtualTriggering) {
		onClose();
	}
};

watch(
	(): boolean => unref(open),
	(val: boolean): void => {
		if (!val) {
			stopHandle?.();
		}
	},
	{
		flush: 'post',
	}
);

watch(
	(): string => props.content,
	(): void => {
		contentRef.value?.updatePopper?.();
	}
);

defineExpose({
	/** @description fb-popper-content component instance */
	contentRef,
});
</script>
