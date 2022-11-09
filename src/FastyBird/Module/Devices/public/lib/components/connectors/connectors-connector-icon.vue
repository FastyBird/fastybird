<template>
  <fb-ui-icon-with-child
    v-if="props.withState"
    :variant="stateColor"
    :data-connector-state="stateName"
  >
    <template #main>
      <font-awesome-icon icon="ethernet" />
    </template>
    <template #child>
      <font-awesome-icon :icon="stateIcon" />
    </template>
  </fb-ui-icon-with-child>

  <template v-else>
    <font-awesome-icon icon="ethernet" />
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  FbUiIconWithChild,
  FbUiVariantTypes,
} from '@fastybird/web-ui-library'
import { ConnectionState } from '@fastybird/metadata-library'
import { useWampV1Client } from '@fastybird/ws-exchange-plugin'

import { useConnectorState } from '@/lib/composables'
import { IConnector } from '@/lib/models/types'

interface IConnectorsIconProps {
  connector: IConnector
  withState?: boolean
}

const props = withDefaults(defineProps<IConnectorsIconProps>(), {
  withState: false
})

const { status: wsStatus } = useWampV1Client()

const { state: connectorState } = useConnectorState(props.connector)

const stateIcon = computed<string>((): string => {
  if (!wsStatus || connectorState.value === ConnectionState.SLEEPING) {
    return 'pause-circle'
  } else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value)) {
    return 'stop-circle'
  } else if (connectorState.value === ConnectionState.ALERT) {
    return 'exclamation-circle'
  } else if (connectorState.value === ConnectionState.LOST) {
    return 'question-circle'
  } else if (connectorState.value === ConnectionState.INIT) {
    return 'user-circle'
  } else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(connectorState.value)) {
    return 'play-circle'
  } else if (connectorState.value === ConnectionState.CONNECTED) {
    return 'check-circle'
  }

  return 'circle'
})

const stateName = computed<string>((): string => {
  if (!wsStatus || connectorState.value === ConnectionState.SLEEPING) {
    return 'pause'
  } else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value)) {
    return 'stop'
  } else if (connectorState.value === ConnectionState.ALERT) {
    return 'alert'
  } else if (connectorState.value === ConnectionState.LOST) {
    return 'lost'
  } else if (connectorState.value === ConnectionState.INIT) {
    return 'init'
  } else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(connectorState.value)) {
    return 'ready'
  } else if (connectorState.value === ConnectionState.CONNECTED) {
    return 'connected'
  }

  return 'unknown'
})

const stateColor = computed<FbUiVariantTypes>((): FbUiVariantTypes => {
  if (!wsStatus || connectorState.value === ConnectionState.SLEEPING) {
    return FbUiVariantTypes.WARNING
  } else if (connectorState.value === ConnectionState.ALERT) {
    return FbUiVariantTypes.DANGER
  } else if (connectorState.value === ConnectionState.INIT) {
    return FbUiVariantTypes.INFO
  } else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(connectorState.value)) {
    return FbUiVariantTypes.SUCCESS
  }

  return FbUiVariantTypes.DEFAULT
})
</script>
