import { Item } from '@vuex-orm/core'
import * as exchangeEntitySchema
  from '@fastybird/metadata/resources/schemas/modules/accounts-module/entity.identity.json'
import {
  IdentityEntity as ExchangeEntity,
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
import uniq from 'lodash/uniq'

import Account from '@/lib/models/accounts/Account'
import { AccountInterface } from '@/lib/models/accounts/types'
import Identity from '@/lib/models/identities/Identity'
import {
  IdentitiesResponseInterface,
  IdentityCreateInterface,
  IdentityEntityTypes,
  IdentityInterface,
  IdentityResponseInterface,
  IdentityUpdateInterface,
} from '@/lib/models/identities/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  IdentityJsonModelInterface,
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

interface IdentityState {
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
  dataTransformer: (result: AxiosResponse<IdentityResponseInterface> | AxiosResponse<IdentitiesResponseInterface>): IdentityJsonModelInterface | IdentityJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as IdentityJsonModelInterface | IdentityJsonModelInterface[],
}

const moduleState: IdentityState = {

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

const moduleGetters: GetterTree<IdentityState, any> = {
  firstLoadFinished: state => (accountId: string): boolean => {
    return state.firstLoad.includes(accountId)
  },

  getting: state => (identityId: string): boolean => {
    return state.semaphore.fetching.item.includes(identityId)
  },

  fetching: state => (accountId: string | null): boolean => {
    return accountId !== null ? state.semaphore.fetching.items.includes(accountId) : state.semaphore.fetching.items.length > 0
  },
}

const moduleActions: ActionTree<IdentityState, any> = {
  async get({ state, commit }, payload: { account: AccountInterface, id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Identity.api().get(
        `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/identities/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.identities.get.failed',
        e,
        'Fetching identity failed.',
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
      await Identity.api().get(
        `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/identities`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD', {
        id: payload.account.id,
      })

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.identities.fetch.failed',
        e,
        'Fetching identities failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
        id: payload.account.id,
      })
    }
  },

  async add({ commit }, payload: { account: AccountInterface, id?: string | null, draft?: boolean, data: IdentityCreateInterface }): Promise<Item<Identity>> {
    const id = typeof payload.id !== 'undefined' && payload.id !== null && payload.id !== '' ? payload.id : uuid().toString()
    const draft = typeof payload.draft !== 'undefined' ? payload.draft : false

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.CREATING,
      id,
    })

    try {
      await Identity.insert({
        data: Object.assign({}, payload.data, { id, draft, accountId: payload.account.id }),
      })
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new OrmError(
        'accounts-module.identities.create.failed',
        e,
        'Create new identity failed.',
      )
    }

    const createdEntity = Identity.find(id)

    if (createdEntity === null) {
      await Identity.delete(id)

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      throw new Error('accounts-module.identities.create.failed')
    }

    if (draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.CREATING,
        id,
      })

      return Identity.find(id)
    } else {
      try {
        await Identity.api().post(
          `${ModuleApiPrefix}/v1/accounts/${payload.account.id}/identities`,
          jsonApiFormatter.serialize({
            stuff: createdEntity,
          }),
          apiOptions,
        )

        return Identity.find(id)
      } catch (e: any) {
        await Identity.delete(id)

        throw new ApiError(
          'accounts-module.identities.create.failed',
          e,
          'Create new identity failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.CREATING,
          id,
        })
      }
    }
  },

  async edit({
               state,
               commit,
             }, payload: { identity: IdentityInterface, data: IdentityUpdateInterface }): Promise<Item<Identity>> {
    if (state.semaphore.updating.includes(payload.identity.id)) {
      throw new Error('accounts-module.identities.update.inProgress')
    }

    if (!Identity.query().where('id', payload.identity.id).exists()) {
      throw new Error('accounts-module.identities.update.inProgress2')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.identity.id,
    })

    const updatedEntity = Identity.find(payload.identity.id)

    if (updatedEntity === null) {
      const account = Account.find(payload.identity.accountId)

      // Updated entity could not be loaded from database
      await Identity.dispatch('get', {
        account,
        id: payload.identity.id,
      })

      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.identity.id,
      })

      throw new Error('accounts-module.identities.update.failed')
    }

    if (updatedEntity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.identity.id,
      })

      return Identity.find(payload.identity.id)
    } else {
      try {
        await Identity.api().patch(
          `${ModuleApiPrefix}/v1/accounts/${payload.identity.accountId}/identities/${payload.identity.id}`,
          jsonApiFormatter.serialize({
            stuff: Object.assign({}, {
              type: payload.identity.type,
              id: payload.identity.id,
              password: payload.data.password.new,
            }),
          }),
          apiOptions,
        )

        return Identity.find(payload.identity.id)
      } catch (e: any) {
        throw new ApiError(
          'accounts-module.identities.update.failed',
          e,
          'Edit identity failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.UPDATING,
          id: payload.identity.id,
        })
      }
    }
  },

  async save({ state, commit }, payload: { identity: IdentityInterface }): Promise<Item<Identity>> {
    if (state.semaphore.updating.includes(payload.identity.id)) {
      throw new Error('accounts-module.identities.save.inProgress')
    }

    if (!Identity.query().where('id', payload.identity.id).where('draft', true).exists()) {
      throw new Error('accounts-module.identities.save.failed 1')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.UPDATING,
      id: payload.identity.id,
    })

    const entityToSave = Identity.find(payload.identity.id)

    if (entityToSave === null) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.identity.id,
      })

      throw new Error('accounts-module.identities.save.failed 2')
    }

    try {
      await Identity.api().post(
        `${ModuleApiPrefix}/v1/accounts/${entityToSave.accountId}/identities`,
        jsonApiFormatter.serialize({
          stuff: entityToSave,
        }),
        apiOptions,
      )

      return Identity.find(payload.identity.id)
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.identities.save.failed',
        e,
        'Save draft identity failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.UPDATING,
        id: payload.identity.id,
      })
    }
  },

  async remove({ state, commit }, payload: { identity: IdentityInterface }): Promise<boolean> {
    if (state.semaphore.deleting.includes(payload.identity.id)) {
      throw new Error('accounts-module.identities.delete.inProgress')
    }

    if (!Identity.query().where('id', payload.identity.id).exists()) {
      throw new Error('accounts-module.identities.delete.failed')
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.DELETING,
      id: payload.identity.id,
    })

    try {
      await Identity.delete(payload.identity.id)
    } catch (e: any) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.identity.id,
      })

      throw new OrmError(
        'accounts-module.identities.delete.failed',
        e,
        'Delete identity failed.',
      )
    }

    if (payload.identity.draft) {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.DELETING,
        id: payload.identity.id,
      })

      return true
    } else {
      try {
        await Identity.api().delete(
          `${ModuleApiPrefix}/v1/accounts/${payload.identity.accountId}/identities/${payload.identity.id}`,
          {
            save: false,
          },
        )

        return true
      } catch (e: any) {
        const account = await Account.find(payload.identity.accountId)

        // Replacing backup failed, we need to refresh whole list
        await Identity.dispatch('get', {
          account,
          id: payload.identity.id,
        })

        throw new ApiError(
          'accounts-module.identities.delete.failed',
          e,
          'Delete identity failed.',
        )
      } finally {
        commit('CLEAR_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: payload.identity.id,
        })
      }
    }
  },

  async requestReset(_store, payload: { uid: string }): Promise<boolean> {
    try {
      await Identity.api().post(
        `${ModuleApiPrefix}/v1/password-reset`,
        jsonApiFormatter.serialize({
          stuff: Object.assign({}, {
            type: IdentityEntityTypes.USER,

            uid: payload.uid,
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
        'accounts-module.identities.requestReset.failed',
        e,
        'Request identity reset failed.',
      )
    }
  },

  async socketData({ state, commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.IDENTITIES_ENTITY_REPORTED,
        RoutingKeys.IDENTITIES_ENTITY_CREATED,
        RoutingKeys.IDENTITIES_ENTITY_UPDATED,
        RoutingKeys.IDENTITIES_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (isValid(body)) {
      if (
        !Identity.query().where('id', body.id).exists() &&
        (payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_UPDATED || payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_DELETED)
      ) {
        throw new Error('accounts-module.identities.update.failed')
      }

      if (payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Identity.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'accounts-module.identities.delete.failed',
            e,
            'Delete identity failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        if (payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_UPDATED && state.semaphore.updating.includes(body.id)) {
          return true
        }

        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {
          type: IdentityEntityTypes.USER,
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
          await Identity.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = Identity.query().with('account').where('id', body.id).first()

          if (failedEntity !== null && failedEntity.account !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Identity.dispatch('get', {
              account: failedEntity.account,
              id: body.id,
            })
          }

          throw new OrmError(
            'accounts-module.identities.update.failed',
            e,
            'Edit identity failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.IDENTITIES_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<IdentityState> = {
  ['SET_FIRST_LOAD'](state: IdentityState, action: FirstLoadAction): void {
    state.firstLoad.push(action.id)

    // Make all keys uniq
    state.firstLoad = uniq(state.firstLoad)
  },

  ['SET_SEMAPHORE'](state: IdentityState, action: SemaphoreAction): void {
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

  ['CLEAR_SEMAPHORE'](state: IdentityState, action: SemaphoreAction): void {
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

  ['RESET_STATE'](state: IdentityState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): IdentityState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
