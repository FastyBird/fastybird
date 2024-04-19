import { isArray } from '@fastybird/web-ui-utils';

import { useFormItem, useFormItemInputId } from '../../../form-item';
import { useCheckboxDisabled } from './use-checkbox-disabled';
import { useCheckboxEvent } from './use-checkbox-event';
import { useCheckboxModel } from './use-checkbox-model';
import { useCheckboxStatus } from './use-checkbox-status';
import type { ComponentInternalInstance } from 'vue';

import type { CheckboxProps } from '../checkbox';

export const useCheckbox = (props: CheckboxProps, slots: ComponentInternalInstance['slots']) => {
	const { formItem: fbFormItem } = useFormItem();
	const { model, isGroup, isLimitExceeded } = useCheckboxModel(props);
	const { isFocused, isChecked, checkboxButtonSize, checkboxSize, hasOwnLabel, actualValue } = useCheckboxStatus(props, slots, { model });
	const { isDisabled } = useCheckboxDisabled({ model, isChecked });
	const { inputId, isLabeledByFormItem } = useFormItemInputId(props, {
		formItemContext: fbFormItem,
		disableIdGeneration: hasOwnLabel,
		disableIdManagement: isGroup,
	});
	const { handleChange, onClickRoot } = useCheckboxEvent(props, {
		model,
		isLimitExceeded,
		hasOwnLabel,
		isDisabled,
		isLabeledByFormItem,
	});
	const setStoreValue = (): void => {
		const addToStore = (): void => {
			if (isArray(model.value) && !model.value.includes(actualValue.value)) {
				model.value.push(actualValue.value);
			} else {
				model.value = props.trueValue || true;
			}
		};

		props.checked && addToStore();
	};

	setStoreValue();

	return {
		inputId,
		isLabeledByFormItem,
		isChecked,
		isDisabled,
		isFocused,
		checkboxButtonSize,
		checkboxSize,
		hasOwnLabel,
		model,
		actualValue,
		handleChange,
		onClickRoot,
	};
};
