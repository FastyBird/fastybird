import { computed, ComputedRef, inject, Ref, ref, WritableComputedRef } from 'vue';

import { ComponentSize, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { useFormDisabled, useFormSize } from '../../../form';
import { RadioGroupContext, radioGroupContextKey } from '../constants';

import type { SetupContext } from 'vue';
import type { RadioButtonProps } from '../radio-button';
import type { RadioEmits, RadioProps } from '../radio';

export const useRadio = (
	props: RadioProps | RadioButtonProps,
	emit?: SetupContext<RadioEmits>['emit']
): {
	radioRef: Ref<HTMLInputElement | undefined>;
	isGroup: ComputedRef<boolean>;
	radioGroup: RadioGroupContext | undefined;
	focus: Ref<boolean>;
	size: ComputedRef<ComponentSize>;
	disabled: ComputedRef<boolean>;
	tabIndex: ComputedRef<number>;
	modelValue: WritableComputedRef<number | string | boolean | undefined>;
	actualValue: ComputedRef<number | string | boolean | undefined>;
} => {
	const radioRef = ref<HTMLInputElement | undefined>();
	const radioGroup = inject(radioGroupContextKey, undefined);
	const isGroup = computed(() => !!radioGroup);
	const actualValue = computed(() => props.value);
	const modelValue = computed<RadioProps['modelValue']>({
		get() {
			return isGroup.value ? radioGroup!.modelValue : props.modelValue!;
		},
		set(val) {
			if (isGroup.value) {
				radioGroup!.changeEvent(val);
			} else {
				emit && emit(UPDATE_MODEL_EVENT, val);
			}
			radioRef.value!.checked = props.modelValue === actualValue.value;
		},
	});

	const size = useFormSize(computed(() => radioGroup?.size));
	const disabled = useFormDisabled(computed(() => radioGroup?.disabled));
	const focus = ref(false);
	const tabIndex = computed(() => {
		return disabled.value || (isGroup.value && modelValue.value !== actualValue.value) ? -1 : 0;
	});

	return {
		radioRef,
		isGroup,
		radioGroup,
		focus,
		size,
		disabled,
		tabIndex,
		modelValue,
		actualValue,
	};
};
