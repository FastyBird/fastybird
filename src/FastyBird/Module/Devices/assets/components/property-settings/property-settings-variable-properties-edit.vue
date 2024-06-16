<template>
	<div
		v-for="property in props.properties"
		:key="property.id"
	>
		<el-form-item
			v-if="property.dataType === DataType.BOOLEAN"
			:label="useEntityTitle(property).value"
			:prop="property.identifier"
		>
			<el-switch
				v-model="model[property.id]"
				:name="property.identifier"
			/>
		</el-form-item>

		<el-form-item
			v-else
			:label="useEntityTitle(property).value"
			:prop="property.identifier"
		>
			<el-input
				v-model="model[property.id]"
				:name="property.identifier"
			/>
		</el-form-item>
	</div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { ElFormItem, ElInput, ElSwitch } from 'element-plus';

import { DataType } from '@fastybird/metadata-library';

import { useEntityTitle } from '../../composables';
import {
	IPropertySettingsVariablePropertiesEditModel,
	IPropertySettingsVariablePropertiesEditProps,
} from './property-settings-variable-properties-edit.types';

defineOptions({
	name: 'PropertySettingsVariablePropertiesEdit',
});

const props = defineProps<IPropertySettingsVariablePropertiesEditProps>();

const emit = defineEmits<{
	(e: 'update:modelValue', model: IPropertySettingsVariablePropertiesEditModel[]): void;
}>();

const model = ref<{ [key: string]: string | undefined }>({});

props.modelValue.forEach((modelItem) => {
	Object.assign(model.value, { [modelItem.id]: modelItem.value });
});

watch(
	(): { [key: string]: string | undefined } => model.value,
	(val): void => {
		emit(
			'update:modelValue',
			Object.entries(val).map((row) => {
				return { id: row[0] as string, value: row[1] };
			})
		);
	},
	{ deep: true }
);
</script>
