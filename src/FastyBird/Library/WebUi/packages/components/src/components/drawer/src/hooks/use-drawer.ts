import { getCurrentInstance, nextTick, onMounted, ref, watch } from 'vue';
import { isUndefined } from 'lodash-unified';

import { useTimeoutFn } from '@vueuse/core';
import { useId, useLockscreen, useZIndex } from '@fastybird/web-ui-hooks';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { isClient } from '@fastybird/web-ui-utils';

import type { Ref, SetupContext } from 'vue';
import type { DrawerEmits, DrawerProps } from '../drawer';

export const useDrawer = (
	props: DrawerProps,
	targetRef: Ref<HTMLElement | undefined>
): {
	afterEnter: () => void;
	afterLeave: () => void;
	beforeLeave: () => void;
	handleClose: () => void;
	onBackdropClick: () => void;
	onOpenAutoFocus: () => void;
	onCloseAutoFocus: () => void;
	onCloseRequested: () => void;
	onFocusoutPrevented: (event: CustomEvent) => void;
	titleId: Ref<string>;
	bodyId: Ref<string>;
	rendered: Ref<boolean>;
	visible: Ref<boolean>;
	zIndex: Ref<number>;
} => {
	const instance = getCurrentInstance()!;
	const emit = instance.emit as SetupContext<DrawerEmits>['emit'];
	const { nextZIndex } = useZIndex();

	const titleId = useId();
	const bodyId = useId();
	const visible = ref(false);
	const closed = ref(false);
	const rendered = ref(false); // when destroyOnClose is true, we initialize it as false vise versa
	const zIndex = ref(props.zIndex ?? nextZIndex());

	let openTimer: (() => void) | undefined = undefined;
	let closeTimer: (() => void) | undefined = undefined;

	const afterEnter = (): void => {
		emit('opened');
	};

	const afterLeave = (): void => {
		emit('closed');
		emit(UPDATE_MODEL_EVENT, false);

		if (props.destroyOnClose) {
			rendered.value = false;
		}
	};

	const beforeLeave = (): void => {
		emit('close');
	};

	const open = (): void => {
		closeTimer?.();
		openTimer?.();

		if (props.openDelay && props.openDelay > 0) {
			({ stop: openTimer } = useTimeoutFn(() => doOpen(), props.openDelay));
		} else {
			doOpen();
		}
	};

	const close = (): void => {
		openTimer?.();
		closeTimer?.();

		if (props.closeDelay && props.closeDelay > 0) {
			({ stop: closeTimer } = useTimeoutFn(() => doClose(), props.closeDelay));
		} else {
			doClose();
		}
	};

	const handleClose = (): void => {
		if (!props.closable) {
			return;
		}

		const hide = (shouldCancel?: boolean): void => {
			if (shouldCancel) {
				return;
			}

			closed.value = true;
			visible.value = false;
		};

		if (props.beforeClose) {
			props.beforeClose(hide);
		} else {
			close();
		}
	};

	const onBackdropClick = (): void => {
		if (props.closeOnClickBackdrop) {
			handleClose();
		}
	};

	const doOpen = (): void => {
		if (!isClient) {
			return;
		}

		visible.value = true;
	};

	const doClose = (): void => {
		visible.value = false;
	};

	const onOpenAutoFocus = (): void => {
		emit('openAutoFocus');
	};

	const onCloseAutoFocus = (): void => {
		emit('closeAutoFocus');
	};

	const onFocusoutPrevented = (event: CustomEvent): void => {
		if (event.detail?.focusReason === 'pointer') {
			event.preventDefault();
		}
	};

	if (props.lockScroll) {
		useLockscreen(visible);
	}

	const onCloseRequested = (): void => {
		if (props.closeOnPressEscape) {
			handleClose();
		}
	};

	watch(
		() => props.modelValue,
		(val) => {
			if (val) {
				closed.value = false;
				open();
				rendered.value = true; // enables lazy rendering
				zIndex.value = isUndefined(props.zIndex) ? nextZIndex() : zIndex.value++;
				// this.$el.addEventListener('scroll', this.updatePopper)
				nextTick(() => {
					emit('open');
					if (targetRef.value) {
						targetRef.value.scrollTop = 0;
					}
				});
			} else {
				// this.$el.removeEventListener('scroll', this.updatePopper
				if (visible.value) {
					close();
				}
			}
		}
	);

	onMounted(() => {
		if (props.modelValue) {
			visible.value = true;
			rendered.value = true; // enables lazy rendering
			open();
		}
	});

	return {
		afterEnter,
		afterLeave,
		beforeLeave,
		handleClose,
		onBackdropClick,
		onOpenAutoFocus,
		onCloseAutoFocus,
		onCloseRequested,
		onFocusoutPrevented,
		titleId,
		bodyId,
		rendered,
		visible,
		zIndex,
	};
};
