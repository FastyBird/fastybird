<template>
	<fb-ui-content :mb="FbSizeTypes.LARGE">
		<fb-form-input
			v-model="model.identifier"
			:error="props.errors?.identifier"
			:label="t('fields.identifier.title')"
			:placeholder="t('fields.identifier.placeholder')"
			:required="true"
			:readonly="!props.channel.draft"
			:tab-index="2"
			name="identifier"
		/>
	</fb-ui-content>

	<fb-ui-content :mb="FbSizeTypes.LARGE">
		<fb-form-input
			v-model="model.name"
			:error="props.errors?.name"
			:label="t('fields.name.title')"
			:placeholder="t('fields.name.placeholder')"
			:tab-index="3"
			name="name"
		/>
	</fb-ui-content>

	<fb-form-text-area
		v-model="model.comment"
		:error="props.errors?.comment"
		:label="t('fields.comment.title')"
		:placeholder="t('fields.comment.placeholder')"
		:tab-index="4"
		:rows="2"
		name="comment"
	/>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { FbUiContent, FbFormInput, FbFormTextArea, FbSizeTypes } from '@fastybird/web-ui-library';

import { IChannel } from '@/lib/models/types';

interface IChannelSettingsChannelRenameModel {
	identifier: string;
	name?: string;
	comment?: string;
}

interface IChannelSettingsChannelRenameProps {
	channel: IChannel;
	modelValue: IChannelSettingsChannelRenameModel;
	errors: {
		identifier?: string;
		name?: string;
		comment?: string;
	};
}

const props = defineProps<IChannelSettingsChannelRenameProps>();

const emit = defineEmits<{
	(e: 'update:modelValue', model: IChannelSettingsChannelRenameModel): void;
}>();

const { t } = useI18n();

const model = ref<IChannelSettingsChannelRenameModel>(props.modelValue);

watch(
	(): IChannelSettingsChannelRenameModel => model.value,
	(val): void => {
		emit('update:modelValue', val);
	}
);
</script>

<i18n>
{
  "en": {
    "fields": {
      "identifier": {
        "title": "Channel identifier",
        "placeholder": "Enter channel identifier"
      },
      "name": {
        "title": "Channel name",
        "placeholder": "Enter channel name"
      },
      "comment": {
        "title": "Channel description",
        "placeholder": "Enter channel description"
      }
    }
  }
}
</i18n>
