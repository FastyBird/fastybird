import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/triggers-module/entity.condition.json'
import {
  ConditionEntity as ExchangeEntity,
  TriggersModuleRoutes as RoutingKeys,
  ConditionType,
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
import Condition from '@/lib/models/conditions/Condition'
import {
  ConditionEntityTypes,
  ConditionInterface,
  ConditionResponseInterface,
  ConditionsResponseInterface,
  CreateChannelPropertyConditionInterface,
  CreateDateConditionInterface,
  CreateDevicePropertyConditionInterface,
  CreateTimeConditionInterface,
  UpdateChannelPropertyConditionInterface,
  UpdateDateConditionInterface,
  UpdateDevicePropertyConditionInterface,
  UpdateTimeConditionInterface,
} from '@/lib/models/conditions/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  ConditionJsonModelInterface,
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

interface ConditionState {
  semaphore: SemaphoreState
}

interface SemaphoreCondition {
  type: SemaphoreTypes
  id: string
}

const jsonApiFormatter = new Jsona({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const apiOptions = {
  dataTransformer: (result: AxiosResponse<ConditionResponseInterface> | AxiosResponse<ConditionsResponseInterface>): ConditionJsonModelInterface | ConditionJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as ConditionJsonModelInterface | ConditionJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: ConditionState = {

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

const moduleActions: ActionTree<ConditionState, any> = {
  async get({ state, commit }, payload: { trigger: TriggerInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Condition.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/conditions/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.conditions.get.failed',
        e,
        'Fetching condition failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async add({ commit }, payload: { trigger: TriggerInterface, id?: string | null, draft?: boolean, data: CreateDevicePropertyConditionInterface | CreateChannelPropertyConditionInterface | CreateDateConditionInterface | CreateTimeConditionInterface }): Promise<Item<Condition>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Condition.insert({
        data: Object.assign({}, payload.data, { id, draft, triggerId: payload.trigger.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'triggers-module.conditions.create.failed',
        e,
        'Create new condition failed.',
      )
    }

    const createdEntity = Condition.find(id)

    if (createdEntity === null) {
      await Condition.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('triggers-module.conditions.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Condition.find(id)
    } else {
      try {
        await Condition.api().post(
          `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/conditions`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Condition.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Condition.delete(id)

        throw new ApiError(
          'triggers-module.conditions.create.failed',
          e,
          'Create new condition failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { condition: ConditionInterface, data: UpdateDevicePropertyConditionInterface | UpdateChannelPropertyConditionInterface | UpdateDateConditionInterface | UpdateTimeConditionInterface }): Promise<Item<Condition>> {
    if (state.semaphore.updating.includes(payload.condition.id)) {
      throw new Error('triggers-module.conditions.update.inProgress')
    }

    if (!Condition.query().where('id', payload.condition.id).exists()) {
      throw new Error('triggers-module.conditions.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.condition.id,
    })

    try {
      await Condition.update({
        where: payload.condition.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.condition.id,
      })

      throw new OrmError(
        'triggers-module.conditions.update.failed',
        e,
        'Edit condition failed.',
      )
    }

    const updatedEntity = Condition.find(payload.condition.id)

    if (updatedEntity === null) {
      const trigger = Trigger.find(payload.condition.triggerId)

      if (trigger !== null) {
        // Updated entity could not be loaded from database
        await Condition.get(
          trigger,
          payload.condition.id,
        )
      }

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.condition.id,
      })

      throw new Error('triggers-module.conditions.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.condition.id,
      })

      return Condition.find(payload.condition.id)
    } else {
      try {
        await Condition.api().patch(
          `${ModuleApiPrefix}/v1/triggers/${updatedEntity.triggerId}/conditions/${updatedEntity.id}`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Condition.find(payload.condition.id)
      } catch (e: any) {
        const trigger = Trigger.find(payload.condition.triggerId)

        if (trigger !== null) {
          // Updating entity on api failed, we need to refresh entity
          await Condition.get(
            trigger,
            payload.condition.id,
          )
        }

        throw new ApiError(
          'triggers-module.conditions.update.failed',
          e,
          'Edit condition failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.condition.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { condition: ConditionInterface }): Promise<Item<Condition>> {
    if (state.semaphore.updating.includes(payload.condition.id)) {
      throw new Error('triggers-module.conditions.save.inProgress')
    }

    if (!Condition.query().where('id', payload.condition.id).where('draft', true).exists()) {
      throw new Error('triggers-module.conditions.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.condition.id,
    })

    const entityToSave = Condition.find(payload.condition.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.condition.id,
      })

      throw new Error('triggers-module.conditions.save.failed')
    }

    try {
      await Condition.api().post(
        `${ModuleApiPrefix}/v1/triggers/${entityToSave.triggerId}/conditions`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Condition.find(payload.condition.id)
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.conditions.save.failed',
        e,
        'Save draft condition failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.condition.id,
      })
    }
  },

  async remove({ state, commit }, payload: { condition: ConditionInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.condition.id)) {
      throw new Error('triggers-module.conditions.delete.inProgress')
    }

    if (!Condition.query().where('id', payload.condition.id).exists()) {
      throw new Error('triggers-module.conditions.delete.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.condition.id,
    })

    try {
      await Condition.delete(payload.condition.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.condition.id,
      })

      throw new OrmError(
        'triggers-module.conditions.delete.failed',
        e,
        'Delete condition failed.',
      )
    }

    if (payload.condition.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.condition.id,
      })

      return true
    } else {
      try {
        await Condition.api().delete(
          `${ModuleApiPrefix}/v1/triggers/${payload.condition.triggerId}/conditions/${payload.condition.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        const trigger = await Trigger.find(payload.condition.triggerId)

        if (trigger !== null) {
          // Replacing backup failed, we need to refresh whole list
          await Condition.get(
            trigger,
            payload.condition.id,
          )
        }

        throw new ApiError(
          'triggers-module.conditions.delete.failed',
          e,
          'Delete condition failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.condition.id,
        })
      }
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.CONDITIONS_ENTITY_REPORTED,
        RoutingKeys.CONDITIONS_ENTITY_CREATED,
        RoutingKeys.CONDITIONS_ENTITY_UPDATED,
        RoutingKeys.CONDITIONS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Condition.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Condition.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'triggers-module.conditions.delete.failed',
            e,
            'Delete condition failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
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
                case ConditionType.DEVICE_PROPERTY:
                  entityData[camelName] = ConditionEntityTypes.DEVICE_PROPERTY
                  break

                case ConditionType.CHANNEL_PROPERTY:
                  entityData[camelName] = ConditionEntityTypes.CHANNEL_PROPERTY
                  break

                case ConditionType.TIME:
                  entityData[camelName] = ConditionEntityTypes.TIME
                  break

                case ConditionType.DATE:
                  entityData[camelName] = ConditionEntityTypes.DATE
                  break

                default:
                  entityData[camelName] = body[attrName]
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Condition.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const trigger = Trigger.query().where('id', body.trigger).first()

          if (trigger !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Condition.get(
              trigger,
              body.id,
            )
          }

          throw new OrmError(
            'triggers-module.conditions.update.failed',
            e,
            'Edit condition failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.CONDITIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<ConditionState> = {
  ['SET_SEMAPHORE'](state: ConditionState, action: SemaphoreCondition): void {
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

  ['CLEAR_SEMAPHORE'](state: ConditionState, action: SemaphoreCondition): void {
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

  ['RESET_STATE'](state: ConditionState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): ConditionState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
