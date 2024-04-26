import { computed, ComputedRef, inject } from 'vue';

import { isUndefined } from '@fastybird/web-ui-utils';

import { useFormDisabled } from '../../../form';
import { checkboxGroupContextKey } from '../constants';

import type { CheckboxModel, CheckboxStatus } from './';

export const useCheckboxDisabled = ({
	model,
	isChecked,
}: Pick<CheckboxModel, 'model'> & Pick<CheckboxStatus, 'isChecked'>): {
	isDisabled: ComputedRef<boolean>;
	isLimitDisabled: ComputedRef<boolean>;
} => {
	const checkboxGroup = inject(checkboxGroupContextKey, undefined);

	const isLimitDisabled = computed(() => {
		const max = checkboxGroup?.max?.value;
		const min = checkboxGroup?.min?.value;

		return (
			(!isUndefined(max) && model.value.length >= max && !isChecked.value) || (!isUndefined(min) && model.value.length <= min && isChecked.value)
		);
	});

	const isDisabled = useFormDisabled(computed(() => checkboxGroup?.disabled.value || isLimitDisabled.value));

	return {
		isDisabled,
		isLimitDisabled,
	};
};

export type CheckboxDisabled = ReturnType<typeof useCheckboxDisabled>;
