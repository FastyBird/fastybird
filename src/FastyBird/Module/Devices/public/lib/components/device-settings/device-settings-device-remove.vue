<template>
  <fb-ui-confirmation-window
    :transparent-bg="props.transparentBg"
    @confirm="onRemove"
    @close="onClose"
  >
    <template #icon>
      <font-awesome-icon
        icon="trash"
        size="6x"
      />
    </template>

    <template #title>
      {{ t('headings.remove') }}
    </template>

    <i18n-t
      keypath="messages.confirmRemove"
      tag="p"
    >
      <template #device>
        <strong>{{ useEntityTitle(props.device).value }}</strong>
      </template>
    </i18n-t>
  </fb-ui-confirmation-window>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import get from 'lodash/get'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { FbUiConfirmationWindow } from '@fastybird/web-ui-library'

import {
  useEntityTitle,
  useFlashMessage,
} from '@/lib/composables'
import { useDevices } from '@/lib/models'
import { IDevice } from '@/lib/models/types'

interface IDeviceSettingsDeviceRemoveProps {
  device: IDevice
  callRemove?: boolean
  transparentBg?: boolean
}

const props = withDefaults(defineProps<IDeviceSettingsDeviceRemoveProps>(), {
  callRemove: true,
  transparentBg: false,
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirmed'): void
  (e: 'removed'): void
}>()

const { t } = useI18n()
const flashMessage = useFlashMessage()
const devicesStore = useDevices()

const onRemove = (): void => {
  emit('confirmed')

  if (props.callRemove) {
    const errorMessage = t('messages.notRemoved', {
      device: useEntityTitle(props.device).value,
    })

    devicesStore.remove({ id: props.device.id })
      .catch((e): void => {
        if (get(e, 'exception', null) !== null) {
          flashMessage.exception(get(e, 'exception', null), errorMessage)
        } else {
          flashMessage.error(errorMessage)
        }
      })

    emit('removed')
  }
}

const onClose = (): void => {
  emit('close')
}
</script>

<i18n>
{
  "en": {
    "headings": {
      "remove": "Remove device"
    },
    "messages": {
      "confirmRemove": "Are you sure to remove device {device} ?",
      "notRemoved": "Device {device} couldn't be removed."
    }
  }
}
</i18n>
