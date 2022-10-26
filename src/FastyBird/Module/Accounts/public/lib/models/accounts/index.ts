import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/accounts-module/entity.account.json'
import {
  AccountEntity as ExchangeEntity,
  AccountsModuleRoutes as RoutingKeys,
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

import Account from '@/lib/models/accounts/Account'
import Email from '@/lib/models/emails/Email'
import Identity from '@/lib/models/identities/Identity'
import {
  AccountCreateInterface,
  AccountEntityTypes,
  AccountInterface,
  AccountRegisterInterface,
  AccountResponseInterface,
  AccountsResponseInterface,
  AccountUpdateInterface,
} from '@/lib/models/accounts/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  AccountJsonModelInterface,
  ModuleApiPrefix,
  SemaphoreTypes,
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

interface AccountState {
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
  dataTransformer: (result: AxiosResponse<AccountResponseInterface> | AxiosResponse<AccountsResponseInterface>): AccountJsonModelInterface | AccountJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as AccountJsonModelInterface | AccountJsonModelInterface[],
}

const jsonSchemaValidator = new Ajv()

const moduleState: AccountState = {

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

const moduleGetters: GetterTree<AccountState, any> = {
  firstLoadFinished: state => (): boolean => {
    return !!state.firstLoad
  },

  getting: state => (id: string): boolean => {
    return state.semaphore.fetching.item.includes(id)
  },

  fetching: state => (): boolean => {
    return !!state.semaphore.fetching.items
  },
}

const moduleActions: ActionTree<AccountState, any> = {
  async get({ state, commit }, payload: { id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Account.api().get(
        `${ModuleApiPrefix}/v1/accounts/${payload.id}?include=emails,identities,roles`,
        apiOptions,
      )
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.accounts.get.failed',
        e,
        'Fetching account failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }

    return true
  },

  async fetch({ state, commit }): Promise<boolean> {
    if (state.semaphore.fetching.items) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
    })

    try {
      await Account.api().get(
        `${ModuleApiPrefix}/v1/accounts?include=emails,identities,roles`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD', true)

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.accounts.fetch.failed',
        e,
        'Fetching accounts failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
      })
    }
  },

  async add({ commit }, payload: { id?: string | null, draft?: boolean, data: AccountCreateInterface }): Promise<Item<Account>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Account.insert({
        data: Object.assign({}, payload.data, { id, draft }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'accounts-module.accounts.create.failed',
        e,
        'Create new account failed.',
      )
    }

    const createdEntity = Account.find(id)

    if (createdEntity === null) {
      await Account.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('accounts-module.accounts.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Account.find(id)
    } else {
      try {
        await Account.api().post(
          `${ModuleApiPrefix}/v1/accounts?include=emails,identities,roles`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Account.find(id)
      } catch (e: any) {
        // Entity could not be created on api, we have to remove it from database
        await Account.delete(id)

        throw new ApiError(
          'accounts-module.accounts.create.failed',
          e,
          'Create new account failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({ state, commit }, payload: { account: AccountInterface, data: AccountUpdateInterface }): Promise<Item<Account>> {
    if (state.semaphore.updating.includes(payload.account.id)) {
      throw new Error('accounts-module.accounts.update.inProgress')
    }

    if (!Account.query().where('id', payload.account.id).exists()) {
      throw new Error('accounts-module.accounts.update.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.account.id,
    })

    try {
      await Account.update({
        where: payload.account.id,
        data: payload.data,
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.account.id,
      })

      throw new OrmError(
        'accounts-module.accounts.update.failed',
        e,
        'Edit account failed.',
      )
    }

    const updatedEntity = Account.find(payload.account.id)

    if (updatedEntity === null) {
      // Updated entity could not be loaded from database
      await Account.dispatch('get', {
        id: payload.account.id,
      })

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.account.id,
      })

      throw new Error('accounts-module.accounts.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.account.id,
      })

      return Account.find(payload.account.id)
    } else {
      try {
        await Account.api().patch(
          `${ModuleApiPrefix}/v1/accounts/${updatedEntity.id}?include=emails,identities,roles`,
          jsonApiFormatter.serialize({
            stuff: updatedEntity,
          }),
          apiOptions,
        )

        return Account.find(payload.account.id)
      } catch (e: any) {
        // Updating entity on api failed, we need to refresh entity
        await Account.dispatch('get', {
          id: payload.account.id,
        })

        throw new ApiError(
          'accounts-module.accounts.update.failed',
          e,
          'Edit account failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.account.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { account: AccountInterface }): Promise<Item<Account>> {
    if (state.semaphore.updating.includes(payload.account.id)) {
      throw new Error('accounts-module.accounts.save.inProgress')
    }

    if (!Account.query().where('id', payload.account.id).where('draft', true).exists()) {
      throw new Error('accounts-module.accounts.save.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.account.id,
    })

    const entityToSave = Account.find(payload.account.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.account.id,
      })

      throw new Error('accounts-module.accounts.save.failed')
    }

    try {
      await Account.api().post(
        `${ModuleApiPrefix}/v1/accounts?include=emails,identities,roles`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Account.find(payload.account.id)
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.accounts.save.failed',
        e,
        'Save draft account failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.account.id,
      })
    }
  },

  async remove({ state, commit }, payload: { account: AccountInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.account.id)) {
      throw new Error('accounts-module.accounts.delete.inProgress')
    }

    if (!Account.query().where('id', payload.account.id).exists()) {
      return true
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.account.id,
    })

    try {
      await Account.delete(payload.account.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.account.id,
      })

      throw new OrmError(
        'accounts-module.accounts.delete.failed',
        e,
        'Delete account failed.',
      )
    }

    if (payload.account.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.account.id,
      })

      return true
    } else {
      try {
        await Account.api().delete(
          `${ModuleApiPrefix}/v1/accounts${payload.account.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        // Deleting entity on api failed, we need to refresh entity
        await Account.dispatch('get', {
          id: payload.account.id,
          includeChannels: false,
        })

        throw new OrmError(
          'accounts-module.accounts.delete.failed',
          e,
          'Delete account failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.account.id,
        })
      }
    }
  },

  async register(_store, payload: AccountRegisterInterface): Promise<any> {
    // TODO: Implement
    try {
      await Account.api().post(
        `${ModuleApiPrefix}/v1/register`,
        jsonApiFormatter.serialize({
          stuff: Object.assign({}, {
            type: AccountEntityTypes.USER,

            email: payload.emailAddress,
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
        'accounts-module.accounts.register.failed',
        e,
        'Register account failed.',
      )
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.ACCOUNTS_ENTITY_REPORTED,
        RoutingKeys.ACCOUNTS_ENTITY_CREATED,
        RoutingKeys.ACCOUNTS_ENTITY_UPDATED,
        RoutingKeys.ACCOUNTS_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (isValid(body)) {
      if (
        !Account.query().where('id', body.id).exists() &&
        (payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_UPDATED || payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_DELETED)
      ) {
        throw new Error('accounts-module.accounts.update.failed')
      }

      if (payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          const account = Account.query().withAll().where('id', body.id).first()

          if (account) {
            account.emails
              .forEach((email) => {
                Email.delete(email.id)
              })

            account.identities
              .forEach((identity) => {
                Identity.delete(identity.id)
              })

            await Account.delete(body.id)
          }
        } catch (e: any) {
          throw new OrmError(
            'accounts-module.accounts.delete.failed',
            e,
            'Delete account failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {
          type: AccountEntityTypes.USER,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            entityData[camelName] = body[attrName]
          })

        try {
          await Account.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          // Updating entity on api failed, we need to refresh entity
          await Account.dispatch('get', {
            id: body.id,
          })

          throw new OrmError(
            'accounts-module.accounts.update.failed',
            e,
            'Edit account failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.ACCOUNTS_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

    Email.reset()
    Identity.reset()
  },
}

const moduleMutations: MutationTree<AccountState> = {
  ['SET_FIRST_LOAD'](state: AccountState, action: boolean): void {
    state.firstLoad = action
  },

  ['SET_SEMAPHORE'](state: AccountState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: AccountState, action: SemaphoreAction): void {
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
            // Find created item in creating semaphore...
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
            // Find created item in creating semaphore...
            if (item === get(action, 'id', 'notValid')) {
              // ...and remove it
              state.semaphore.deleting.splice(index, 1)
            }
          })
        break
    }
  },

  ['RESET_STATE'](state: AccountState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): AccountState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
