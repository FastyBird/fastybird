<template>
  <fb-ui-content
    :mt="FbSizeTypes.MEDIUM"
    v-for="property in props.properties"
  >
    <fb-form-checkbox
      v-if="property.dataType === DataType.BOOLEAN"
      v-model="model[property.id]"
      :name="property.identifier"
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
import {
  ref,
  watch,
} from 'vue'

import {
  FbFormCheckbox,
  FbFormInput,
  FbUiContent,
  FbSizeTypes,
} from '@fastybird/web-ui-theme'
import { DataType } from '@fastybird/metadata-library'

import { useEntityTitle } from '@/lib/composables'
import { IConnectorProperty } from '@/lib/models/types'

interface IPropertySettingsStaticPropertiesEditModel {
  id: string
  value: string | null
}

interface IPropertySettingsStaticPropertiesEditProps {
  modelValue: IPropertySettingsStaticPropertiesEditModel[]
  properties: IConnectorProperty[]
}

const props = defineProps<IPropertySettingsStaticPropertiesEditProps>()

const emit = defineEmits<{
  (e: 'update:modelValue', model: IPropertySettingsStaticPropertiesEditModel[]): void
}>()

const model = ref<{ [key: string]: string | null }>({})

props.modelValue.forEach(modelItem => {
  Object.assign(model.value, { [modelItem.id]: modelItem.value })
})

watch(
  (): { [key: string]: string | null } => model.value,
  (val): void => {
    emit('update:modelValue', Object.entries(val).map(row => { return { id: row[0] as string, value: row[1] } }))
  },
  { deep: true },
)
</script>
