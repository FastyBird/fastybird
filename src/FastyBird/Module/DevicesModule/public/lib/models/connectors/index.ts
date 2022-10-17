import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/devices-module/entity.connector.json'
import {
  ConnectorEntity as ExchangeEntity,
  DevicesModuleRoutes as RoutingKeys,
} from '@fastybird/metadata'

import {
  ActionTree,
  GetterTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { AxiosResponse } from 'axios'
import get from 'lodash/get'
import uniq from 'lodash/uniq'

import Connector from '@/lib/models/connectors/Connector'
import {
  ConnectorInterface,
  ConnectorResponseInterface,
  ConnectorsResponseInterface,
  FbBusConnectorUpdateInterface,
  FbMqttConnectorUpdateInterface,
  Connector\ModbusUpdateInterface,
  ShellyConnectorUpdateInterface,
  SonoffConnectorUpdateInterface,
  TuyaUpdateInterface,
} from '@/lib/models/connectors/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { ModuleApiPrefix, ConnectorJsonModelInterface, SemaphoreTypes } from '@/lib/types'
import ConnectorControl from '@/lib/models/connector-controls/ConnectorControl'

interface SemaphoreFetchingState {
  items: boolean
  item: string[]
}

interface SemaphoreState {
  fetching: SemaphoreFetchingState
  updating: string[]
}

interface ConnectorState {
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
  dataTransformer: (result: AxiosResponse<ConnectorResponseInterface> | AxiosResponse<ConnectorsResponseInterface>): ConnectorJsonModelInterface | ConnectorJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ConnectorJsonModelInterface | ConnectorJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ConnectorState = {

  semaphore: {
    fetching: {
      items: false,
      item: [],
    },
    updating: [],
  },

  firstLoad: false,
}

const moduleGetters: GetterTree<ConnectorState, unknown> = {
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

const moduleActions: ActionTree<ConnectorState, unknown> = {
  async get({ state, commit }, payload: { id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Connector.api().get(
        `${ModuleApiPrefix}/v1/connectors/${payload.id}?include=controls`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.connectors.fetch.failed',
        e,
        'Fetching connectors failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }): Promise<boolean> {
    if (state.semaphore.fetching.items) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
    })

    try {
      await Connector.api().get(
        `${ModuleApiPrefix}/v1/connectors?include=controls`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD', true)

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.connectors.fetch.failed',
        e,
        'Fetching connectors failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
      })
    }
  },

  async edit({ state, commit }, payload: { connector: ConnectorInterface, data: FbMqttConnectorUpdateInterface | FbBusConnectorUpdateInterface | ShellyConnectorUpdateInterface | TuyaUpdateInterface | SonoffConnectorUpdateInterface | Connector\ModbusUpdateInterface }): Promise<Item<Connector>> {
    if (state.semaphore.updating.includes(payload.connector.id)) {
      throw new Error('devices-module.connectors.update.inProgress')
    }

    if (!Connector.query().where('id', payload.connector.id).exists()) {
      throw new Error('devices-module.connectors.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.connector.id,
    })

    try {
      await Connector.update({
        where: payload.connector.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.connector.id,
      })

      throw new OrmError(
        'devices-module.connectors.update.failed',
        e,
        'Edit connector failed.',
      )
    }

    const updatedEntity = Connector.find(payload.connector.id)

    if (updatedEntity === null) {
      // Updated entity could not be loaded from database
      await Connector.get(
        payload.connector.id,
      )

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.connector.id,
      })

      throw new Error('devices-module.connectors.update.failed')
    }

    try {
      await Connector.api().patch(
        `${ModuleApiPrefix}/v1/connectors/${updatedEntity.id}?include=controls`,
        jsonApiFormatter.serialize({
          stuff: updatedEntity,
        }),
        apiOptions,
      )

      return Connector.find(payload.connector.id)
    } catch (e: any) {
      // Updating entity on api failed, we need to refresh entity
      await Connector.get(
        payload.connector.id,
      )

      throw new ApiError(
        'devices-module.connectors.update.failed',
        e,
        'Edit connector failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.connector.id,
      })
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.CONNECTOR_ENTITY_REPORTED,
        RoutingKeys.CONNECTOR_ENTITY_CREATED,
        RoutingKeys.CONNECTOR_ENTITY_UPDATED,
        RoutingKeys.CONNECTOR_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Connector.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Connector.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'devices-module.connectors.delete.failed',
            e,
            'Delete connector failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | number | string[] | boolean | null | undefined } = {}

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'type') {
              if (payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_CREATED) {
                entityData[camelName] = `${payload.source}/connector/${body[attrName]}`
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Connector.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          // Updating entity on api failed, we need to refresh entity
          await Connector.get(
            body.id,
          )

          throw new OrmError(
            'devices-module.connectors.update.failed',
            e,
            'Edit connector failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.CONNECTOR_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

    ConnectorControl.reset()
  },
}

const moduleMutations: MutationTree<ConnectorState> = {
  ['SET_FIRST_LOAD'](state: ConnectorState, action: boolean): void {
    state.firstLoad = action
  },

  ['SET_SEMAPHORE'](state: ConnectorState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items = true
        break

      case SemaphoreTypes.GETTING:
        state.semaphore.fetching.item.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.fetching.item = uniq(state.semaphore.fetching.item)
        break

      case SemaphoreTypes.UPDATING:
        state.semaphore.updating.push(get(action, 'id', 'notValid'))

        // Make all keys uniq
        state.semaphore.updating = uniq(state.semaphore.updating)
        break
    }
  },

  ['CLEAR_SEMAPHORE'](state: ConnectorState, action: SemaphoreAction): void {
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
    }
  },

  ['RESET_STATE'](state: ConnectorState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ConnectorState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
