import { RpCallResponse } from '@fastybird/vue-wamp-v1'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/devices-module/entity.channel.control.json'
import {
  ChannelControlEntity as ExchangeEntity,
  DevicesModuleRoutes as RoutingKeys,
  ActionRoutes,
  DataType,
  ControlAction,
} from '@fastybird/metadata'

import {
  ActionTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { AxiosResponse } from 'axios'
import get from 'lodash/get'
import uniq from 'lodash/uniq'

import Device from '@/lib/models/devices/Device'
import Channel from '@/lib/models/channels/Channel'
import { ChannelInterface } from '@/lib/models/channels/types'
import ChannelControl from '@/lib/models/channel-controls/ChannelControl'
import {
  ChannelControlInterface,
  ChannelControlResponseInterface,
  ChannelControlsResponseInterface,
} from '@/lib/models/channel-controls/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { ChannelControlJsonModelInterface, ModuleApiPrefix, SemaphoreTypes } from '@/lib/types'

interface SemaphoreFetchingState {
  items: string[]
  item: string[]
}

interface SemaphoreState {
  fetching: SemaphoreFetchingState
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface ChannelControlState {
  semaphore: SemaphoreState
}

interface SemaphoreAction {
  type: SemaphoreTypes
  id: string
}

const jsonApiFormatter = new Jsona({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const apiOptions = {
  dataTransformer: (result: AxiosResponse<ChannelControlResponseInterface> | AxiosResponse<ChannelControlsResponseInterface>): ChannelControlJsonModelInterface | ChannelControlJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ChannelControlJsonModelInterface | ChannelControlJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ChannelControlState = {

  semaphore: {
    fetching: {
      items: [],
      item: [],
    },
    creating: [],
    updating: [],
    deleting: [],
  },

}

const moduleActions: ActionTree<ChannelControlState, unknown> = {
  async get({ state, commit }, payload: { channel: ChannelInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await ChannelControl.api().get(
        `${ModuleApiPrefix}/v1/devices/${payload.channel.deviceId}/channels/${payload.channel.id}/controls/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.channel-controls.fetch.failed',
        e,
        'Fetching channel control failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }, payload: { channel: ChannelInterface }): Promise<boolean> {
    if (state.semaphore.fetching.items.includes(payload.channel.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
      id: payload.channel.id,
    })

    try {
      await ChannelControl.api().get(
        `${ModuleApiPrefix}/v1/devices/${payload.channel.deviceId}/channels/${payload.channel.id}/controls`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.channel-controls.fetch.failed',
        e,
        'Fetching channel controls failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.channel.id,
      })
    }
  },

  async transmitCommand(_store, payload: { control: ChannelControlInterface, value?: string | number | boolean | null }): Promise<boolean> {
    if (!ChannelControl.query().where('id', payload.control.id).exists()) {
      throw new Error('devices-module.channel-controls.transmit.failed')
    }

    const channel = Channel.find(payload.control.channelId)

    if (channel === null) {
      throw new Error('devices-module.channel-controls.transmit.failed')
    }

    const device = Device.find(channel.deviceId)

    if (device === null) {
      throw new Error('devices-module.channel-controls.transmit.failed')
    }

    return new Promise((resolve, reject) => {
      ChannelControl.wamp().call<{ data: string }>({
        routing_key: ActionRoutes.CHANNEL,
        source: ChannelControl.$devicesModuleSource,
        data: {
          action: ControlAction.SET,
          device: device.id,
          channel: channel.id,
          control: payload.control.id,
          expected_value: payload.value,
        },
      })
        .then((response: RpCallResponse<{ data: string }>): void => {
          if (get(response.data, 'response') === 'accepted') {
            resolve(true)
          } else {
            reject(new Error('devices-module.channel-controls.transmit.failed'))
          }
        })
        .catch((): void => {
          reject(new Error('devices-module.channel-controls.transmit.failed'))
        })
    })
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.CHANNELS_CONTROL_ENTITY_REPORTED,
        RoutingKeys.CHANNELS_CONTROL_ENTITY_CREATED,
        RoutingKeys.CHANNELS_CONTROL_ENTITY_UPDATED,
        RoutingKeys.CHANNELS_CONTROL_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !ChannelControl.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await ChannelControl.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'devices-module.channel-controls.delete.failed',
            e,
            'Delete channel control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | boolean | number | string[] | number[] | DataType | null | undefined } = {
          type: `${payload.source}/control/channel`,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'channel') {
              const channel = Channel.query().where('id', body[attrName]).first()

              if (channel !== null) {
                entityData.channelId = channel.id
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await ChannelControl.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = ChannelControl.query().with('channel').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.channel !== null) {
            // Updating entity on api failed, we need to refresh entity
            await ChannelControl.get(
              failedEntity.channel,
              body.id,
            )
          }

          throw new OrmError(
            'devices-module.channel-controls.update.failed',
            e,
            'Edit channel control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.CHANNELS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
            id: body.id,
          })
        }
      }

      return true
    } else {
      return false
    }
  },

  reset({ commit }): void {
    commit('RESET_STATE')
  },
}

const moduleMutations: MutationTree<ChannelControlState> = {
  ['SET_SEMAPHORE'](state: ChannelControlState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items.push(action.id)

        // Make all keys uniq
        state.semaphore.fetching.items = uniq(state.semaphore.fetching.items)
        break

      case SemaphoreTypes.GETTING:
        state.semaphore.fetching.item.push(action.id)

        // Make all keys uniq
        state.semaphore.fetching.item = uniq(state.semaphore.fetching.item)
        break

      case SemaphoreTypes.CREATING:
        state.semaphore.creating.push(action.id)

        // Make all keys uniq
        state.semaphore.creating = uniq(state.semaphore.creating)
        break

      case SemaphoreTypes.UPDATING:
        state.semaphore.updating.push(action.id)

        // Make all keys uniq
        state.semaphore.updating = uniq(state.semaphore.updating)
        break

      case SemaphoreTypes.DELETING:
        state.semaphore.deleting.push(action.id)

        // Make all keys uniq
        state.semaphore.deleting = uniq(state.semaphore.deleting)
        break
    }
  },

  ['CLEAR_SEMAPHORE'](state: ChannelControlState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.FETCHING:
        // Process all semaphore items
        state.semaphore.fetching.items
          .forEach((item: string, index: number): void => {
            // Find created item in reading one item semaphore...
            if (item === action.id) {
              // ...and remove it
              state.semaphore.fetching.items.splice(index, 1)
            }
          })
        break

      case SemaphoreTypes.GETTING:
        // Process all semaphore items
        state.semaphore.fetching.item
          .forEach((item: string, index: number): void => {
            // Find created item in reading one item semaphore...
            if (item === action.id) {
              // ...and remove it
              state.semaphore.fetching.item.splice(index, 1)
            }
          })
        break

      case SemaphoreTypes.CREATING:
        // Process all semaphore items
        state.semaphore.creating
          .forEach((item: string, index: number): void => {
            // Find created item in creating semaphore...
            if (item === action.id) {
              // ...and remove it
              state.semaphore.creating.splice(index, 1)
            }
          })
        break

      case SemaphoreTypes.UPDATING:
        // Process all semaphore items
        state.semaphore.updating
          .forEach((item: string, index: number): void => {
            // Find created item in updating semaphore...
            if (item === action.id) {
              // ...and remove it
              state.semaphore.updating.splice(index, 1)
            }
          })
        break

      case SemaphoreTypes.DELETING:
        // Process all semaphore items
        state.semaphore.deleting
          .forEach((item: string, index: number): void => {
            // Find removed item in removing semaphore...
            if (item === action.id) {
              // ...and remove it
              state.semaphore.deleting.splice(index, 1)
            }
          })
        break
    }
  },

  ['RESET_STATE'](state: ChannelControlState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ChannelControlState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
