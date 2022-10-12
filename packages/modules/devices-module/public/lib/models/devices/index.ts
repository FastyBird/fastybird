import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema from '@fastybird/metadata/resources/schemas/modules/devices-module/entity.device.json'
import {
  DeviceEntity as ExchangeEntity,
  DevicesModuleRoutes as RoutingKeys,
  ConnectionState,
  HardwareManufacturer,
  DeviceModel,
  FirmwareManufacturer,
} from '@fastybird/metadata'

import {
  ActionTree,
  GetterTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { v4 as uuid } from 'uuid'
import { AxiosResponse } from 'axios'
import get from 'lodash/get'
import uniq from 'lodash/uniq'

import Device from '@/lib/models/devices/Device'
import {
  DeviceCreateInterface,
  DeviceInterface,
  DeviceResponseInterface,
  DevicesResponseInterface,
  DeviceUpdateInterface,
} from '@/lib/models/devices/types'
import Channel from '@/lib/models/channels/Channel'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { DeviceJsonModelInterface, ModuleApiPrefix, SemaphoreTypes } from '@/lib/types'
import DeviceProperty from '@/lib/models/device-properties/DeviceProperty'
import DeviceControl from '@/lib/models/device-controls/DeviceControl'
import { ConnectorInterface } from '@/lib/models/connectors/types'

interface SemaphoreFetchingState {
  items: boolean
  item: string[]
}

interface SemaphoreState {
  fetching: SemaphoreFetchingState
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface DeviceState {
  semaphore: SemaphoreState
  firstLoad: boolean
}

interface SemaphoreAction {
  type: SemaphoreTypes
  id?: string
}

const jsonApiFormatter = new Jsona({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const apiOptions = {
  dataTransformer: (result: AxiosResponse<DeviceResponseInterface> | AxiosResponse<DevicesResponseInterface>): DeviceJsonModelInterface | DeviceJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as DeviceJsonModelInterface | DeviceJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: DeviceState = {

  semaphore: {
    fetching: {
      items: false,
      item: [],
    },
    creating: [],
    updating: [],
    deleting: [],
  },

  firstLoad: false,

}

const moduleGetters: GetterTree<DeviceState, unknown> = {
  firstLoadFinished: state => (): boolean => {
    return state.firstLoad
  },

  getting: state => (id: string): boolean => {
    return state.semaphore.fetching.item.includes(id)
  },

  fetching: state => (): boolean => {
    return state.semaphore.fetching.items
  },
}

const moduleActions: ActionTree<DeviceState, unknown> = {
  async get({ state, commit }, payload: { id: string, includeChannels: boolean }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Device.api().get(
        `${ModuleApiPrefix}/v1/devices/${payload.id}?include=properties,controls`,
        apiOptions,
      )

      if (payload.includeChannels) {
        const device = Device.find(payload.id)

        if (device !== null) {
          await Channel.fetch(
            device,
          )
        }
      }

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.devices.fetch.failed',
        e,
        'Fetching devices failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }, payload: { includeChannels: boolean }): Promise<boolean> {
    if (state.semaphore.fetching.items) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
    })

    try {
      await Device.api().get(
        `${ModuleApiPrefix}/v1/devices?include=properties,controls`,
        apiOptions,
      )

      if (payload.includeChannels) {
        const devices = await Device.all()

        const promises: Promise<boolean>[] = []

        devices.forEach((device: DeviceInterface) => {
          promises.push(
            Channel.fetch(
              device,
            ),
          )
        })

        await Promise.all(promises)
      }

      commit('SET_FIRST_LOAD', true)

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.devices.fetch.failed',
        e,
        'Fetching devices failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
      })
    }
  },

  async add({ commit }, payload: { connector: ConnectorInterface, id?: string | null, draft?: boolean, data: DeviceCreateInterface }): Promise<Item<Device>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Device.insert({
        data: Object.assign({}, payload.data, { id, draft, connectorId: payload.connector.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'devices-module.devices.create.failed',
        e,
        'Create new device failed.',
      )
    }

    const createdEntity = Device.find(id)

    if (createdEntity === null) {
      await Device.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('devices-module.devices.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Device.find(id)
    } else {
      try {
        await Device.api().post(
          `${ModuleApiPrefix}/v1/devices?include=properties,controls`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Device.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Device.delete(id)

        throw new ApiError(
          'devices-module.devices.create.failed',
          e,
          'Create new device failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { device: DeviceInterface, data: DeviceUpdateInterface }): Promise<Item<Device>> {
    if (state.semaphore.updating.includes(payload.device.id)) {
      throw new Error('devices-module.devices.update.inProgress')
    }

    if (!Device.query().where('id', payload.device.id).exists()) {
      throw new Error('devices-module.devices.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.device.id,
    })

    try {
      await Device.update({
        where: payload.device.id,
        data: payload.data,
      })
    } catch (e: any) {
      throw new OrmError(
        'devices-module.devices.update.failed',
        e,
        'Edit device failed.',
      )
    }

    const updatedEntity = Device.find(payload.device.id)

    if (updatedEntity === null) {
      // Updated entity could not be loaded from database
      await Device.get(
        payload.device.id,
        false,
      )

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.device.id,
      })

      throw new Error('devices-module.devices.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.device.id,
      })

      return Device.find(payload.device.id)
    } else {
      try {
        await Device.api().patch(
          `${ModuleApiPrefix}/v1/devices/${updatedEntity.id}?include=properties,controls`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Device.find(payload.device.id)
      } catch (e: any) {
        // Updating entity on api failed, we need to refresh entity
        await Device.get(
          payload.device.id,
          false,
        )

        throw new ApiError(
          'devices-module.devices.update.failed',
          e,
          'Edit device failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.device.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { device: DeviceInterface }): Promise<Item<Device>> {
    if (state.semaphore.updating.includes(payload.device.id)) {
      throw new Error('devices-module.devices.save.inProgress')
    }

    if (!Device.query().where('id', payload.device.id).where('draft', true).exists()) {
      throw new Error('devices-module.devices.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.device.id,
    })

    const entityToSave = Device.find(payload.device.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.device.id,
      })

      throw new Error('devices-module.devices.save.failed')
    }

    try {
      await Device.api().post(
        `${ModuleApiPrefix}/v1/devices?include=properties,controls`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Device.find(payload.device.id)
    } catch (e: any) {
      throw new ApiError(
        'devices-module.devices.save.failed',
        e,
        'Save draft device failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.device.id,
      })
    }
  },

  async remove({ state, commit }, payload: { device: DeviceInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.device.id)) {
      throw new Error('devices-module.devices.delete.inProgress')
    }

    if (!Device.query().where('id', payload.device.id).exists()) {
      return true
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.device.id,
    })

    try {
      await Device.delete(payload.device.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.device.id,
      })

      throw new OrmError(
        'devices-module.devices.delete.failed',
        e,
        'Delete device failed.',
      )
    }

    if (payload.device.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.device.id,
      })

      return true
    } else {
      try {
        await Device.api().delete(
          `${ModuleApiPrefix}/v1/devices/${payload.device.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        // Deleting entity on api failed, we need to refresh entity
        await Device.get(
          payload.device.id,
          false,
        )

        throw new OrmError(
          'devices-module.devices.delete.failed',
          e,
          'Delete device failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.device.id,
        })
      }
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.DEVICE_ENTITY_REPORTED,
        RoutingKeys.DEVICE_ENTITY_CREATED,
        RoutingKeys.DEVICE_ENTITY_UPDATED,
        RoutingKeys.DEVICE_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Device.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.DEVICE_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.DEVICE_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Device.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'devices-module.devices.delete.failed',
            e,
            'Delete device failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.DEVICE_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.DEVICE_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.DEVICE_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | ConnectionState | HardwareManufacturer | DeviceModel | FirmwareManufacturer | boolean | string[] | null | undefined } = {}

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'type') {
              if (payload.routingKey === RoutingKeys.DEVICE_ENTITY_CREATED) {
                entityData[camelName] = `${payload.source}/device/${body[attrName]}`
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Device.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          // Updating entity on api failed, we need to refresh entity
          await Device.get(
            body.id,
            false,
          )

          throw new OrmError(
            'devices-module.devices.update.failed',
            e,
            'Edit device failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.DEVICE_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

    DeviceProperty.reset()
    DeviceControl.reset()

    Channel.reset()
  },
}

const moduleMutations: MutationTree<DeviceState> = {
  ['SET_FIRST_LOAD'](state: DeviceState, action: boolean): void {
    state.firstLoad = action
  },

  ['SET_SEMAPHORE'](state: DeviceState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items = true
        break

      case SemaphoreTypes.GETTING:
        state.semaphore.fetching.item.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.fetching.item = uniq(state.semaphore.fetching.item)
        break

      case SemaphoreTypes.CREATING:
        state.semaphore.creating.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.creating = uniq(state.semaphore.creating)
        break

      case SemaphoreTypes.UPDATING:
        state.semaphore.updating.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.updating = uniq(state.semaphore.updating)
        break

      case SemaphoreTypes.DELETING:
        state.semaphore.deleting.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.deleting = uniq(state.semaphore.deleting)
        break
    }
  },

  ['CLEAR_SEMAPHORE'](state: DeviceState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items = false
        break

      case SemaphoreTypes.GETTING:
        // Process all semaphore items
        state.semaphore.fetching.item
          .forEach((item: string, index: number): void => {
            // Find created item in reading one item semaphore...
            if (item === get(action, 'id', 'notValid')) {
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
            if (item === get(action, 'id', 'notValid')) {
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
            if (item === get(action, 'id', 'notValid')) {
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
            if (item === get(action, 'id', 'notValid')) {
              // ...and remove it
              state.semaphore.deleting.splice(index, 1)
            }
          })
        break
    }
  },

  ['RESET_STATE'](state: DeviceState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): DeviceState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
