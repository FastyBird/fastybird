<template>
	<transition name="fade-in-linear" @after-leave="$emit('vanish')">
		<fb-overlay v-show="visible" :z-index="currentZIndex" :overlay-class="[ns.is('message-box')]" :mask="props.backdrop">
			<div
				role="dialog"
				:aria-label="props.title"
				aria-modal="true"
				:aria-describedby="!props.showInput ? contentId : undefined"
				:class="`${ns.namespace.value}-overlay-message-box`"
				@click="overlayEvent.onClick"
				@mousedown="overlayEvent.onMousedown"
				@mouseup="overlayEvent.onMouseup"
			>
				<fb-focus-trap loop :trapped="visible" :focus-trap-el="rootRef" :focus-start-el="focusStartRef" @release-requested="onCloseByFocusTrap">
					<div
						ref="rootRef"
						:class="[ns.b(), props.customClass, ns.is('draggable', draggable), { [ns.m('center')]: props.center }]"
						:style="props.customStyle"
						tabindex="-1"
						@click.stop=""
					>
						<div v-if="props.title !== null && props.title !== undefined" ref="headerRef" :class="[ns.e('header'), { 'show-close': props.closable }]">
							<div :class="ns.e('title')">
								<fb-icon v-if="iconComponent && props.center" :class="[ns.e('status'), ns.em('icon', props.type)]">
									<component :is="iconComponent" />
								</fb-icon>

								<span>{{ props.title }}</span>
							</div>

							<button
								v-if="props.closable"
								type="button"
								:class="ns.e('headerbtn')"
								:aria-label="t('component.message-box.close')"
								@click="handleAction(props.distinguishCancelAndClose ? MessageBoxActionTypes.CLOSE : MessageBoxActionTypes.CANCEL)"
								@keydown.prevent.enter="handleAction(props.distinguishCancelAndClose ? MessageBoxActionTypes.CLOSE : MessageBoxActionTypes.CANCEL)"
							>
								<fb-icon :class="ns.e('close')">
									<close />
								</fb-icon>
							</button>
						</div>

						<div :id="contentId" :class="ns.e('content')">
							<div :class="ns.e('container')">
								<fb-icon v-if="iconComponent && !props.center && hasMessage" :class="[ns.e('status'), ns.em('icon', props.type)]">
									<component :is="iconComponent" />
								</fb-icon>
								<div v-if="hasMessage" :class="ns.e('message')">
									<slot>
										<component
											:is="props.showInput ? 'label' : 'p'"
											v-if="!props.dangerouslyUseHTMLString"
											:for="props.showInput ? inputId : undefined"
										>
											{{ !props.dangerouslyUseHTMLString ? props.message : '' }}
										</component>
										<component :is="props.showInput ? 'label' : 'p'" v-else :for="props.showInput ? inputId : undefined" v-html="props.message" />
									</slot>
								</div>
							</div>

							<div v-show="props.showInput" :class="ns.e('input')">
								<fb-input
									:id="inputId"
									ref="inputRef"
									v-model="inputValue"
									:type="props.inputType"
									:placeholder="props.inputPlaceholder"
									:aria-invalid="validateError"
									:class="{ invalid: validateError }"
									@keydown.enter="handleInputEnter"
								/>
								<div
									:class="ns.e('errormsg')"
									:style="{
										visibility: !!editorErrorMessage ? 'visible' : 'hidden',
									}"
								>
									{{ editorErrorMessage }}
								</div>
							</div>
						</div>

						<div :class="ns.e('btns')">
							<fb-button
								v-if="props.showCancelButton"
								ref="cancelRef"
								:class="[props.cancelButtonClass]"
								:round="props.roundButton"
								:size="props.buttonSize"
								text
								@click="handleAction(MessageBoxActionTypes.CANCEL)"
								@keydown.prevent.enter="handleAction(MessageBoxActionTypes.CANCEL)"
							>
								{{ props.cancelButtonText || t('component.message-box.cancel') }}
							</fb-button>

							<fb-button
								v-if="props.showConfirmButton"
								ref="confirmRef"
								:variant="buttonVariant"
								:class="[props.confirmButtonClass]"
								:round="props.roundButton"
								:size="props.buttonSize"
								@click="handleAction(MessageBoxActionTypes.CONFIRM)"
								@keydown.prevent.enter="handleAction(MessageBoxActionTypes.CONFIRM)"
							>
								{{ props.confirmButtonText || t('component.message-box.confirm') }}
							</fb-button>
						</div>
					</div>
				</fb-focus-trap>
			</div>
		</fb-overlay>
	</transition>
</template>

<script lang="ts" setup>
import { ComponentPublicInstance, computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { useDraggable, useId, useLockscreen, useNamespace, useSameTarget } from '@fastybird/web-ui-hooks';
import { CloseComponentsMap, TypeComponentsMap } from '@fastybird/web-ui-utils';
import { Variant, VariantTypes } from '@fastybird/web-ui-constants';

import { FbButton } from '../../button';
import { useGlobalComponentSettings } from '../../config-provider';
import { FbFocusTrap } from '../../focus-trap';
import { FbIcon } from '../../icon';
import { FbInput } from '../../input';
import { FbOverlay } from '../../overlay';
import { MessageBoxActionTypes, MessageBoxTypeTypes, MessageBoxAction, messageBoxEmits, messageBoxProps } from './message-box';

import type { Component } from 'vue';

defineOptions({
	name: 'FbMessageBox',
	inheritAttrs: false,
});

const props = defineProps(messageBoxProps);
const emit = defineEmits(messageBoxEmits);

const ns = useNamespace('message-box');
const { locale, zIndex } = useGlobalComponentSettings(props.buttonSize);
const { t } = locale;
const { currentZIndex, nextZIndex } = zIndex;
const { close } = CloseComponentsMap;

const visible = ref<boolean>(false);

const contentId = useId();
const inputId = useId();

const iconComponent = computed<string | Component | undefined>(
	(): string | Component | undefined =>
		props.icon || (props.type && TypeComponentsMap[props.type as 'success' | 'warning' | 'error' | 'info']) || undefined
);

const buttonVariant = computed<Variant>((): Variant => props.type || VariantTypes.PRIMARY);

const hasMessage = computed<boolean>((): boolean => !!props.message);

const rootRef = ref<HTMLElement>();
const headerRef = ref<HTMLElement>();
const focusStartRef = ref<HTMLElement>();
const inputRef = ref<ComponentPublicInstance>();
const cancelRef = ref<ComponentPublicInstance>();
const confirmRef = ref<ComponentPublicInstance>();

const editorErrorMessage = ref<string | undefined>();
const validateError = ref<boolean>(false);

const action = ref<MessageBoxAction | undefined>(undefined);

const inputValue = ref<string | undefined>(props.inputValue);

const draggable = computed<boolean>(() => props.draggable);
const overflow = computed<boolean>(() => props.overflow);

useDraggable(rootRef, headerRef, draggable, overflow);

const overlayEvent = useSameTarget((): void => {
	if (props.closeOnClickBackdrop) {
		handleAction(props.distinguishCancelAndClose ? MessageBoxActionTypes.CLOSE : MessageBoxActionTypes.CANCEL);
	}
});

// locks the screen to prevent scroll
if (props.lockScroll) {
	useLockscreen(visible);
}

onMounted(async (): Promise<void> => {
	await nextTick();

	if (props.closeOnHashChange) {
		window.addEventListener('hashchange', doClose);
	}

	visible.value = true;
});

onBeforeUnmount((): void => {
	if (props.closeOnHashChange) {
		window.removeEventListener('hashchange', doClose);
	}
});

watch(
	(): string | undefined => inputValue.value,
	async (val: string | undefined): Promise<void> => {
		await nextTick();

		if (props.boxType === MessageBoxTypeTypes.PROMPT && val) {
			validate();
		}
	},
	{ immediate: true }
);

watch(
	() => visible.value,
	(val: boolean): void => {
		if (val) {
			if (props.boxType !== MessageBoxTypeTypes.PROMPT) {
				if (props.autofocus) {
					focusStartRef.value = confirmRef.value?.$el ?? rootRef.value;
				} else {
					focusStartRef.value = rootRef.value;
				}
			}

			nextZIndex();
		}

		if (props.boxType !== MessageBoxTypeTypes.PROMPT) {
			return;
		}

		if (val) {
			nextTick().then(() => {
				if (inputRef.value && inputRef.value.$el) {
					if (props.autofocus) {
						focusStartRef.value = getInputElement() ?? rootRef.value;
					} else {
						focusStartRef.value = rootRef.value;
					}
				}
			});
		} else {
			editorErrorMessage.value = '';
			validateError.value = false;
		}
	}
);

const doClose = (): void => {
	if (!visible.value) {
		return;
	}

	visible.value = false;

	nextTick((): void => {
		if (action.value) {
			emit('action', action.value);
		}
	});
};

const validate = (): boolean => {
	if (props.boxType === MessageBoxTypeTypes.PROMPT) {
		if (props.inputPattern && !props.inputPattern.test(inputValue.value || '')) {
			editorErrorMessage.value = props.inputErrorMessage || t('component.message-box.error');
			validateError.value = true;

			return false;
		}

		if (typeof props.inputValidator === 'function') {
			const validateResult = props.inputValidator(inputValue.value || '');

			if (validateResult === false) {
				editorErrorMessage.value = props.inputErrorMessage || t('component.message-box.error');
				validateError.value = true;

				return false;
			}

			if (typeof validateResult === 'string') {
				editorErrorMessage.value = validateResult;
				validateError.value = true;

				return false;
			}
		}
	}

	editorErrorMessage.value = '';
	validateError.value = false;

	return true;
};

const getInputElement = (): HTMLElement | undefined => {
	const inputRefs = inputRef.value?.$refs;

	return (inputRefs?.input || inputRefs?.textarea) as HTMLElement | undefined;
};

// when close on press escape is disabled, pressing esc should not callout
// any other message box and close any other dialog-ish elements
// e.g. Dialog has a close on press esc feature, and when it closes, it calls
// props.beforeClose method to make an intermediate state by callout a message box
// for some verification or alerting. then if we allow global event like this
// to dispatch, it could callout another message box.
const onCloseByFocusTrap = (): void => {
	if (props.closeOnPressEscape) {
		handleClose();
	}
};

const handleInputEnter = (e: Event | KeyboardEvent): void => {
	if (props.inputType !== 'textarea') {
		e.preventDefault();

		return handleAction(MessageBoxActionTypes.CONFIRM);
	}
};

const handleAction = (act: MessageBoxAction): void => {
	if (props.boxType === MessageBoxTypeTypes.PROMPT && act === MessageBoxActionTypes.CONFIRM && !validate()) {
		return;
	}

	action.value = act;

	if (props.beforeClose) {
		props.beforeClose?.(
			act,
			{
				visible,
				close: handleClose,
				cancelButtonRef: cancelRef,
				confirmButtonRef: confirmRef,
			},
			doClose
		);
	} else {
		doClose();
	}
};

const handleClose = (): void => {
	handleAction(MessageBoxActionTypes.CLOSE);
};

defineExpose({
	/** @description */
	visible,
	/** @description */
	close: handleClose,
	/** @description */
	cancelButtonRef: cancelRef,
	/** @description */
	confirmButtonRef: confirmRef,
});
</script>
