import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema from '@fastybird/metadata/resources/schemas/modules/triggers-module/entity.action.json'
import {
  ActionEntity as ExchangeEntity,
  TriggersModuleRoutes as RoutingKeys,
  ActionType,
} from '@fastybird/metadata'

import {
  ActionTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { v4 as uuid } from 'uuid'
import { AxiosResponse } from 'axios'
import uniq from 'lodash/uniq'

import Trigger from '@/lib/models/triggers/Trigger'
import { TriggerInterface } from '@/lib/models/triggers/types'
import Action from '@/lib/models/actions/Action'
import {
  ActionEntityTypes,
  ActionInterface,
  ActionResponseInterface,
  ActionsResponseInterface,
  CreateChannelPropertyActionInterface,
  CreateDevicePropertyActionInterface,
  UpdateChannelPropertyActionInterface,
  UpdateDevicePropertyActionInterface,
} from '@/lib/models/actions/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  ActionJsonModelInterface,
  ModuleApiPrefix,
  SemaphoreTypes,
} from '@/lib/types'

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

interface ActionState {
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
  dataTransformer: (result: AxiosResponse<ActionResponseInterface> | AxiosResponse<ActionsResponseInterface>): ActionJsonModelInterface | ActionJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ActionJsonModelInterface | ActionJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ActionState = {

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

const moduleActions: ActionTree<ActionState, any> = {
  async get({ state, commit }, payload: { trigger: TriggerInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Action.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/actions/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.actions.get.failed',
        e,
        'Fetching action failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async add({ commit }, payload: { trigger: TriggerInterface, id?: string | null, draft?: boolean, data: CreateDevicePropertyActionInterface | CreateChannelPropertyActionInterface }): Promise<Item<Action>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Action.insert({
        data: Object.assign({}, payload.data, { id, draft, triggerId: payload.trigger.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'triggers-module.actions.create.failed',
        e,
        'Create new action failed.',
      )
    }

    const createdEntity = Action.find(id)

    if (createdEntity === null) {
      await Action.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('triggers-module.actions.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Action.find(id)
    } else {
      try {
        await Action.api().post(
          `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/actions`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Action.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Action.delete(id)

        throw new ApiError(
          'triggers-module.actions.create.failed',
          e,
          'Create new action failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { action: ActionInterface, data: UpdateDevicePropertyActionInterface | UpdateChannelPropertyActionInterface }): Promise<Item<Action>> {
    if (state.semaphore.updating.includes(payload.action.id)) {
      throw new Error('triggers-module.actions.update.inProgress')
    }

    if (!Action.query().where('id', payload.action.id).exists()) {
      throw new Error('triggers-module.actions.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.action.id,
    })

    try {
      await Action.update({
        where: payload.action.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.action.id,
      })

      throw new OrmError(
        'triggers-module.actions.update.failed',
        e,
        'Edit action failed.',
      )
    }

    const updatedEntity = Action.find(payload.action.id)

    if (updatedEntity === null) {
      const trigger = Trigger.find(payload.action.triggerId)

      if (trigger !== null) {
        // Updated entity could not be loaded from database
        await Action.get(
          trigger,
          payload.action.id,
        )
      }

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.action.id,
      })

      throw new Error('triggers-module.actions.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.action.id,
      })

      return Action.find(payload.action.id)
    } else {
      try {
        await Action.api().patch(
          `${ModuleApiPrefix}/v1/triggers/${updatedEntity.triggerId}/actions/${updatedEntity.id}`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Action.find(payload.action.id)
      } catch (e: any) {
        const trigger = Trigger.find(payload.action.triggerId)

        if (trigger !== null) {
          // Updating entity on api failed, we need to refresh entity
          await Action.get(
            trigger,
            payload.action.id,
          )
        }

        throw new ApiError(
          'triggers-module.actions.update.failed',
          e,
          'Edit action failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.action.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { action: ActionInterface }): Promise<Item<Action>> {
    if (state.semaphore.updating.includes(payload.action.id)) {
      throw new Error('triggers-module.actions.save.inProgress')
    }

    if (!Action.query().where('id', payload.action.id).where('draft', true).exists()) {
      throw new Error('triggers-module.actions.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.action.id,
    })

    const entityToSave = Action.find(payload.action.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.action.id,
      })

      throw new Error('triggers-module.actions.save.failed')
    }

    try {
      await Action.api().post(
        `${ModuleApiPrefix}/v1/triggers/${entityToSave.triggerId}/actions`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Action.find(payload.action.id)
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.actions.save.failed',
        e,
        'Save draft action failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.action.id,
      })
    }
  },

  async remove({ state, commit }, payload: { action: ActionInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.action.id)) {
      throw new Error('triggers-module.actions.delete.inProgress')
    }

    if (!Action.query().where('id', payload.action.id).exists()) {
      throw new Error('triggers-module.actions.delete.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.action.id,
    })

    try {
      await Action.delete(payload.action.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.action.id,
      })

      throw new OrmError(
        'triggers-module.actions.delete.failed',
        e,
        'Delete action failed.',
      )
    }

    if (payload.action.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.action.id,
      })

      return true
    } else {
      try {
        await Action.api().delete(
          `${ModuleApiPrefix}/v1/triggers/${payload.action.triggerId}/actions/${payload.action.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        const trigger = await Trigger.find(payload.action.triggerId)

        if (trigger !== null) {
          // Replacing backup failed, we need to refresh whole list
          await Action.get(
            trigger,
            payload.action.id,
          )
        }

        throw new ApiError(
          'triggers-module.actions.delete.failed',
          e,
          'Delete action failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.action.id,
        })
      }
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.ACTIONS_ENTITY_REPORTED,
        RoutingKeys.ACTIONS_ENTITY_CREATED,
        RoutingKeys.ACTIONS_ENTITY_UPDATED,
        RoutingKeys.ACTIONS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Action.query().where('id', body.id).exists()
        && payload.routingKey === RoutingKeys.ACTIONS_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.ACTIONS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Action.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'triggers-module.actions.delete.failed',
            e,
            'Delete action failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.ACTIONS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.ACTIONS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.ACTIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {}

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'trigger') {
              const trigger = Trigger.query().where('id', body[attrName]).first()

              if (trigger !== null) {
                entityData.triggerId = trigger.id
              }
            } else if (camelName === 'type') {
              switch (body[attrName]) {
                case ActionType.DEVICE_PROPERTY:
                  entityData[camelName] = ActionEntityTypes.DEVICE_PROPERTY
                  break

                case ActionType.CHANNEL_PROPERTY:
                  entityData[camelName] = ActionEntityTypes.CHANNEL_PROPERTY
                  break

                default:
                  entityData[camelName] = body[attrName]
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Action.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const trigger = Trigger.query().where('id', body.trigger).first()

          if (trigger !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Action.get(
              trigger,
              body.id,
            )
          }

          throw new OrmError(
            'triggers-module.actions.update.failed',
            e,
            'Edit action failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.ACTIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<ActionState> = {
  ['SET_SEMAPHORE'](state: ActionState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: ActionState, action: SemaphoreAction): void {
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
            // Find created item in creating semaphore...
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

  ['RESET_STATE'](state: ActionState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ActionState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
