import { computed, getCurrentInstance, inject, ref } from 'vue';

import { isArray, isUndefined } from '@fastybird/web-ui-utils';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { checkboxGroupContextKey } from '../constants';

import type { ComputedRef, Ref, WritableComputedRef } from 'vue';
import type { CheckboxProps } from '../checkbox';

export const useCheckboxModel = (
	props: CheckboxProps
): {
	model: WritableComputedRef<number | string | boolean | (number | string | boolean)[] | undefined>;
	isGroup: ComputedRef<boolean>;
	isLimitExceeded: Ref<boolean>;
} => {
	const selfModel = ref<number | string | boolean | (number | string | boolean)[] | undefined>(false);
	const { emit } = getCurrentInstance()!;
	const checkboxGroup = inject(checkboxGroupContextKey, undefined);
	const isGroup = computed(() => isUndefined(checkboxGroup) === false);
	const isLimitExceeded = ref(false);
	const model = computed({
		get() {
			return isGroup.value ? checkboxGroup?.modelValue?.value : props.modelValue ?? selfModel.value;
		},

		set(val: number | string | boolean | (number | string | boolean)[] | undefined) {
			if (isGroup.value && isArray(val) && isArray(model.value)) {
				isLimitExceeded.value = checkboxGroup?.max?.value !== undefined && val.length > checkboxGroup?.max.value && val.length > model.value?.length;
				isLimitExceeded.value === false && checkboxGroup?.changeEvent?.(val);
			} else {
				emit(UPDATE_MODEL_EVENT, val);
				selfModel.value = val;
			}
		},
	});

	return {
		model,
		isGroup,
		isLimitExceeded,
	};
};

export type CheckboxModel = ReturnType<typeof useCheckboxModel>;
