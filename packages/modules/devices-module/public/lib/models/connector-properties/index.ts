import { Item } from '@vuex-orm/core'
import { RpCallResponse } from '@fastybird/vue-wamp-v1'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/devices-module/entity.connector.property.json'
import {
  ConnectorPropertyEntity as ExchangeEntity,
  DevicesModuleRoutes as RoutingKeys,
  ActionRoutes,
  DataType,
  normalizeValue,
  PropertyAction,
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
import ConnectorProperty from '@/lib/models/connector-properties/ConnectorProperty'
import {
  ConnectorPropertyInterface,
  ConnectorPropertyResponseInterface,
  ConnectorPropertiesResponseInterface,
  ConnectorPropertyUpdateInterface,
  ConnectorPropertyCreateInterface,
} from '@/lib/models/connector-properties/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { ConnectorPropertyJsonModelInterface, ModuleApiPrefix, SemaphoreTypes } from '@/lib/types'
import { v4 as uuid } from 'uuid'

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

interface ConnectorPropertyState {
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
  dataTransformer: (result: AxiosResponse<ConnectorPropertyResponseInterface> | AxiosResponse<ConnectorPropertiesResponseInterface>): ConnectorPropertyJsonModelInterface | ConnectorPropertyJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ConnectorPropertyJsonModelInterface | ConnectorPropertyJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ConnectorPropertyState = {

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

const moduleActions: ActionTree<ConnectorPropertyState, unknown> = {
  async get({ state, commit }, payload: { connector: ConnectorInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await ConnectorProperty.api().get(
        `${ModuleApiPrefix}/v1/connectors/${payload.connector.id}/properties/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.connector-properties.fetch.failed',
        e,
        'Fetching connector property failed.',
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
      await ConnectorProperty.api().get(
        `${ModuleApiPrefix}/v1/connectors/${payload.connector.id}/properties`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'devices-module.connector-properties.fetch.failed',
        e,
        'Fetching connector properties failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.connector.id,
      })
    }
  },

  async add({ commit }, payload: { connector: ConnectorInterface, id?: string | null, draft?: boolean, data: ConnectorPropertyCreateInterface }): Promise<Item<ConnectorProperty>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await ConnectorProperty.insert({
        data: Object.assign({}, payload.data, { id, draft, connectorId: payload.connector.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'devices-module.connector-properties.create.failed',
        e,
        'Create new connector property failed.',
      )
    }

    const createdEntity = ConnectorProperty.find(id)

    if (createdEntity === null) {
      await ConnectorProperty.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('devices-module.connector-properties.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return ConnectorProperty.find(id)
    } else {
      try {
        await ConnectorProperty.api().post(
          `${ModuleApiPrefix}/v1/connectors/${payload.connector.id}/properties`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return ConnectorProperty.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await ConnectorProperty.delete(id)

        throw new ApiError(
          'devices-module.connector-properties.create.failed',
          e,
          'Create new connector property failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { property: ConnectorPropertyInterface, data: ConnectorPropertyUpdateInterface }): Promise<Item<ConnectorProperty>> {
    if (state.semaphore.updating.includes(payload.property.id)) {
      throw new Error('devices-module.connector-properties.update.inProgress')
    }

    if (!ConnectorProperty.query().where('id', payload.property.id).exists()) {
      throw new Error('devices-module.connector-properties.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.property.id,
    })

    try {
      await ConnectorProperty.update({
        where: payload.property.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.property.id,
      })

      throw new OrmError(
        'devices-module.connector-properties.update.failed',
        e,
        'Edit connector property failed.',
      )
    }

    const updatedEntity = ConnectorProperty.find(payload.property.id)

    if (updatedEntity === null) {
      const propertyConnector = Connector.find(payload.property.connectorId)

      if (propertyConnector !== null) {
        // Updated entity could not be loaded from database
        await ConnectorProperty.get(
          propertyConnector,
          payload.property.id,
        )
      }

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.property.id,
      })

      throw new Error('devices-module.connector-properties.update.failed')
    }

    const connector = Connector.find(payload.property.connectorId)

    if (connector === null) {
      throw new Error('devices-module.connector-properties.update.failed')
    }

    try {
      await ConnectorProperty.api().patch(
        `${ModuleApiPrefix}/v1/connectors/${updatedEntity.connectorId}/properties/${updatedEntity.id}`,
        jsonApiFormatter.serialize({
          stuff: updatedEntity,
        }),
        apiOptions,
      )

      return ConnectorProperty.find(payload.property.id)
    } catch (e: any) {
      // Updating entity on api failed, we need to refresh entity
      await ConnectorProperty.get(
        connector,
        payload.property.id,
      )

      throw new ApiError(
        'devices-module.connector-properties.update.failed',
        e,
        'Edit connector property failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.property.id,
      })
    }
  },

  async save({ state, commit }, payload: { property: ConnectorPropertyInterface }): Promise<Item<ConnectorProperty>> {
    if (state.semaphore.updating.includes(payload.property.id)) {
      throw new Error('devices-module.connector-properties.save.inProgress')
    }

    if (!ConnectorProperty.query().where('id', payload.property.id).where('draft', true).exists()) {
      throw new Error('devices-module.connector-properties.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.property.id,
    })

    const entityToSave = ConnectorProperty.find(payload.property.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.property.id,
      })

      throw new Error('devices-module.connector-properties.save.failed')
    }

    const connector = Connector.find(payload.property.connectorId)

    if (connector === null) {
      throw new Error('devices-module.connector-properties.save.failed')
    }

    try {
      await ConnectorProperty.api().post(
        `${ModuleApiPrefix}/v1/connectors/${entityToSave.connectorId}/properties`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return ConnectorProperty.find(payload.property.id)
    } catch (e: any) {
      throw new ApiError(
        'devices-module.connector-properties.save.failed',
        e,
        'Save draft connector property failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.property.id,
      })
    }
  },

  async remove({ state, commit }, payload: { property: ConnectorPropertyInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.property.id)) {
      throw new Error('devices-module.connector-properties.delete.inProgress')
    }

    if (!ConnectorProperty.query().where('id', payload.property.id).exists()) {
      return true
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.property.id,
    })

    try {
      await ConnectorProperty.delete(payload.property.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.property.id,
      })

      throw new OrmError(
        'devices-module.connector-properties.delete.failed',
        e,
        'Delete connector property failed.',
      )
    }

    if (payload.property.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.property.id,
      })

      return true
    } else {
      const connector = Connector.find(payload.property.connectorId)

      if (connector === null) {
        throw new Error('devices-module.connector-properties.save.failed')
      }

      try {
        await ConnectorProperty.api().delete(
          `${ModuleApiPrefix}/v1/connectors/${payload.property.connectorId}/properties/${payload.property.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        // Replacing backup failed, we need to refresh whole list
        await ConnectorProperty.get(
          connector,
          payload.property.id,
        )

        throw new OrmError(
          'devices-module.connector-properties.delete.failed',
          e,
          'Delete connector property failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.property.id,
        })
      }
    }
  },

  async transmitData(_store, payload: { property: ConnectorPropertyInterface, value: string }): Promise<boolean> {
    if (!ConnectorProperty.query().where('id', payload.property.id).exists()) {
      throw new Error('devices-module.connector-properties.transmit.failed')
    }

    const connector = Connector.find(payload.property.connectorId)

    if (connector === null) {
      throw new Error('devices-module.connector-properties.transmit.failed')
    }

    const backupValue = payload.property.actualValue

    const expectedValue = normalizeValue(payload.property.dataType, payload.value, payload.property.format)

    try {
      await ConnectorProperty.update({
        where: payload.property.id,
        data: {
          value: expectedValue,
        },
      })
    } catch (e: any) {
      throw new OrmError(
        'devices-module.connector-properties.transmit.failed',
        e,
        'Edit connector property failed.',
      )
    }

    return new Promise((resolve, reject) => {
      ConnectorProperty.wamp().call<{ data: string }>({
        routing_key: ActionRoutes.CONNECTOR_PROPERTY,
        source: ConnectorProperty.$devicesModuleSource,
        data: {
          action: PropertyAction.SET,
          connector: connector.id,
          property: payload.property.id,
          expected_value: expectedValue,
        },
      })
        .then((response: RpCallResponse<{ data: string }>): void => {
          if (get(response.data, 'response') === 'accepted') {
            resolve(true)
          } else {
            ConnectorProperty.update({
              where: payload.property.id,
              data: {
                value: backupValue,
              },
            })

            reject(new Error('devices-module.connector-properties.transmit.failed'))
          }
        })
        .catch((): void => {
          ConnectorProperty.update({
            where: payload.property.id,
            data: {
              value: backupValue,
            },
          })

          reject(new Error('devices-module.connector-properties.transmit.failed'))
        })
    })
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.CONNECTORS_PROPERTY_ENTITY_REPORTED,
        RoutingKeys.CONNECTORS_PROPERTY_ENTITY_CREATED,
        RoutingKeys.CONNECTORS_PROPERTY_ENTITY_UPDATED,
        RoutingKeys.CONNECTORS_PROPERTY_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !ConnectorProperty.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await ConnectorProperty.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'devices-module.connector-properties.delete.failed',
            e,
            'Delete connector property failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | boolean | number | string[] | ((string | null)[])[] | (number | null)[] | DataType | null | undefined } = {}

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'type') {
              if (payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_CREATED) {
                entityData[camelName] = `${payload.source}/property/connector/${body[attrName]}`
              }
            } else if (camelName === 'connector') {
              const connector = Connector.query().where('id', body[attrName]).first()

              if (connector !== null) {
                entityData.connectorId = connector.id
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await ConnectorProperty.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = ConnectorProperty.query().with('connector').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.connector !== null) {
            // Updating entity on api failed, we need to refresh entity
            await ConnectorProperty.get(
              failedEntity.connector,
              body.id,
            )
          }

          throw new OrmError(
            'devices-module.connector-properties.update.failed',
            e,
            'Edit connector property failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.CONNECTORS_PROPERTY_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<ConnectorPropertyState> = {
  ['SET_SEMAPHORE'](state: ConnectorPropertyState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: ConnectorPropertyState, action: SemaphoreAction): void {
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

  ['RESET_STATE'](state: ConnectorPropertyState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ConnectorPropertyState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
