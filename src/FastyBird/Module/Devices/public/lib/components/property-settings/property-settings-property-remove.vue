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
      v-if="isConnectorProperty"
      keypath="messages.confirmRemoveConnectorProperty"
      tag="p"
    >
      <template #property>
        <strong>{{ useEntityTitle(props.property).value }}</strong>
      </template>

      <template #connector>
        <strong>{{ useEntityTitle(props.connector).value }}</strong>
      </template>
    </i18n-t>

    <i18n-t
      v-if="isDeviceProperty"
      keypath="messages.confirmRemoveDeviceProperty"
      tag="p"
    >
      <template #property>
        <strong>{{ useEntityTitle(props.property).value }}</strong>
      </template>

      <template #device>
        <strong>{{ useEntityTitle(props.device).value }}</strong>
      </template>
    </i18n-t>

    <i18n-t
      v-if="isChannelProperty"
      keypath="messages.confirmRemoveChannelProperty"
      tag="p"
    >
      <template #property>
        <strong>{{ useEntityTitle(props.property).value }}</strong>
      </template>

      <template #device>
        <strong>{{ useEntityTitle(props.device).value }}</strong>
      </template>

      <template #channel>
        <strong>{{ useEntityTitle(props.channel).value }}</strong>
      </template>
    </i18n-t>
  </fb-ui-confirmation-window>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import get from 'lodash/get'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { FbUiConfirmationWindow } from '@fastybird/web-ui-library'

import {
  useEntityTitle,
  useFlashMessage,
} from '@/lib/composables'
import {
  useChannelProperties,
  useConnectorProperties,
  useDeviceProperties,
} from '@/lib/models'
import {
  IChannel,
  IChannelProperty,
  IConnector,
  IConnectorProperty,
  IDevice,
  IDeviceProperty,
} from '@/lib/models/types'

interface IDeviceSettingsDeviceRemoveProps {
  connector?: IConnector
  device?: IDevice
  channel?: IChannel
  property: IChannelProperty | IConnectorProperty | IDeviceProperty
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

const channelPropertiesStore = useChannelProperties()
const connectorPropertiesStore = useConnectorProperties()
const devicePropertiesStore = useDeviceProperties()

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined)
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined)
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined)

const onRemove = (): void => {
  emit('confirmed')

  if (props.callRemove) {
    const errorMessage = t('messages.notRemoved', {
      property: useEntityTitle(props.property).value,
    })

    if (isChannelProperty.value) {
      channelPropertiesStore.remove({ id: props.property.id })
        .catch((e): void => {
          if (get(e, 'exception', null) !== null) {
            flashMessage.exception(get(e, 'exception', null), errorMessage)
          } else {
            flashMessage.error(errorMessage)
          }
        })

    } else if (isDeviceProperty.value) {
      devicePropertiesStore.remove({ id: props.property.id })
        .catch((e): void => {
          if (get(e, 'exception', null) !== null) {
            flashMessage.exception(get(e, 'exception', null), errorMessage)
          } else {
            flashMessage.error(errorMessage)
          }
        })

    } else if (isConnectorProperty.value) {
      connectorPropertiesStore.remove({ id: props.property.id })
        .catch((e): void => {
          if (get(e, 'exception', null) !== null) {
            flashMessage.exception(get(e, 'exception', null), errorMessage)
          } else {
            flashMessage.error(errorMessage)
          }
        })
    }

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
      "remove": "Remove parameter"
    },
    "messages": {
      "confirmRemoveConnectorProperty": "Are you sure to remove connector parameter {property} ?",
      "confirmRemoveDeviceProperty": "Are you sure to remove device parameter {property} ?",
      "confirmRemoveChannelProperty": "Are you sure to remove channel parameter {property} ?",
      "notRemoved": "Parameter {property} couldn't be removed."
    }
  }
}
</i18n>
