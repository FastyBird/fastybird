<template>
	<fb-ui-content
		v-for="property in props.properties"
		:key="property.id"
		:mt="FbSizeTypes.MEDIUM"
	>
		<fb-form-checkbox
			v-if="property.dataType === DataType.BOOLEAN"
			v-model="model[property.id]"
			:name="property.identifier"
			:option="true"
		>
			{{ useEntityTitle(property).value }}
		</fb-form-checkbox>

		<template v-else>
			<fb-form-input
				v-model="model[property.id]"
				:label="useEntityTitle(property).value"
				:name="property.identifier"
			/>
		</template>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import { FbFormCheckbox, FbFormInput, FbUiContent, FbSizeTypes } from '@fastybird/web-ui-library';
import { DataType } from '@fastybird/metadata-library';

import { useEntityTitle } from '../../composables';
import {
	IPropertySettingsVariablePropertiesEditModel,
	IPropertySettingsVariablePropertiesEditProps,
} from './property-settings-variable-properties-edit.types';

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
