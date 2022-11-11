<template>
	<fb-ui-content :mb="FbSizeTypes.LARGE">
		<fb-form-input
			v-model="model.name"
			:error="props.errors?.name"
			:label="t('fields.name.title')"
			:placeholder="t('fields.name.placeholder')"
			:tab-index="2"
			name="name"
		/>
	</fb-ui-content>

	<fb-form-text-area
		v-model="model.comment"
		:error="props.errors?.comment"
		:label="t('fields.comment.title')"
		:placeholder="t('fields.comment.placeholder')"
		:tab-index="3"
		:rows="2"
		name="comment"
	/>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { FbUiContent, FbFormInput, FbFormTextArea, FbSizeTypes } from '@fastybird/web-ui-library';

interface IConnectorSettingsConnectorRenameModel {
	identifier: string;
	name?: string;
	comment?: string;
}

interface IConnectorSettingsConnectorRenameProps {
	modelValue: IConnectorSettingsConnectorRenameModel;
	errors: {
		identifier?: string;
		name?: string;
		comment?: string;
	};
}

const props = defineProps<IConnectorSettingsConnectorRenameProps>();

const emit = defineEmits<{
	(e: 'update:modelValue', model: IConnectorSettingsConnectorRenameModel): void;
}>();

const { t } = useI18n();

const model = ref<IConnectorSettingsConnectorRenameModel>(props.modelValue);

watch(
	(): IConnectorSettingsConnectorRenameModel => model.value,
	(val): void => {
		emit('update:modelValue', val);
	}
);
</script>

<i18n>
{
  "en": {
    "fields": {
      "name": {
        "title": "Connector name",
        "placeholder": "Enter connector name"
      },
      "comment": {
        "title": "Connector description",
        "placeholder": "Enter connector description"
      }
    }
  }
}
</i18n>
