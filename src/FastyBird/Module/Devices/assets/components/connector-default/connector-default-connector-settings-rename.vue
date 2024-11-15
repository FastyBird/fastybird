<template>
	<el-form-item
		:label="t('devicesModule.fields.connectors.identifier.title')"
		:prop="['details', 'identifier']"
		:rules="identifierRule"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.connector?.draft"
			:disabled="!props.connector?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.connectors.name.title')"
		:prop="['details', 'name']"
		:rules="nameRule"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.connectors.comment.title')"
		:prop="['details', 'comment']"
		:rules="commentRule"
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
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElFormItem, ElInput, FormItemRule } from 'element-plus';

import { IConnectorForm } from '../../types';

import { IConnectorDefaultConnectorSettingsRenameProps } from './connector-default-connector-settings-rename.types';

defineOptions({
	name: 'ConnectorDefaultConnectorSettingsRename',
});

const props = withDefaults(defineProps<IConnectorDefaultConnectorSettingsRenameProps>(), {
	connector: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IConnectorForm['details']): void;
}>();

const { t } = useI18n();

const model = ref<IConnectorForm['details']>(props.modelValue);

const identifierRule = reactive<FormItemRule[]>([
	{ type: 'string', required: true, message: t('devicesModule.fields.connectors.identifier.validation.required') },
]);

const nameRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

const commentRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

watch(
	(): IConnectorForm['details'] => model.value,
	(val: IConnectorForm['details']): void => {
		emit('update:modelValue', val);
	}
);
</script>
