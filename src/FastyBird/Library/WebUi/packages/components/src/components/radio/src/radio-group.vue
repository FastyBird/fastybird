<template>
	<component
		:is="props.tag"
		:id="groupId"
		ref="radioGroupRef"
		:class="ns.b('group')"
		role="radiogroup"
		:aria-label="!isLabeledByFormItem ? props.label || 'radio-group' : undefined"
		:aria-labelledby="isLabeledByFormItem ? formItem!.labelId : undefined"
	>
		<slot />
	</component>
</template>

<script lang="ts" setup>
import { computed, nextTick, onMounted, provide, reactive, ref, toRefs, watch } from 'vue';

import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { useId, useNamespace } from '@fastybird/web-ui-hooks';
import { debugWarn } from '@fastybird/web-ui-utils';

import { useFormItem, useFormItemInputId } from '../../form-item';
import { radioGroupEmits, radioGroupProps } from './radio-group';
import { radioGroupContextKey } from './constants';

import type { RadioGroupProps } from './radio-group';

defineOptions({
	name: 'FbRadioGroup',
});

const props = defineProps(radioGroupProps);
const emit = defineEmits(radioGroupEmits);
const ns = useNamespace('radio');

const radioId = useId();
const radioGroupRef = ref<HTMLDivElement>();
const { formItem } = useFormItem();
const { inputId: groupId, isLabeledByFormItem } = useFormItemInputId(props, {
	formItemContext: formItem,
});

const changeEvent = async (value: RadioGroupProps['modelValue']): Promise<void> => {
	emit(UPDATE_MODEL_EVENT, value);
	await nextTick();
	emit('change', value);
};

onMounted((): void => {
	const radios = radioGroupRef.value!.querySelectorAll<HTMLInputElement>('[type=radio]');
	const firstLabel = radios[0];

	if (!Array.from(radios).some((radio) => radio.checked) && firstLabel) {
		firstLabel.tabIndex = 0;
	}
});

const name = computed<string>((): string => {
	return props.name || radioId.value;
});

provide(
	radioGroupContextKey,
	reactive({
		...toRefs(props),
		changeEvent,
		name,
	})
);

watch(
	(): string | number | boolean | undefined => props.modelValue,
	() => {
		if (props.validateEvent) {
			formItem?.validate('change').catch((err) => debugWarn(err));
		}
	}
);
</script>
