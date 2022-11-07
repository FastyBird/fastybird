<template>
  <fb-ui-content :mb="FbSizeTypes.LARGE">
    <fb-form-input
      v-model="model.identifier"
      :error="props.errors?.identifier"
      :label="t('fields.identifier.title')"
      :placeholder="t('fields.identifier.placeholder')"
      :required="true"
      :readonly="!props.device.draft"
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
import {
  ref,
  watch,
} from 'vue'
import { useI18n } from 'vue-i18n'

import {
  FbUiContent,
  FbFormInput,
  FbFormTextArea,
  FbSizeTypes,
} from '@fastybird/web-ui-theme'

import { IDevice } from '@/lib/models/types'

interface IDeviceSettingsDeviceRenameModel {
  identifier: string
  name: string | null
  comment: string | null
}

interface IDeviceSettingsDeviceRenameProps {
  device: IDevice | null
  modelValue: IDeviceSettingsDeviceRenameModel,
  errors: {
    identifier?: string
    name?: string
    comment?: string
  }
}

const props = withDefaults(defineProps<IDeviceSettingsDeviceRenameProps>(), {
  device: null
})

const emit = defineEmits<{
  (e: 'update:modelValue', model: IDeviceSettingsDeviceRenameModel): void
}>()

const { t } = useI18n()

const model = ref<IDeviceSettingsDeviceRenameModel>(props.modelValue)

watch(
  (): IDeviceSettingsDeviceRenameModel => model.value,
  (val): void => {
    emit('update:modelValue', val)
  },
)
</script>

<i18n>
{
  "en": {
    "fields": {
      "identifier": {
        "title": "Device identifier",
        "placeholder": "Enter device identifier"
      },
      "name": {
        "title": "Device name",
        "placeholder": "Enter device name"
      },
      "comment": {
        "title": "Device description",
        "placeholder": "Enter device description"
      }
    }
  }
}
</i18n>
