import { computed, inject, ref, toRaw } from 'vue';
import { isEqual } from 'lodash';

import { isArray, isBoolean, isObject, isPropAbsent } from '@fastybird/web-ui-utils';

import { useFormSize } from '../../../form';
import { checkboxGroupContextKey } from '../constants';

import type { ComponentInternalInstance, ComputedRef, Ref } from 'vue';
import type { ComponentSize } from '@fastybird/web-ui-constants';
import type { CheckboxProps } from '../checkbox';
import type { CheckboxModel } from './';

export const useCheckboxStatus = (
	props: CheckboxProps,
	slots: ComponentInternalInstance['slots'],
	{ model }: Pick<CheckboxModel, 'model'>
): {
	checkboxButtonSize: ComputedRef<ComponentSize>;
	isChecked: ComputedRef<boolean>;
	isFocused: Ref<boolean>;
	checkboxSize: ComputedRef<ComponentSize>;
	hasOwnLabel: ComputedRef<boolean>;
	actualValue: ComputedRef<number | string | boolean | undefined>;
} => {
	const checkboxGroup = inject(checkboxGroupContextKey, undefined);
	const isFocused = ref(false);
	const actualValue = computed(() => props.value);
	const isChecked = computed<boolean>((): boolean => {
		const value = model.value;
		if (isBoolean(value)) {
			return value;
		} else if (isArray(value)) {
			if (isObject(actualValue.value)) {
				return value.map(toRaw).some((o) => isEqual(o, actualValue.value));
			} else if (actualValue.value) {
				return value.map(toRaw).includes(actualValue.value);
			} else {
				return false;
			}
		} else if (value !== null && value !== undefined) {
			return value === props.trueValue;
		} else {
			return !!value;
		}
	});

	const checkboxButtonSize = useFormSize(
		computed(() => checkboxGroup?.size?.value),
		{
			prop: true,
		}
	);
	const checkboxSize = useFormSize(computed(() => checkboxGroup?.size?.value));

	const hasOwnLabel = computed<boolean>(() => {
		return !!slots.default || !isPropAbsent(actualValue.value) || !isPropAbsent(props.label);
	});

	return {
		checkboxButtonSize,
		isChecked,
		isFocused,
		checkboxSize,
		hasOwnLabel,
		actualValue,
	};
};

export type CheckboxStatus = ReturnType<typeof useCheckboxStatus>;
