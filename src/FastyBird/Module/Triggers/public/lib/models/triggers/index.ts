import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/triggers-module/entity.trigger.json'
import {
  TriggerEntity as ExchangeEntity,
  TriggersModuleRoutes as RoutingKeys,
  TriggerType,
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

import Trigger from '@/lib/models/triggers/Trigger'
import {
  CreateAutomaticTriggerInterface,
  CreateManualTriggerInterface,
  TriggerEntityTypes,
  TriggerInterface,
  TriggerResponseInterface,
  TriggersResponseInterface,
  TriggerUpdateInterface,
} from '@/lib/models/triggers/types'
import Action from '@/lib/models/actions/Action'
import {
  ActionEntityTypes,
  ActionInterface,
  CreateChannelPropertyActionInterface,
  CreateDevicePropertyActionInterface,
} from '@/lib/models/actions/types'
import Condition from '@/lib/models/conditions/Condition'
import {
  ConditionEntityTypes,
  ConditionInterface,
  CreateChannelPropertyConditionInterface,
  CreateDateConditionInterface,
  CreateDevicePropertyConditionInterface,
  CreateTimeConditionInterface,
} from '@/lib/models/conditions/types'
import Notification from '@/lib/models/notifications/Notification'
import {
  CreateEmailNotificationInterface,
  CreateSmsNotificationInterface,
  NotificationEntityTypes,
  NotificationInterface,
} from '@/lib/models/notifications/types'
import TriggerControl from '@/lib/models/trigger-controls/TriggerControl'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  ModuleApiPrefix,
  SemaphoreTypes,
  TriggerJsonModelInterface,
} from '@/lib/types'

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

interface TriggerState {
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
  dataTransformer: (result: AxiosResponse<TriggerResponseInterface> | AxiosResponse<TriggersResponseInterface>): TriggerJsonModelInterface | TriggerJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as TriggerJsonModelInterface | TriggerJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: TriggerState = {

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

function buildCreateDevicePropertyAction(data: any): CreateDevicePropertyActionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    device: data.device,
    property: data.property,
    value: data.value,
  }
}

function buildCreateChannelPropertyAction(data: any): CreateChannelPropertyActionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    device: data.device,
    channel: data.channel,
    property: data.property,
    value: data.value,
  }
}

function buildCreateDevicePropertyCondition(data: any): CreateDevicePropertyConditionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    operator: data.operator,
    operand: data.operand,
    device: data.device,
    property: data.property,
  }
}

function buildCreateChannelPropertyCondition(data: any): CreateChannelPropertyConditionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    operator: data.operator,
    operand: data.operand,
    device: data.device,
    channel: data.channel,
    property: data.property,
  }
}

function buildCreateDateCondition(data: any): CreateDateConditionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    date: data.date,
  }
}

function buildCreateTimeCondition(data: any): CreateTimeConditionInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    time: data.time,
    days: data.days,
  }
}

function buildCreateSmsNotification(data: any): CreateSmsNotificationInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    phone: data.phone,
  }
}

function buildCreateEmailNotification(data: any): CreateEmailNotificationInterface {
  return {
    type: data.type,
    enabled: data.enabled,

    email: data.email,
  }
}

const moduleGetters: GetterTree<TriggerState, any> = {
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

const moduleActions: ActionTree<TriggerState, any> = {
  async get({ state, commit }, payload: { id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Trigger.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.id}?include=conditions,actions,notifications,controls`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.triggers.fetch.failed',
        e,
        'Fetching triggers failed.',
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
      await Trigger.api().get(
        `${ModuleApiPrefix}/v1/triggers?include=conditions,actions,notifications,controls`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD', true)

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.triggers.fetch.failed',
        e,
        'Fetching triggers failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
      })
    }
  },

  async add({ commit }, payload: { id?: string | null, draft?: boolean, data: CreateManualTriggerInterface | CreateAutomaticTriggerInterface }): Promise<Item<Trigger>> {
    const actions: (CreateDevicePropertyActionInterface | CreateChannelPropertyActionInterface)[] = []
    const conditions: (CreateDevicePropertyConditionInterface | CreateChannelPropertyConditionInterface | CreateDateConditionInterface | CreateTimeConditionInterface)[] = []
    const notifications: (CreateSmsNotificationInterface | CreateEmailNotificationInterface)[] = []

    for (const actionData of payload.data.actions) {
      if (actionData.type === ActionEntityTypes.DEVICE_PROPERTY) {
        actions.push(buildCreateDevicePropertyAction(actionData))
      } else if (actionData.type === ActionEntityTypes.CHANNEL_PROPERTY) {
        actions.push(buildCreateChannelPropertyAction(actionData))
      }
    }

    if (payload.data.type === TriggerEntityTypes.AUTOMATIC) {
      if (Object.prototype.hasOwnProperty.call(payload.data, 'conditions')) {
        // @ts-ignore
        for (const conditionData of payload.data.conditions) {
          if (conditionData.type === ConditionEntityTypes.DEVICE_PROPERTY) {
            conditions.push(buildCreateDevicePropertyCondition(conditionData))
          } else if (conditionData.type === ConditionEntityTypes.CHANNEL_PROPERTY) {
            conditions.push(buildCreateChannelPropertyCondition(conditionData))
          } else if (conditionData.type === ConditionEntityTypes.DATE) {
            conditions.push(buildCreateDateCondition(conditionData))
          } else if (conditionData.type === ConditionEntityTypes.TIME) {
            conditions.push(buildCreateTimeCondition(conditionData))
          }
        }
      }
    }

    for (const notificationData of payload.data.notifications) {
      if (notificationData.type === NotificationEntityTypes.SMS) {
        notifications.push(buildCreateSmsNotification(notificationData))
      } else if (notificationData.type === NotificationEntityTypes.EMAIL) {
        notifications.push(buildCreateEmailNotification(notificationData))
      }
    }

    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Trigger.insert({
        data: Object.assign({}, payload.data, { id, draft }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'triggers-module.triggers.create.failed',
        e,
        'Create new trigger failed.',
      )
    }

    const createdEntity = Trigger.find(id)

    if (createdEntity === null) {
      await Trigger.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('triggers-module.triggers.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Trigger.find(id)
    } else {
      try {
        await Trigger.api().post(
          `${ModuleApiPrefix}/v1/triggers`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        const createdTrigger = Trigger.find(id)

        if (createdTrigger === null) {
          throw new Error('triggers-module.triggers.create.failed')
        }

        const promises: Promise<any>[] = []

        actions
          .forEach((action): void => {
            promises.push(
              Action.add(
                createdTrigger,
                action,
              ),
            )
          })

        conditions
          .forEach((condition): void => {
            promises.push(
              Condition.add(
                createdTrigger,
                condition,
              ),
            )
          })

        notifications
          .forEach((notification): void => {
            promises.push(
              Notification.add(
                createdTrigger,
                notification,
              ),
            )
          })

        await Promise.all(promises)

        return createdTrigger
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Trigger.delete(id)

        throw new ApiError(
          'triggers-module.triggers.create.failed',
          e,
          'Create new trigger failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { trigger: TriggerInterface, data: TriggerUpdateInterface }): Promise<Item<Trigger>> {
    if (state.semaphore.updating.includes(payload.trigger.id)) {
      throw new Error('triggers-module.triggers.update.inProgress')
    }

    if (!Trigger.query().where('id', payload.trigger.id).exists()) {
      throw new Error('triggers-module.triggers.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.trigger.id,
    })

    try {
      await Trigger.update({
        where: payload.trigger.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.trigger.id,
      })

      throw new OrmError(
        'triggers-module.triggers.update.failed',
        e,
        'Edit trigger failed.',
      )
    }

    const updatedEntity = Trigger.find(payload.trigger.id)

    if (updatedEntity === null) {
      // Updated entity could not be loaded from database
      await Trigger.get(
        payload.trigger.id,
      )

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.trigger.id,
      })

      throw new Error('triggers-module.triggers.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.trigger.id,
      })

      return Trigger.find(payload.trigger.id)
    } else {
      try {
        await Trigger.api().patch(
          `${ModuleApiPrefix}/v1/triggers/${updatedEntity.id}`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Trigger.find(payload.trigger.id)
      } catch (e: any) {
        // Updating entity on api failed, we need to refresh entity
        await Trigger.get(
          payload.trigger.id,
        )

        throw new ApiError(
          'triggers-module.triggers.update.failed',
          e,
          'Edit trigger failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.trigger.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { trigger: TriggerInterface }): Promise<Item<Trigger>> {
    if (state.semaphore.updating.includes(payload.trigger.id)) {
      throw new Error('triggers-module.triggers.save.inProgress')
    }

    if (!Trigger.query().where('id', payload.trigger.id).where('draft', true).exists()) {
      throw new Error('triggers-module.triggers.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.trigger.id,
    })

    const entityToSave = Trigger.find(payload.trigger.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.trigger.id,
      })

      throw new Error('triggers-module.triggers.save.failed')
    }

    try {
      await Trigger.api().post(
        `${ModuleApiPrefix}/v1/triggers`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      const createdTrigger = Trigger
        .query()
        .with('actions')
        .with('conditions')
        .with('notifications')
        .where('id', payload.trigger.id)
        .first()

      if (createdTrigger !== null) {
        const promises: Promise<any>[] = []

        createdTrigger.actions
          .forEach((action): void => {
            promises.push(
              Action.save(
                action,
              ),
            )
          })

        createdTrigger.conditions
          .forEach((condition): void => {
            promises.push(
              Condition.save(
                condition,
              ),
            )
          })

        createdTrigger.notifications
          .forEach((notification): void => {
            promises.push(
              Notification.save(
                notification,
              ),
            )
          })

        await Promise.all(promises)
      }

      return createdTrigger
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.triggers.save.failed',
        e,
        'Save draft trigger failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.trigger.id,
      })
    }
  },

  async remove({ state, commit }, payload: { trigger: TriggerInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.trigger.id)) {
      throw new Error('triggers-module.triggers.delete.inProgress')
    }

    const trigger = Trigger
      .query()
      .with('actions')
      .with('conditions')
      .with('notifications')
      .where('id', payload.trigger.id)
      .first()

    if (trigger === null) {
      return true
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.trigger.id,
    })

    try {
      await Trigger.delete(payload.trigger.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.trigger.id,
      })

      throw new OrmError(
        'triggers-module.triggers.delete.failed',
        e,
        'Delete trigger failed.',
      )
    }

    if (payload.trigger.draft) {
      trigger.actions
        .forEach((action): void => {
          Action.remove(
            action,
          )
        })

      trigger.conditions
        .forEach((condition): void => {
          Condition.remove(
            condition,
          )
        })

      trigger.notifications
        .forEach((notification): void => {
          Notification.remove(
            notification,
          )
        })

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.trigger.id,
      })

      return true
    } else {
      try {
        await Trigger.api().delete(
          `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}`,
          {
            save: false,
          },
        )

        trigger.actions
          .forEach((action: ActionInterface): void => {
            Action.delete(action.id)
          })

        trigger.conditions
          .forEach((condition: ConditionInterface): void => {
            Condition.delete(condition.id)
          })

        trigger.notifications
          .forEach((notification: NotificationInterface): void => {
            Notification.delete(notification.id)
          })

        return true
      } catch (e: any) {
        // Deleting entity on api failed, we need to refresh entity
        await Trigger.get(
          payload.trigger.id,
        )

        throw new OrmError(
          'triggers-module.triggers.delete.failed',
          e,
          'Delete trigger failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.trigger.id,
        })
      }
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.TRIGGERS_ENTITY_REPORTED,
        RoutingKeys.TRIGGERS_ENTITY_CREATED,
        RoutingKeys.TRIGGERS_ENTITY_UPDATED,
        RoutingKeys.TRIGGERS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Trigger.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Trigger.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'triggers-module.triggers.delete.failed',
            e,
            'Delete trigger failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {}

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'type') {
              switch (body[attrName]) {
                case TriggerType.MANUAL:
                  entityData[camelName] = TriggerEntityTypes.MANUAL
                  break

                case TriggerType.AUTOMATIC:
                  entityData[camelName] = TriggerEntityTypes.AUTOMATIC
                  break

                default:
                  entityData[camelName] = body[attrName]
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Trigger.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          // Updating entity on api failed, we need to refresh entity
          await Trigger.get(
            body.id,
          )

          throw new OrmError(
            'triggers-module.triggers.update.failed',
            e,
            'Edit trigger failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.TRIGGERS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

    TriggerControl.reset()
    Action.reset()
    Condition.reset()
    Notification.reset()
  },
}

const moduleMutations: MutationTree<TriggerState> = {
  ['SET_FIRST_LOAD'](state: TriggerState, action: boolean): void {
    state.firstLoad = action
  },

  ['SET_SEMAPHORE'](state: TriggerState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: TriggerState, action: SemaphoreAction): void {
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

  ['RESET_STATE'](state: TriggerState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): TriggerState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
