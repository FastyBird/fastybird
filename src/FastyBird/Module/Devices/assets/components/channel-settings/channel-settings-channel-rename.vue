<template>
	<el-form-item
		:label="t('fields.channels.identifier.title')"
		prop="identifier"
	>
		<el-input
			v-model="model.identifier"
			name="identifier"
			:readonly="!props.channel?.draft"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.channels.name.title')"
		prop="name"
	>
		<el-input
			v-model="model.name"
			name="name"
		/>
	</el-form-item>

	<el-form-item
		:label="t('fields.channels.comment.title')"
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

import { IChannelSettingsChannelRenameModel, IChannelSettingsChannelRenameProps } from './channel-settings-channel-rename.types';

defineOptions({
	name: 'ChannelSettingsChannelRename',
});

const props = withDefaults(defineProps<IChannelSettingsChannelRenameProps>(), {
	channel: null,
});

const emit = defineEmits<{
	(e: 'update:modelValue', model: IChannelSettingsChannelRenameModel): void;
}>();

const { t } = useI18n();

const model = ref<IChannelSettingsChannelRenameModel>(props.modelValue);

watch(
	(): IChannelSettingsChannelRenameModel => model.value,
	(val: IChannelSettingsChannelRenameModel): void => {
		emit('update:modelValue', val);
	}
);
</script>
