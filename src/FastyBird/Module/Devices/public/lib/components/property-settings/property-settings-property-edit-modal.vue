<template>
  <fb-ui-modal-form
    :transparent-bg="true"
    :lock-submit-button="remoteFormResult !== FbFormResultTypes.NONE"
    :state="remoteFormResult"
    :submit-btn-label="isDraft ? t('buttons.update.title') : t('buttons.save.title')"
    :layout="isExtraSmallDevice ? FbUiModalLayoutTypes.PHONE : (isSmallDevice ? FbUiModalLayoutTypes.TABLET : FbUiModalLayoutTypes.DEFAULT)"
    @submit="onSubmitForm"
    @cancel="onClose"
    @close="onClose"
  >
    <template #title>
      {{ t('headings.edit') }}
    </template>

    <template #icon>
      <font-awesome-icon icon="pencil-alt" />
    </template>

    <template #form>
      <property-settings-property-form
        :connector="props.connector"
        :device="props.device"
        :channel="props.channel"
        :property="props.property"
        v-model:remote-form-submit="remoteFormSubmit"
        v-model:remote-form-result="remoteFormResult"
        @added="$emit('close')"
      />
    </template>
  </fb-ui-modal-form>
</template>

<script setup lang="ts">
import {
  computed,
  ref,
  watch,
} from 'vue'
import { useI18n } from 'vue-i18n'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  FbUiModalForm,
  FbFormResultTypes,
  FbUiModalLayoutTypes,
} from '@fastybird/web-ui-library'

import { useBreakpoints } from '@/lib/composables'
import {
  IChannel,
  IChannelProperty,
  IConnector,
  IConnectorProperty,
  IDevice,
  IDeviceProperty,
} from '@/lib/models/types'
import { PropertySettingsPropertyForm } from '@/lib/components'

interface IPropertySettingsPropertyModalProps {
  connector?: IConnector
  device?: IDevice
  channel?: IChannel
  property: IChannelProperty | IDeviceProperty | IConnectorProperty
}

const props = defineProps<IPropertySettingsPropertyModalProps>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const { t } = useI18n()
const { isExtraSmallDevice, isSmallDevice } = useBreakpoints()

const remoteFormSubmit = ref<boolean>(false)
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE)

const isDraft = computed<boolean>((): boolean => {
  if (isChannelProperty.value) {
    return props.channel ? props.channel.draft : false
  }

  if (isDeviceProperty.value) {
    return props.device ? props.device.draft : false
  }

  if (isConnectorProperty.value) {
    return props.connector ? props.connector.draft : false
  }

  return false
})

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined)
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined)
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined)

const onSubmitForm = (): void => {
  remoteFormSubmit.value = true
}

const onClose = (): void => {
  emit('close')
}

watch(
  (): FbFormResultTypes => remoteFormResult.value,
  (actual, previous): void => {
    if (actual === FbFormResultTypes.NONE && previous === FbFormResultTypes.OK) {
      emit('close')
    }
  },
)
</script>

<i18n>
{
  "en": {
    "headings": {
      "edit": "Edit parameter"
    },
    "buttons": {
      "update": {
        "title": "Update"
      },
      "save": {
        "title": "Save"
      }
    }
  }
}
</i18n>
