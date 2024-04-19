import { computed, getCurrentInstance, inject, nextTick, watch } from 'vue';

import { debugWarn } from '@fastybird/web-ui-utils';

import { useFormItem } from '../../../form-item';
import { checkboxGroupContextKey } from '../constants';

import type { useFormItemInputId } from '../../../form-item';
import type { CheckboxProps } from '../checkbox';
import type { CheckboxDisabled, CheckboxModel, CheckboxStatus } from './';

export const useCheckboxEvent = (
	props: CheckboxProps,
	{
		model,
		isLimitExceeded,
		hasOwnLabel,
		isDisabled,
		isLabeledByFormItem,
	}: Pick<CheckboxModel, 'model' | 'isLimitExceeded'> &
		Pick<CheckboxStatus, 'hasOwnLabel'> &
		Pick<CheckboxDisabled, 'isDisabled'> &
		Pick<ReturnType<typeof useFormItemInputId>, 'isLabeledByFormItem'>
) => {
	const checkboxGroup = inject(checkboxGroupContextKey, undefined);
	const { formItem } = useFormItem();
	const { emit } = getCurrentInstance()!;

	const getLabeledValue = (value: string | number | boolean): string | number | boolean => {
		return [true, props.trueValue].includes(value) ? props.trueValue ?? true : props.falseValue ?? false;
	};

	const emitChangeEvent = (checked: string | number | boolean, e: InputEvent | MouseEvent): void => {
		emit('change', getLabeledValue(checked), e);
	};

	const handleChange = (e: Event): void => {
		if (isLimitExceeded.value) {
			return;
		}

		const target = e.target as HTMLInputElement;

		emit('change', getLabeledValue(target.checked), e);
	};

	const onClickRoot = async (e: MouseEvent): Promise<void> => {
		if (isLimitExceeded.value) {
			return;
		}

		if (!hasOwnLabel.value && !isDisabled.value && isLabeledByFormItem.value) {
			const eventTargets: EventTarget[] = e.composedPath();
			const hasLabel = eventTargets.some((item) => (item as HTMLElement).tagName === 'LABEL');

			if (!hasLabel) {
				model.value = getLabeledValue([false, props.falseValue].includes(model.value));
				await nextTick();
				emitChangeEvent(model.value, e);
			}
		}
	};

	const validateEvent = computed(() => checkboxGroup?.validateEvent || props.validateEvent);

	watch(
		(): string | number | boolean | undefined => props.modelValue,
		() => {
			if (validateEvent.value) {
				formItem?.validate('change').catch((err) => debugWarn(err));
			}
		}
	);

	return {
		handleChange,
		onClickRoot,
	};
};
