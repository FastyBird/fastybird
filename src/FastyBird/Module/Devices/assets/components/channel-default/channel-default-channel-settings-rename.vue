<template>
	<el-form-item
		:label="t('devicesModule.fields.channels.identifier.title')"
		:prop="['details', 'identifier']"
		:rules="identifierRule"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.channel?.draft"
			:disabled="!props.channel?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.channels.name.title')"
		:prop="['details', 'name']"
		:rules="nameRule"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('devicesModule.fields.channels.comment.title')"
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

import { IChannelForm } from '../../types';

import { IChannelDefaultChannelSettingsRenameProps } from './channel-default-channel-settings-rename.types';

defineOptions({
	name: 'ChannelDefaultChannelSettingsRename',
});

const props = withDefaults(defineProps<IChannelDefaultChannelSettingsRenameProps>(), {
	channel: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IChannelForm['details']): void;
}>();

const { t } = useI18n();

const model = ref<IChannelForm['details']>(props.modelValue);

const identifierRule = reactive<FormItemRule[]>([
	{ type: 'string', required: true, message: t('devicesModule.fields.channels.identifier.validation.required') },
]);

const nameRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

const commentRule = reactive<FormItemRule[]>([{ type: 'string', required: false }]);

watch(
	(): IChannelForm['details'] => model.value,
	(val: IChannelForm['details']): void => {
		emit('update:modelValue', val);
	}
);
</script>
