<template>
	<el-form-item
		:label="t('fields.connectors.identifier.title')"
		prop="identifier"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.connector?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.connectors.name.title')"
		prop="name"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.connectors.comment.title')"
		prop="comment"
	>
		<el-input
			v-model="model.comment"
			:rows="2"
			type="textarea"
			name="comment"
		/>
	</el-form-item>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElFormItem, ElInput } from 'element-plus';

import { IConnectorSettingsConnectorRenameModel, IConnectorSettingsConnectorRenameProps } from './connector-settings-connector-rename.types';

defineOptions({
	name: 'ConnectorSettingsConnectorRename',
});

const props = withDefaults(defineProps<IConnectorSettingsConnectorRenameProps>(), {
	connector: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IConnectorSettingsConnectorRenameModel): void;
}>();

const { t } = useI18n();

const model = ref<IConnectorSettingsConnectorRenameModel>(props.modelValue);

watch(
	(): IConnectorSettingsConnectorRenameModel => model.value,
	(val: IConnectorSettingsConnectorRenameModel): void => {
		emit('update:modelValue', val);
	}
);
</script>
