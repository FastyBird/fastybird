<template>
	<div
		v-for="property in props.properties"
		:key="property.id"
	>
		<property-default-variable-property-edit
			v-model="model[property.id]"
			:property="property"
			:label="get(props.labels, property.id)"
			:disabled="get(props.disabled, property.id)"
			:readonly="get(props.readonly, property.id)"
			:rules="get(props.rules, property.id)"
			@change="onValueChange"
		/>
	</div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import get from 'lodash.get';

import { PropertyDefaultVariablePropertyEdit } from '../../components';

import {
	IPropertyDefaultVariablePropertiesEditModel,
	IPropertyDefaultVariablePropertiesEditProps,
} from './property-default-variable-properties-edit.types';

defineOptions({
	name: 'PropertyDefaultVariablePropertiesEdit',
});

const props = defineProps<IPropertyDefaultVariablePropertiesEditProps>();

const emit = defineEmits<{
	(e: 'update:modelValue', model: IPropertyDefaultVariablePropertiesEditModel): void;
	(e: 'change', value: IPropertyDefaultVariablePropertiesEditModel): void;
}>();

const model = ref<IPropertyDefaultVariablePropertiesEditModel>(props.modelValue);

const onValueChange = (): void => {
	emit('change', model.value);
};

watch(
	(): IPropertyDefaultVariablePropertiesEditModel => model.value,
	(val: IPropertyDefaultVariablePropertiesEditModel): void => {
		emit('update:modelValue', val);
	},
	{ deep: true }
);

watch(
	(): IPropertyDefaultVariablePropertiesEditModel => props.modelValue,
	(val: IPropertyDefaultVariablePropertiesEditModel): void => {
		model.value = val;
	}
);
</script>
