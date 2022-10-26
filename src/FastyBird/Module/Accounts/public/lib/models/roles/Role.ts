import {
  Fields,
  Model,
} from '@vuex-orm/core'

import {
  RoleInterface,
  RoleEntityTypes,
} from '@/lib/models/roles/types'
import Account from '@/lib/models/accounts/Account'
import RoleAccount from '@/lib/models/roles-accounts/RoleAccount'

export default class Role extends Model implements RoleInterface {
  id!: string
  type!: RoleEntityTypes
  name!: string
  description!: string
  anonymous!: boolean
  authenticated!: boolean
  administrator!: boolean
  // Relations
  relationshipNames!: string[]

  static get entity(): string {
    return 'accounts_module_role'
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      name: this.string(''),
      description: this.string(''),
      anonymous: this.boolean(false),
      authenticated: this.boolean(false),
      administrator: this.boolean(false),

      // Relations
      relationshipNames: this.attr([]),

      account: this.belongsToMany(Account, RoleAccount, 'roleId', 'accountId'),
    }
  }

  static async get(id: string): Promise<boolean> {
    return await Role.dispatch('get', {
      id,
    })
  }

  static async fetch(): Promise<boolean> {
    return await Role.dispatch('fetch')
  }

  static reset(): Promise<void> {
    return Role.dispatch('reset')
  }
}
