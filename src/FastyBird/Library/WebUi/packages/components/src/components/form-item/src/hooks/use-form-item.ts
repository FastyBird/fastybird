import { computed, inject, onMounted, onUnmounted, ref, toRef, watch } from 'vue';

import { useId } from '@fastybird/web-ui-hooks';
import { debugWarn } from '@fastybird/web-ui-utils';

import { formItemContextKey } from '../constants';
import { formContextKey } from '../../../form/src/constants';

import type { ComputedRef, Ref, WatchStopHandle } from 'vue';
import type { FormContext } from '../../../form';
import type { FormItemContext } from '../types';

const SCOPE = 'FbForm';

export const useFormItem = (): {
	form: FormContext | undefined;
	formItem: FormItemContext | undefined;
} => {
	const form = inject(formContextKey, undefined);
	const formItem = inject(formItemContextKey, undefined);

	return {
		form,
		formItem,
	};
};

export type IUseFormItemInputCommonProps = {
	id?: string;
	label?: string | number | boolean | Record<string, any>;
};

export const useFormItemInputId = (
	props: Partial<IUseFormItemInputCommonProps>,
	{
		formItemContext,
		disableIdGeneration,
		disableIdManagement,
	}: {
		formItemContext?: FormItemContext;
		disableIdGeneration?: ComputedRef<boolean> | Ref<boolean>;
		disableIdManagement?: ComputedRef<boolean> | Ref<boolean>;
	}
): {
	isLabeledByFormItem: ComputedRef<boolean>;
	inputId: Ref<string | undefined>;
} => {
	if (!disableIdGeneration) {
		disableIdGeneration = ref<boolean>(false);
	}
	if (!disableIdManagement) {
		disableIdManagement = ref<boolean>(false);
	}

	const inputId = ref<string | undefined>();
	let idUnwatch: WatchStopHandle | undefined = undefined;

	const isLabeledByFormItem = computed<boolean>(() => {
		return !!(!props.label && formItemContext && formItemContext.inputIds && formItemContext.inputIds?.length <= 1);
	});

	// Generate id for ElFormItem label if not provided as prop
	onMounted((): void => {
		idUnwatch = watch(
			[toRef(props, 'id'), disableIdGeneration] as any,
			([id, disableIdGeneration]: [string, boolean]) => {
				const newId = id ?? (!disableIdGeneration ? useId().value : undefined);
				if (newId !== inputId.value) {
					if (formItemContext?.removeInputId) {
						inputId.value && formItemContext.removeInputId(inputId.value);
						if (!disableIdManagement?.value && !disableIdGeneration && newId) {
							formItemContext.addInputId(newId);
						}
					}
					inputId.value = newId;
				}
			},
			{ immediate: true }
		);
	});

	onUnmounted((): void => {
		idUnwatch && idUnwatch();
		if (formItemContext?.removeInputId) {
			inputId.value && formItemContext.removeInputId(inputId.value);
		}
	});

	return {
		isLabeledByFormItem,
		inputId,
	};
};

export const useFormItemLabelWidth = (): {
	autoLabelWidth: ComputedRef<string>;
	registerLabelWidth: (val: number, oldVal: number) => void;
	deregisterLabelWidth: (val: number) => void;
} => {
	const potentialLabelWidthArr = ref<number[]>([]);

	const autoLabelWidth = computed(() => {
		if (!potentialLabelWidthArr.value.length) {
			return '0';
		}

		const max = Math.max(...potentialLabelWidthArr.value);

		return max ? `${max}px` : '';
	});

	const getLabelWidthIndex = (width: number): number => {
		const index = potentialLabelWidthArr.value.indexOf(width);

		if (index === -1 && autoLabelWidth.value === '0') {
			debugWarn(SCOPE, `unexpected width ${width}`);
		}

		return index;
	};

	const registerLabelWidth = (val: number, oldVal: number): void => {
		if (val && oldVal) {
			const index = getLabelWidthIndex(oldVal);
			potentialLabelWidthArr.value.splice(index, 1, val);
		} else if (val) {
			potentialLabelWidthArr.value.push(val);
		}
	};

	const deregisterLabelWidth = (val: number): void => {
		const index = getLabelWidthIndex(val);
		if (index > -1) {
			potentialLabelWidthArr.value.splice(index, 1);
		}
	};

	return {
		autoLabelWidth,
		registerLabelWidth,
		deregisterLabelWidth,
	};
};
