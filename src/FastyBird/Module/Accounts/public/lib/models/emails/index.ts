import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema from '@fastybird/metadata/resources/schemas/modules/accounts-module/entity.email.json'
import { EmailEntity as ExchangeEntity, AccountsModuleRoutes as RoutingKeys } from '@fastybird/metadata'

import {
  ActionTree,
  GetterTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { v4 as uuid } from 'uuid'
import { AxiosResponse } from 'axios'
import uniq from 'lodash/uniq'

import Account from '@/lib/models/accounts/Account'
import { AccountInterface } from '@/lib/models/accounts/types'
import Email from '@/lib/models/emails/Email'
import {
  EmailCreateInterface,
  EmailEntityTypes,
  EmailInterface,
  EmailResponseInterface,
  EmailsResponseInterface,
  EmailUpdateInterface,
} from '@/lib/models/emails/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  EmailJsonModelInterface,
  ModuleApiPrefix,
  SemaphoreTypes,
} from '@/lib/types'

interface SemaphoreFetchingState {
  item: string[]
  items: string[]
}

interface SemaphoreState {
  fetching: SemaphoreFetchingState
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface EmailState {
  semaphore: SemaphoreState
  firstLoad: string[]
}

interface FirstLoadAction {
  id: string
}

interface SemaphoreAction {
  type: SemaphoreTypes
  id: string
}

const jsonApiFormatter = new Jsona({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const jsonSchemaValidator = new Ajv()

const apiOptions = {
  dataTransformer: (result: AxiosResponse<EmailResponseInterface> | AxiosResponse<EmailsResponseInterface>): EmailJsonModelInterface | EmailJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as EmailJsonModelInterface | EmailJsonModelInterface[],
}

const moduleState: EmailState = {

  semaphore: {
    fetching: {
      item: [],
      items: [],
    },
    creating: [],
    updating: [],
    deleting: [],
  },

  firstLoad: [],

}

const moduleGetters: GetterTree<EmailState, any> = {
  firstLoadFinished: state => (accountId: string): boolean => {
    return state.firstLoad.includes(accountId)
  },

  getting: state => (emailId: string): boolean => {
    return state.semaphore.fetching.item.includes(emailId)
  },

  fetching: state => (accountId: string | null): boolean => {
    return accountId !== null ? state.semaphore.fetching.items.includes(accountId) : state.semaphore.fetching.items.length > 0
  },
}

const moduleActions: ActionTree<EmailState, any> = {
  async get({ state, commit }, payload: { account: AccountInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Email.api().get(
        `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/emails/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.emails.get.failed',
        e,
        'Fetching email failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ state, commit }, payload: { account: AccountInterface }): Promise<boolean> {
    if (state.semaphore.fetching.items.includes(payload.account.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
      id: payload.account.id,
    })

    try {
      await Email.api().get(
        `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/emails`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD', {
        id: payload.account.id,
      })

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.emails.fetch.failed',
        e,
        'Fetching emails failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.account.id,
      })
    }
  },

  async add({ commit }, payload: { account: AccountInterface, id?: string | null, draft?: boolean, data: EmailCreateInterface }): Promise<Item<Email>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Email.insert({
        data: Object.assign({}, payload.data, {
          id,
          draft,
          accountId: payload.account.id,
          type: EmailEntityTypes.EMAIL,
        }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'accounts-module.emails.create.failed',
        e,
        'Create new email failed.',
      )
    }

    const createdEntity = Email.find(id)

    if (createdEntity === null) {
      await Email.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('accounts-module.emails.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Email.find(id)
    } else {
      try {
        await Email.api().post(
          `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/emails`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Email.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Email.delete(id)

        throw new ApiError(
          'accounts-module.emails.create.failed',
          e,
          'Create new email failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { email: EmailInterface, data: EmailUpdateInterface }): Promise<Item<Email>> {
    if (state.semaphore.updating.includes(payload.email.id)) {
      throw new Error('accounts-module.emails.update.inProgress')
    }

    if (!Email.query().where('id', payload.email.id).exists()) {
      throw new Error('accounts-module.emails.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.email.id,
    })

    try {
      await Email.update({
        where: payload.email.id,
        data: payload,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.email.id,
      })

      throw new OrmError(
        'accounts-module.emails.update.failed',
        e,
        'Edit email failed.',
      )
    }

    const updatedEntity = Email.find(payload.email.id)

    if (updatedEntity === null) {
      const account = Account.find(payload.email.accountId)

      // Updated entity could not be loaded from database
      await Email.dispatch('get', {
        account,
        id: payload.email.id,
      })

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.email.id,
      })

      throw new Error('accounts-module.emails.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.email.id,
      })

      return Email.find(payload.email.id)
    } else {
      try {
        await Email.api().patch(
          `${ModuleApiPrefix}/v1/accounts/${updatedEntity.accountId}/emails/${updatedEntity.id}`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Email.find(payload.email.id)
      } catch (e: any) {
        const account = Account.find(payload.email.accountId)

        // Updating entity on api failed, we need to refresh entity
        await Email.dispatch('get', {
          account,
          id: payload.email.id,
        })

        throw new ApiError(
          'accounts-module.emails.update.failed',
          e,
          'Edit email failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.email.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { email: EmailInterface }): Promise<Item<Email>> {
    if (state.semaphore.updating.includes(payload.email.id)) {
      throw new Error('accounts-module.emails.save.inProgress')
    }

    if (!Email.query().where('id', payload.email.id).where('draft', true).exists()) {
      throw new Error('accounts-module.emails.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.email.id,
    })

    const entityToSave = Email.find(payload.email.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.email.id,
      })

      throw new Error('accounts-module.emails.save.failed')
    }

    try {
      await Email.api().post(
        `${ModuleApiPrefix}/v1/accounts/${entityToSave.accountId}/emails`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Email.find(payload.email.id)
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.emails.save.failed',
        e,
        'Save draft email failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.email.id,
      })
    }
  },

  async remove({ state, commit }, payload: { email: EmailInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.email.id)) {
      throw new Error('accounts-module.emails.delete.inProgress')
    }

    if (!Email.query().where('id', payload.email.id).exists()) {
      throw new Error('accounts-module.emails.delete.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.email.id,
    })

    try {
      await Email.delete(payload.email.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.email.id,
      })

      throw new OrmError(
        'accounts-module.emails.delete.failed',
        e,
        'Delete email failed.',
      )
    }

    if (payload.email.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.email.id,
      })

      return true
    } else {
      try {
        await Email.api().delete(
          `${ModuleApiPrefix}/v1/accounts/${payload.email.accountId}/emails/${payload.email.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        const account = await Account.find(payload.email.accountId)

        // Replacing backup failed, we need to refresh whole list
        await Email.dispatch('get', {
          account,
          id: payload.email.id,
        })

        throw new ApiError(
          'accounts-module.emails.delete.failed',
          e,
          'Delete email failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.email.id,
        })
      }
    }
  },

  async validate(_store, payload: { address: string }): Promise<any> {
    try {
      await Email.api().post(
        `${ModuleApiPrefix}/v1/validate-email`,
        jsonApiFormatter.serialize({
          stuff: Object.assign({}, {
            type: EmailEntityTypes.EMAIL,

            address: payload.address,
          }),
        }),
        {
          ...apiOptions,
          save: false,
        },
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.emails.validate.failed',
        e,
        'Validate email address failed.',
      )
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.EMAILS_ENTITY_REPORTED,
        RoutingKeys.EMAILS_ENTITY_CREATED,
        RoutingKeys.EMAILS_ENTITY_UPDATED,
        RoutingKeys.EMAILS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (isValid(body)) {
      if (
        !Email.query().where('id', body.id).exists() &&
        (payload.routingKey === RoutingKeys.EMAILS_ENTITY_UPDATED || payload.routingKey === RoutingKeys.EMAILS_ENTITY_DELETED)
      ) {
        throw new Error('accounts-module.emails.update.failed')
      }

      if (payload.routingKey === RoutingKeys.EMAILS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Email.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'accounts-module.emails.delete.failed',
            e,
            'Delete email failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.EMAILS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.EMAILS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.EMAILS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {
          type: EmailEntityTypes.EMAIL,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            if (camelName === 'account') {
              const account = Account.query().where('id', body[attrName]).first()

              if (account !== null) {
                entityData.accountId = account.id
              }
            } else {
              entityData[camelName] = body[attrName]
            }
          })

        try {
          await Email.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = Email.query().with('account').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.account !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Email.dispatch('get', {
              account: failedEntity.account,
              id: body.id,
            })
          }

          throw new OrmError(
            'accounts-module.emails.update.failed',
            e,
            'Edit email failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.EMAILS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<EmailState> = {
  ['SET_FIRST_LOAD'](state: EmailState, action: FirstLoadAction): void {
    state.firstLoad.push(action.id)

    // Make all keys uniq
    state.firstLoad = uniq(state.firstLoad)
  },

  ['SET_SEMAPHORE'](state: EmailState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.GETTING:
        state.semaphore.fetching.item.push(action.id)

        // Make all keys uniq
        state.semaphore.fetching.item = uniq(state.semaphore.fetching.item)
        break

      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items.push(action.id)

        // Make all keys uniq
        state.semaphore.fetching.items = uniq(state.semaphore.fetching.items)
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

  ['CLEAR_SEMAPHORE'](state: EmailState, action: SemaphoreAction): void {
    switch (action.type) {
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

  ['RESET_STATE'](state: EmailState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): EmailState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
