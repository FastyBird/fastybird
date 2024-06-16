<template>
	<el-form-item
		:label="t('fields.devices.identifier.title')"
		prop="identifier"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.device?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.devices.name.title')"
		prop="name"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.devices.comment.title')"
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

import { IDeviceSettingsDeviceRenameModel, IDeviceSettingsDeviceRenameProps } from './device-settings-device-rename.types';

defineOptions({
	name: 'DeviceSettingsDeviceRename',
});

const props = withDefaults(defineProps<IDeviceSettingsDeviceRenameProps>(), {
	device: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IDeviceSettingsDeviceRenameModel): void;
}>();

const { t } = useI18n();

const model = ref<IDeviceSettingsDeviceRenameModel>(props.modelValue);

watch(
	(): IDeviceSettingsDeviceRenameModel => model.value,
	(val: IDeviceSettingsDeviceRenameModel): void => {
		emit('update:modelValue', val);
	}
);
</script>
