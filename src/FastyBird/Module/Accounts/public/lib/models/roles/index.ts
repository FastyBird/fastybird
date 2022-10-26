import * as exchangeEntitySchema from '@fastybird/metadata/resources/schemas/modules/accounts-module/entity.role.json'
import { RoleEntity as ExchangeEntity, AccountsModuleRoutes as RoutingKeys } from '@fastybird/metadata'

import {
  ActionTree,
  GetterTree,
  MutationTree,
} from 'vuex'
import Jsona from 'jsona'
import Ajv from 'ajv'
import { AxiosResponse } from 'axios'
import uniq from 'lodash/uniq'

import Role from '@/lib/models/roles/Role'
import {
  RoleEntityTypes,
  RoleResponseInterface,
  RolesResponseInterface,
} from '@/lib/models/roles/types'

import {
  ApiError,
  OrmError,
} from '@/lib/errors'
import {
  JsonApiModelPropertiesMapper,
  JsonApiJsonPropertiesMapper,
} from '@/lib/jsonapi'
import {
  RoleJsonModelInterface,
  ModuleApiPrefix,
  SemaphoreTypes,
} from '@/lib/types'

interface SemaphoreFetchingState {
  item: string[]
  items: boolean
}

interface SemaphoreState {
  fetching: SemaphoreFetchingState
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface RoleState {
  semaphore: SemaphoreState
  firstLoad: boolean
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
  dataTransformer: (result: AxiosResponse<RoleResponseInterface> | AxiosResponse<RolesResponseInterface>): RoleJsonModelInterface | RoleJsonModelInterface[] => jsonApiFormatter.deserialize(result.data) as RoleJsonModelInterface | RoleJsonModelInterface[],
}

const moduleState: RoleState = {

  semaphore: {
    fetching: {
      item: [],
      items: false,
    },
    creating: [],
    updating: [],
    deleting: [],
  },

  firstLoad: false,

}

const moduleGetters: GetterTree<RoleState, any> = {
  firstLoadFinished: state => (): boolean => {
    return state.firstLoad
  },

  getting: state => (roleId: string): boolean => {
    return state.semaphore.fetching.item.includes(roleId)
  },

  fetching: state => (): boolean => {
    return state.semaphore.fetching.items
  },
}

const moduleActions: ActionTree<RoleState, any> = {
  async get({ state, commit }, payload: { id: string }): Promise<boolean> {
    if (state.semaphore.fetching.item.includes(payload.id)) {
      return false
    }

    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.GETTING,
      id: payload.id,
    })

    try {
      await Role.api().get(
        `${ModuleApiPrefix}/v1/roles/${payload.id}`,
        apiOptions,
      )

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.roles.get.failed',
        e,
        'Fetching role failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.GETTING,
        id: payload.id,
      })
    }
  },

  async fetch({ commit }): Promise<boolean> {
    commit('SET_SEMAPHORE', {
      type: SemaphoreTypes.FETCHING,
    })

    try {
      await Role.api().get(
        `${ModuleApiPrefix}/v1/roles`,
        apiOptions,
      )

      commit('SET_FIRST_LOAD')

      return true
    } catch (e: any) {
      throw new ApiError(
        'accounts-module.roles.fetch.failed',
        e,
        'Fetching roles failed.',
      )
    } finally {
      commit('CLEAR_SEMAPHORE', {
        type: SemaphoreTypes.FETCHING,
      })
    }
  },

  async socketData({ commit }, payload: { source: string, routingKey: string, data: string }): Promise<boolean> {
    if (
      ![
        RoutingKeys.ROLES_ENTITY_REPORTED,
        RoutingKeys.ROLES_ENTITY_CREATED,
        RoutingKeys.ROLES_ENTITY_UPDATED,
        RoutingKeys.ROLES_ENTITY_DELETED,
      ].includes(payload.routingKey as RoutingKeys)
    ) {
      return false
    }

    const body: ExchangeEntity = JSON.parse(payload.data)

    const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

    if (isValid(body)) {
      if (
        !Role.query().where('id', body.id).exists() &&
        (payload.routingKey === RoutingKeys.ROLES_ENTITY_UPDATED || payload.routingKey === RoutingKeys.ROLES_ENTITY_DELETED)
      ) {
        throw new Error('accounts-module.roles.update.failed')
      }

      if (payload.routingKey === RoutingKeys.ROLES_ENTITY_DELETED) {
        commit('SET_SEMAPHORE', {
          type: SemaphoreTypes.DELETING,
          id: body.id,
        })

        try {
          await Role.delete(body.id)
        } catch (e: any) {
          throw new OrmError(
            'accounts-module.roles.delete.failed',
            e,
            'Delete role failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: SemaphoreTypes.DELETING,
            id: body.id,
          })
        }
      } else {
        commit('SET_SEMAPHORE', {
          type: payload.routingKey === RoutingKeys.ROLES_ENTITY_REPORTED ? SemaphoreTypes.GETTING : (payload.routingKey === RoutingKeys.ROLES_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING),
          id: body.id,
        })

        const entityData: { [index: string]: any } = {
          type: RoleEntityTypes.ROLE,
        }

        const camelRegex = new RegExp('_([a-z0-9])', 'g')

        Object.keys(body)
          .forEach((attrName) => {
            const camelName = attrName.replace(camelRegex, g => g[1].toUpperCase())

            entityData[camelName] = body[attrName]
          })

        try {
          await Role.insertOrUpdate({
            data: entityData,
          })
        } catch (e: any) {
          const failedEntity = Role.query().with('account').where('id', body.id).first()

          if (failedEntity !== null) {
            // Updating entity on api failed, we need to refresh entity
            await Role.dispatch('get', {
              id: body.id,
            })
          }

          throw new OrmError(
            'accounts-module.roles.update.failed',
            e,
            'Edit role failed.',
          )
        } finally {
          commit('CLEAR_SEMAPHORE', {
            type: payload.routingKey === RoutingKeys.ROLES_ENTITY_UPDATED ? SemaphoreTypes.UPDATING : SemaphoreTypes.CREATING,
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

const moduleMutations: MutationTree<RoleState> = {
  ['SET_FIRST_LOAD'](state: RoleState): void {
    state.firstLoad = true
  },

  ['SET_SEMAPHORE'](state: RoleState, action: SemaphoreAction): void {
    switch (action.type) {
      case SemaphoreTypes.GETTING:
        state.semaphore.fetching.item.push(action.id)

        // Make all keys uniq
        state.semaphore.fetching.item = uniq(state.semaphore.fetching.item)
        break

      case SemaphoreTypes.FETCHING:
        state.semaphore.fetching.items = true
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

  ['CLEAR_SEMAPHORE'](state: RoleState, action: SemaphoreAction): void {
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
        state.semaphore.fetching.items = false
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

  ['RESET_STATE'](state: RoleState): void {
    Object.assign(state, moduleState)
  },
}

export default {
  state: (): RoleState => (moduleState),
  getters: moduleGetters,
  actions: moduleActions,
  mutations: moduleMutations,
}
