import { computed } from 'vue'

import {
  ConnectionState,
  PropertyType,
} from '@fastybird/metadata-library'

import { IDevice } from '@/lib/models/types'

export function useDeviceState(device: IDevice) {
  const state = computed<ConnectionState>((): ConnectionState => {
    const stateProperty = device.stateProperty

    if (
      stateProperty !== null
    ) {
      if (
        stateProperty.type.type === PropertyType.STATIC
        && typeof stateProperty.value === 'string'
        && Object.values(ConnectionState).includes(stateProperty.value as ConnectionState)
      ) {
        return stateProperty.value as ConnectionState
      }

      if (
        stateProperty.type.type === PropertyType.DYNAMIC
        && typeof stateProperty.actualValue === 'string'
        && Object.values(ConnectionState).includes(stateProperty.actualValue as ConnectionState)
      ) {
        return stateProperty.actualValue as ConnectionState
      }
    }

    return ConnectionState.UNKNOWN
  })

  const isReady = computed<boolean>((): boolean => {
    return ([ConnectionState.READY, ConnectionState.CONNECTED, ConnectionState.RUNNING] as string[])
      .includes(state.value)
  })

  return {
    state,
    isReady,
  }
}
