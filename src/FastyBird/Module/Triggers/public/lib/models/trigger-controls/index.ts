import { RpCallResponse } from '@fastybird/vue-wamp-v1'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/triggers-module/entity.trigger.control.json'
import {
  TriggerControlEntity as ExchangeEntity,
  TriggersModuleRoutes as RoutingKeys,
  ActionRoutes,
  DataType, ControlAction,
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

import Trigger from '@/lib/models/triggers/Trigger'
import { TriggerInterface } from '@/lib/models/triggers/types'
import TriggerControl from '@/lib/models/trigger-controls/TriggerControl'
import {
  TriggerControlEntityTypes,
  TriggerControlInterface,
  TriggerControlResponseInterface,
  TriggerControlsResponseInterface,
} from '@/lib/models/trigger-controls/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import { TriggerControlJsonModelInterface, ModuleApiPrefix, SemaphoreTypes } from '@/lib/types'

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

interface TriggerControlState {
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
  dataTransformer: (result: AxiosResponse<TriggerControlResponseInterface> | AxiosResponse<TriggerControlsResponseInterface>): TriggerControlJsonModelInterface | TriggerControlJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as TriggerControlJsonModelInterface | TriggerControlJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: TriggerControlState = {

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

const moduleActions: ActionTree<TriggerControlState, unknown> = {
  async get({ state, commit }, payload: { trigger: TriggerInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await TriggerControl.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/controls/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.trigger-controls.fetch.failed',
        e,
        'Fetching trigger control failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }, payload: { trigger: TriggerInterface }): Promise<boolean> {
    if (state.semaphore.fetching.items.includes(payload.trigger.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
      id: payload.trigger.id,
    })

    try {
      await TriggerControl.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/controls`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.trigger-controls.fetch.failed',
        e,
        'Fetching trigger controls failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.trigger.id,
      })
    }
  },

  async transmitCommand(_store, payload: { control: TriggerControlInterface, value?: string | number | boolean | null }): Promise<boolean> {
    if (!TriggerControl.query().where('id', payload.control.id).exists()) {
      throw new Error('triggers-module.trigger-controls.transmit.failed')
    }

    const trigger = Trigger.find(payload.control.triggerId)

    if (trigger === null) {
      throw new Error('triggers-module.trigger-controls.transmit.failed')
    }

    return new Promise((resolve, reject) => {
      TriggerControl.wamp().call<{ data: string }>({
        routing_key: ActionRoutes.TRIGGER,
        source: TriggerControl.$triggersModuleSource,
        data: {
          action: ControlAction.SET,
          trigger: trigger.id,
          control: payload.control.id,
          expected_value: payload.value,
        },
      })
        .then((response: RpCallResponse<{ data: string }>): void => {
          if (get(response.data, 'response') === 'accepted') {
            resolve(true)
          } else {
            reject(new Error('triggers-module.trigger-controls.transmit.failed'))
          }
        })
        .catch((): void => {
          reject(new Error('triggers-module.trigger-controls.transmit.failed'))
        })
    })
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.TRIGGERS_CONTROL_ENTITY_REPORTED,
        RoutingKeys.TRIGGERS_CONTROL_ENTITY_CREATED,
        RoutingKeys.TRIGGERS_CONTROL_ENTITY_UPDATED,
        RoutingKeys.TRIGGERS_CONTROL_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !TriggerControl.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await TriggerControl.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'triggers-module.trigger-controls.delete.failed',
            e,
            'Delete trigger control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: string | boolean | number | string[] | number[] | DataType | null | undefined } = {
          type: TriggerControlEntityTypes.CONTROL,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'trigger') {
              const trigger = Trigger.query().where('id', body[attrName]).first()

              if (trigger !== null) {
                entityData.triggerId = trigger.id
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await TriggerControl.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = TriggerControl.query().with('trigger').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.trigger !== null) {
            // Updating entity on api failed, we need to refresh entity
            await TriggerControl.get(
              failedEntity.trigger,
              body.id,
            )
          }

          throw new OrmError(
            'triggers-module.trigger-controls.update.failed',
            e,
            'Edit trigger control failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.TRIGGERS_CONTROL_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<TriggerControlState> = {
  ['SET_SEMAPHORE'](state: TriggerControlState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: TriggerControlState, action: SemaphoreAction): void {
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

  ['RESET_STATE'](state: TriggerControlState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): TriggerControlState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
