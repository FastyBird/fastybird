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
      <template #connector>
        <strong>{{ useEntityTitle(props.connector).value }}</strong>
      </template>
    </i18n-t>
  </fb-ui-confirmation-window>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import get from 'lodash/get'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { FbUiConfirmationWindow } from '@fastybird/web-ui-theme'

import {
  useEntityTitle,
  useFlashMessage,
} from '@/lib/composables'
import { useConnectors } from '@/lib/models'
import { IConnector } from '@/lib/models/types'

interface IConnectorSettingsConnectorRemoveProps {
  connector: IConnector
  callRemove?: boolean
  transparentBg?: boolean
}

const props = withDefaults(defineProps<IConnectorSettingsConnectorRemoveProps>(), {
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
const connectorsStore = useConnectors()

const onRemove = (): void => {
  emit('confirmed')

  if (props.callRemove) {
    const errorMessage = t('messages.notRemoved', {
      connector: useEntityTitle(props.connector).value,
    })

    connectorsStore.remove({ id: props.connector.id })
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
      "remove": "Remove connector"
    },
    "messages": {
      "confirmRemove": "Are you sure to remove connector {connector} ?",
      "notRemoved": "Connector {connector} couldn't be removed."
    }
  }
}
</i18n>
