<template>
	<component
		:is="props.tag"
		:id="groupId"
		ref="checkboxGroupRef"
		:class="ns.b('group')"
		role="group"
		:aria-label="!isLabeledByFormItem ? props.label || 'checkbox-group' : undefined"
		:aria-labelledby="isLabeledByFormItem ? formItem?.labelId : undefined"
	>
		<slot />
	</component>
</template>

<script lang="ts" setup>
import { computed, nextTick, provide, ref, toRefs, watch } from 'vue';
import { pick } from 'lodash';

import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { debugWarn } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { useFormItem, useFormItemInputId } from '../../form-item';
import { checkboxGroupEmits, checkboxGroupProps } from './checkbox-group';
import { checkboxGroupContextKey } from './constants';

import type { CheckboxGroupValueType } from './checkbox-group';

defineOptions({
	name: 'FbCheckboxGroup',
});

const props = defineProps(checkboxGroupProps);
const emit = defineEmits(checkboxGroupEmits);
const ns = useNamespace('checkbox');

const checkboxGroupRef = ref<HTMLDivElement>();
const { formItem } = useFormItem();
const { inputId: groupId, isLabeledByFormItem } = useFormItemInputId(props, {
	formItemContext: formItem,
});

const changeEvent = async (value: CheckboxGroupValueType): Promise<void> => {
	emit(UPDATE_MODEL_EVENT, value);
	await nextTick();
	emit('change', value);
};

const modelValue = computed<CheckboxGroupValueType>({
	get(): CheckboxGroupValueType {
		return props.modelValue;
	},
	set(val: CheckboxGroupValueType) {
		changeEvent(val);
	},
});

provide(checkboxGroupContextKey, {
	...pick(toRefs(props), ['size', 'min', 'max', 'disabled', 'validateEvent', 'fill', 'textColor']),
	modelValue,
	changeEvent,
});

watch(
	(): CheckboxGroupValueType => props.modelValue,
	() => {
		if (props.validateEvent) {
			formItem?.validate('change').catch((err) => debugWarn(err));
		}
	}
);
</script>
