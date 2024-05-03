<template>
	<div
		ref="contentRef"
		v-bind="contentAttrs"
		:style="contentStyle"
		:class="contentClass"
		tabindex="-1"
		@mouseenter="(e) => $emit('mouseenter', e)"
		@mouseleave="(e) => $emit('mouseleave', e)"
	>
		<fb-focus-trap
			:trapped="trapped"
			:trap-on-focus-in="true"
			:focus-trap-el="contentRef"
			:focus-start-el="focusStartRef"
			@focus-after-trapped="onFocusAfterTrapped"
			@focus-after-released="onFocusAfterReleased"
			@focusin="onFocusInTrap"
			@focusout-prevented="onFocusoutPrevented"
			@release-requested="onReleaseRequested"
		>
			<slot />
		</fb-focus-trap>
	</div>
</template>

<script lang="ts" setup>
import { inject, onBeforeUnmount, onMounted, provide, ref, unref, watch } from 'vue';
import { isNil } from 'lodash';

import { NOOP } from '@vue/shared';
import { isElement } from '@fastybird/web-ui-utils';

import { formItemContextKey } from '../../form-item';
import { FbFocusTrap } from '../../focus-trap';
import { POPPER_CONTENT_INJECTION_KEY } from './constants';
import { popperContentEmits, popperContentProps } from './content';
import { usePopperContent, usePopperContentDOM, usePopperContentFocusTrap } from './hooks';

import type { WatchStopHandle } from 'vue';

defineOptions({
	name: 'FbPopperContent',
});

const emit = defineEmits(popperContentEmits);

const props = defineProps(popperContentProps);

const {
	focusStartRef,
	trapped,

	onFocusAfterReleased,
	onFocusAfterTrapped,
	onFocusInTrap,
	onFocusoutPrevented,
	onReleaseRequested,
} = usePopperContentFocusTrap(props, emit);

const { attributes, arrowRef, contentRef, styles, instanceRef, role, update } = usePopperContent(props);

const { ariaModal, arrowStyle, contentAttrs, contentClass, contentStyle, updateZIndex } = usePopperContentDOM(props, {
	styles,
	attributes,
	role,
});

const formItemContext = inject(formItemContextKey, undefined);
const arrowOffset = ref<number>();

provide(POPPER_CONTENT_INJECTION_KEY, {
	arrowStyle,
	arrowRef,
	arrowOffset,
});

if (formItemContext) {
	// disallow auto-id from inside popper content
	provide(formItemContextKey, {
		...formItemContext,
		addInputId: NOOP,
		removeInputId: NOOP,
	});
}

let triggerTargetAriaStopWatch: WatchStopHandle | undefined = undefined;

const updatePopper = (shouldUpdateZIndex = true): void => {
	update();
	shouldUpdateZIndex && updateZIndex();
};

const togglePopperAlive = (): void => {
	updatePopper(false);

	if (props.visible && props.focusOnShow) {
		trapped.value = true;
	} else if (props.visible === false) {
		trapped.value = false;
	}
};

onMounted((): void => {
	watch(
		(): HTMLElement | undefined => props.triggerTargetEl,
		(triggerTargetEl, prevTriggerTargetEl): void => {
			triggerTargetAriaStopWatch?.();
			triggerTargetAriaStopWatch = undefined;

			const el = unref(triggerTargetEl || contentRef.value);
			const prevEl = unref(prevTriggerTargetEl || contentRef.value);

			if (isElement(el)) {
				triggerTargetAriaStopWatch = watch(
					[role, (): string | undefined => props.ariaLabel, ariaModal, (): string | undefined => props.id],
					(watches) => {
						['role', 'aria-label', 'aria-modal', 'id'].forEach((key, idx) => {
							isNil(watches[idx]) ? el.removeAttribute(key) : el.setAttribute(key, watches[idx]!);
						});
					},
					{ immediate: true }
				);
			}
			if (prevEl !== el && isElement(prevEl)) {
				['role', 'aria-label', 'aria-modal', 'id'].forEach((key) => {
					prevEl.removeAttribute(key);
				});
			}
		},
		{ immediate: true }
	);

	watch((): boolean => props.visible, togglePopperAlive, { immediate: true });
});

onBeforeUnmount((): void => {
	triggerTargetAriaStopWatch?.();
	triggerTargetAriaStopWatch = undefined;
});

defineExpose({
	/** @description popper content element */
	popperContentRef: contentRef,
	/** @description popperjs instance */
	popperInstanceRef: instanceRef,
	/** @description method for updating popper */
	updatePopper,
	/** @description content style */
	contentStyle,
});
</script>
