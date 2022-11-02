import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/triggers-module/entity.notification.json'
import {
  NotificationEntity as ExchangeEntity,
  NotificationType,
  TriggersModuleRoutes as RoutingKeys,
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
import Notification from '@/lib/models/notifications/Notification'
import {
  CreateEmailNotificationInterface,
  CreateSmsNotificationInterface,
  NotificationEntityTypes,
  NotificationInterface,
  NotificationResponseInterface,
  NotificationsResponseInterface,
  UpdateEmailNotificationInterface,
  UpdateSmsNotificationInterface,
} from '@/lib/models/notifications/types'

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
  NotificationJsonModelInterface,
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

interface NotificationState {
  semaphore: SemaphoreState
}

interface SemaphoreNotification {
  type: SemaphoreTypes
  id: string
}

const jsonApiFormatter = new Jsona({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const apiOptions = {
  dataTransformer: (result: AxiosResponse<NotificationResponseInterface> | AxiosResponse<NotificationsResponseInterface>): NotificationJsonModelInterface | NotificationJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as NotificationJsonModelInterface | NotificationJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: NotificationState = {

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

const moduleActions: ActionTree<NotificationState, any> = {
  async get({ state, commit }, payload: { trigger: TriggerInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Notification.api().get(
        `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/notifications/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.notifications.get.failed',
        e,
        'Fetching notification failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async add({ commit }, payload: { trigger: TriggerInterface, id?: string | null, draft?: boolean, data: CreateSmsNotificationInterface | CreateEmailNotificationInterface }): Promise<Item<Notification>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Notification.insert({
        data: Object.assign({}, payload.data, { id, draft, triggerId: payload.trigger.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'triggers-module.notifications.create.failed',
        e,
        'Create new notification failed.',
      )
    }

    const createdEntity = Notification.find(id)

    if (createdEntity === null) {
      await Notification.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('triggers-module.notifications.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Notification.find(id)
    } else {
      try {
        await Notification.api().post(
          `${ModuleApiPrefix}/v1/triggers/${payload.trigger.id}/notifications`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Notification.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Notification.delete(id)

        throw new ApiError(
          'triggers-module.notifications.create.failed',
          e,
          'Create new notification failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { notification: NotificationInterface, data: UpdateSmsNotificationInterface | UpdateEmailNotificationInterface }): Promise<Item<Notification>> {
    if (state.semaphore.updating.includes(payload.notification.id)) {
      throw new Error('triggers-module.notifications.update.inProgress')
    }

    if (!Notification.query().where('id', payload.notification.id).exists()) {
      throw new Error('triggers-module.notifications.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.notification.id,
    })

    try {
      await Notification.update({
        where: payload.notification.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.notification.id,
      })

      throw new OrmError(
        'triggers-module.notifications.update.failed',
        e,
        'Edit notification failed.',
      )
    }

    const updatedEntity = Notification.find(payload.notification.id)

    if (updatedEntity === null) {
      const trigger = Trigger.find(payload.notification.triggerId)

      if (trigger !== null) {
        // Updated entity could not be loaded from database
        await Notification.get(
          trigger,
          payload.notification.id,
        )
      }

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.notification.id,
      })

      throw new Error('triggers-module.notifications.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.notification.id,
      })

      return Notification.find(payload.notification.id)
    } else {
      try {
        await Notification.api().patch(
          `${ModuleApiPrefix}/v1/triggers/${updatedEntity.triggerId}/notifications/${updatedEntity.id}`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Notification.find(payload.notification.id)
      } catch (e: any) {
        const trigger = Trigger.find(payload.notification.triggerId)

        if (trigger !== null) {
          // Updating entity on api failed, we need to refresh entity
          await Notification.get(
            trigger,
            payload.notification.id,
          )
        }

        throw new ApiError(
          'triggers-module.notifications.update.failed',
          e,
          'Edit notification failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.notification.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { notification: NotificationInterface }): Promise<Item<Notification>> {
    if (state.semaphore.updating.includes(payload.notification.id)) {
      throw new Error('triggers-module.notifications.save.inProgress')
    }

    if (!Notification.query().where('id', payload.notification.id).where('draft', true).exists()) {
      throw new Error('triggers-module.notifications.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.notification.id,
    })

    const entityToSave = Notification.find(payload.notification.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.notification.id,
      })

      throw new Error('triggers-module.notifications.save.failed')
    }

    try {
      await Notification.api().patch(
        `${ModuleApiPrefix}/v1/triggers/${entityToSave.triggerId}/notifications`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Notification.find(payload.notification.id)
    } catch (e: any) {
      throw new ApiError(
        'triggers-module.notifications.save.failed',
        e,
        'Save draft notification failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.notification.id,
      })
    }
  },

  async remove({ state, commit }, payload: { notification: NotificationInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.notification.id)) {
      throw new Error('triggers-module.notifications.delete.inProgress')
    }

    if (!Notification.query().where('id', payload.notification.id).exists()) {
      throw new Error('triggers-module.notifications.delete.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.notification.id,
    })

    try {
      await Notification.delete(payload.notification.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.notification.id,
      })

      throw new OrmError(
        'triggers-module.notifications.delete.failed',
        e,
        'Delete notification failed.',
      )
    }

    if (payload.notification.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.notification.id,
      })

      return true
    } else {
      try {
        await Notification.api().delete(
          `${ModuleApiPrefix}/v1/triggers/${payload.notification.triggerId}/notifications/${payload.notification.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        const trigger = await Trigger.find(payload.notification.triggerId)

        if (trigger !== null) {
          // Replacing backup failed, we need to refresh whole list
          await Notification.get(
            trigger,
            payload.notification.id,
          )
        }

        throw new ApiError(
          'triggers-module.notifications.delete.failed',
          e,
          'Delete notification failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.notification.id,
        })
      }
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.NOTIFICATIONS_ENTITY_REPORTED,
        RoutingKeys.NOTIFICATIONS_ENTITY_CREATED,
        RoutingKeys.NOTIFICATIONS_ENTITY_UPDATED,
        RoutingKeys.NOTIFICATIONS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const validate = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (validate(body)) {
      if (
        !Notification.query().where('id', body.id).exists() &&
        payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_DELETED
      ) {
        return true
      }

      if (payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Notification.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'triggers-module.notifications.delete.failed',
            e,
            'Delete notification failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
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
                case NotificationType.SMS:
                  entityData[camelName] = NotificationEntityTypes.SMS
                  break

                case NotificationType.EMAIL:
                  entityData[camelName] = NotificationEntityTypes.EMAIL
                  break

                default:
                  entityData[camelName] = body[attrName]
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Notification.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const trigger = Trigger.query().where('id', body.trigger).first()

          if (trigger !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Notification.get(
              trigger,
              body.id,
            )
          }

          throw new OrmError(
            'triggers-module.notifications.update.failed',
            e,
            'Edit notification failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.NOTIFICATIONS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<NotificationState> = {
  ['SET_SEMAPHORE'](state: NotificationState, action: SemaphoreNotification): void {
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

  ['CLEAR_SEMAPHORE'](state: NotificationState, action: SemaphoreNotification): void {
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

  ['RESET_STATE'](state: NotificationState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): NotificationState => (moduleState),
  actions: moduleActions,
  mutations: moduleMutations,
}
