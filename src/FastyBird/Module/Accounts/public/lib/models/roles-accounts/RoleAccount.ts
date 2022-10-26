import { Fields, Model } from '@vuex-orm/core'
import { RoleAccountInterface } from '@/lib/models/roles-accounts/types'

export default class RoleAccount extends Model implements RoleAccountInterface {
  roleId!: string
  accountId!: string

  static get entity(): string {
    return 'accounts_module_role_user'
  }

  static get primaryKey(): string[] {
    return ['roleId', 'accountId']
  }

  static fields(): Fields {
    return {
      roleId: this.string(null),
      accountId: this.string(null),
    }
  }
}
