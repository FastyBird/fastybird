import { RpCallResponse } from '@fastybird/vue-wamp-v1'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/devices-module/entity.connector.control.json'
import {
  ConnectorControlEntity as ExchangeEntity,
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

import Connector from '@/lib/models/connectors/Connector'
import { ConnectorInterface } from '@/lib/models/connectors/types'
import ConnectorControl from '@/lib/models/connector-controls/ConnectorControl'
import {
  ConnectorControlInterface,
  ConnectorControlResponseInterface,
  ConnectorControlsResponseInterface,
} from '@/lib/models/connector-controls/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { ConnectorControlJsonModelInterface, ModuleApiPrefix, SemaphoreTypes } from '@/lib/types'

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

interface ConnectorControlState {
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
  dataTransformer: (result: AxiosResponse<ConnectorControlResponseInterface> | AxiosResponse<ConnectorControlsResponseInterface>): ConnectorControlJsonModelInterface | ConnectorControlJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ConnectorControlJsonModelInterface | ConnectorControlJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ConnectorControlState = {

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

const moduleActions: ActionTree<ConnectorControlState, unknown> = {
  async get({ state, commit }, payload: { connector: ConnectorInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await ConnectorControl.api().get(
        `${ModuleApiPrefix}/v1/connectors/${payload.connector.id}/controls/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'connectors-module.connector-controls.fetch.failed',
        e,
        'Fetching connector control failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }, payload: { connector: ConnectorInterface }): Promise<boolean> {
    if (state.semaphore.fetching.items.includes(payload.connector.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
      id: payload.connector.id,
    })

    try {
      await ConnectorControl.api().get(
        `${ModuleApiPrefix}/v1/connectors/${payload.connector.id}/controls`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'connectors-module.connector-controls.fetch.failed',
        e,
        'Fetching connector controls failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.connector.id,
      })
    }
  },

  async transmitCommand(_store, payload: { control: ConnectorControlInterface, value?: string | number | boolean | null }): Promise<boolean> {
    if (!ConnectorControl.query().where('id', payload.control.id).exists()) {
      throw new Error('connectors-module.connector-controls.transmit.failed')
    }

    const connector = Connector.find(payload.control.connectorId)

    if (connector === null) {
      throw new Error('connectors-module.connector-controls.transmit.failed')
    }

    return new Promise((resolve, reject) => {
      ConnectorControl.wamp().call<{ data: string }>({
        routing_key: ActionRoutes.CONNECTOR,
        source: ConnectorControl.$devicesModuleSource,
        data: {
          action: ControlAction.SET,
          connector: connector.id,
          control: payload.control.id,
          expected_value: payload.value,
        },
      })
        .then((response: RpCallResponse<{ data: string }>): void => {
          if (get(response.data, 'response') === 'accepted') {
            resolve(true)
          } else {
            reject(new Error('connectors-module.connector-controls.transmit.failed'))
          }
        })
        .catch((): void => {
          reject(new Error('connectors-module.connector-controls.transmit.failed'))
        })
    })
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.CONNECTORS_CONTROL_ENTITY_REPORTED,
        RoutingKeys.CONNECTORS_CONTROL_ENTITY_CREATED,
        RoutingKeys.CONNECTORS_CONTROL_ENTITY_UPDATED,
        RoutingKeys.CONNECTORS_CONTROL_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !ConnectorControl.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await ConnectorControl.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'connectors-module.connector-controls.delete.failed',
            e,
            'Delete connector control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | boolean | number | string[] | number[] | DataType | null | undefined } = {
          type: `${payload.source}/control/connector`,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'connector') {
              const connector = Connector.query().where('id', body[attrName]).first()

              if (connector !== null) {
                entityData.connectorId = connector.id
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await ConnectorControl.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = ConnectorControl.query().with('connector').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.connector !== null) {
            // Updating entity on api failed, we need to refresh entity
            await ConnectorControl.get(
              failedEntity.connector,
              body.id,
            )
          }

          throw new OrmError(
            'connectors-module.connector-controls.update.failed',
            e,
            'Edit connector control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.CONNECTORS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<ConnectorControlState> = {
  ['SET_SEMAPHORE'](state: ConnectorControlState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: ConnectorControlState, action: SemaphoreAction): void {
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

  ['RESET_STATE'](state: ConnectorControlState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ConnectorControlState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
