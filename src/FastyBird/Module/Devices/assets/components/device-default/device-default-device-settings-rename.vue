<template>
	<el-form-item
		:label="t('devicesModule.fields.devices.identifier.title')"
		:prop="['details', 'identifier']"
		:rules="identifierRule"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.device?.draft"
			:disabled="!props.device?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.devices.name.title')"
		:prop="['details', 'name']"
		:rules="nameRule"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.devices.comment.title')"
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

import { IDeviceForm } from '../../types';

import { IDeviceDefaultDeviceSettingsRenameProps } from './device-default-device-settings-rename.types';

defineOptions({
	name: 'DeviceDefaultDeviceSettingsRename',
});

const props = withDefaults(defineProps<IDeviceDefaultDeviceSettingsRenameProps>(), {
	device: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IDeviceForm['details']): void;
}>();

const { t } = useI18n();

const model = ref<IDeviceForm['details']>(props.modelValue);

const identifierRule = reactive<FormItemRule[]>([
	{ type: 'string', required: true, message: t('devicesModule.fields.devices.identifier.validation.required') },
]);

const nameRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

const commentRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

watch(
	(): IDeviceForm['details'] => model.value,
	(val: IDeviceForm['details']): void => {
		emit('update:modelValue', val);
	}
);
</script>
