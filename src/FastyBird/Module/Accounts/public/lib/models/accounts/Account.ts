import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'
import { AccountState } from '@fastybird/metadata'

import {
  AccountInterface,
  AccountEntityTypes,
  AccountCreateInterface,
  AccountUpdateInterface,
  AccountRegisterInterface,
} from '@/lib/models/accounts/types'
import Email from '@/lib/models/emails/Email'
import { EmailInterface } from '@/lib/models/emails/types'
import Identity from '@/lib/models/identities/Identity'
import { IdentityInterface } from '@/lib/models/identities/types'
import Role from '@/lib/models/roles/Role'
import RoleAccount from '@/lib/models/roles-accounts/RoleAccount'

export default class Account extends Model implements AccountInterface {
  id!: string
  type!: AccountEntityTypes
  draft!: boolean
  state!: AccountState
  lastVisit!: string
  registered!: string
  firstName!: string
  lastName!: string
  middleName!: string | null
  language!: string
  weekStart!: number
  timezone!: string
  dateFormat!: string
  timeFormat!: string
  // Relations
  relationshipNames!: string[]
  emails!: EmailInterface[]
  identities!: IdentityInterface[]

  static get entity(): string {
    return 'accounts_module_account'
  }

  // Entity transformers
  get name(): string {
    return `${this.firstName} ${this.lastName}`
  }

  get email(): EmailInterface | null {
    return Email
      .query()
      .where('accountId', this.id)
      .where('default', true)
      .first()
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      draft: this.boolean(false),

      state: this.string('active'),

      lastVisit: this.string(null).nullable(),
      registered: this.string(null).nullable(),

      firstName: this.string(''),
      lastName: this.string(''),
      middleName: this.string(null).nullable(),

      language: this.string('en'),

      weekStart: this.number(1),
      timezone: this.string(''),
      dateFormat: this.string('dd.MM.yyyy'),
      timeFormat: this.string('HH:mm'),

      // Relations
      relationshipNames: this.attr([]),

      emails: this.hasMany(Email, 'accountId'),
      identities: this.hasMany(Identity, 'accountId'),
      roles: this.belongsToMany(Role, RoleAccount, 'accountId', 'roleId'),
    }
  }

  static async get(id: string): Promise<boolean> {
    return await Account.dispatch('get', {
      id,
    })
  }

  static async fetch(): Promise<boolean> {
    return await Account.dispatch('fetch')
  }

  static async add(data: AccountCreateInterface, id?: string | null, draft = true): Promise<Item<Account>> {
    return await Account.dispatch('add', {
      id,
      draft,
      data,
    })
  }

  static async edit(account: AccountInterface, data: AccountUpdateInterface): Promise<Item<Account>> {
    return await Account.dispatch('edit', {
      account,
      data,
    })
  }

  static async save(account: AccountInterface): Promise<Item<Account>> {
    return await Account.dispatch('save', {
      account,
    })
  }

  static async remove(account: AccountInterface): Promise<boolean> {
    return await Account.dispatch('remove', {
      account,
    })
  }

  static async register(data: AccountRegisterInterface): Promise<boolean> {
    return await Account.dispatch('register', {
      data,
    })
  }

  static reset(): Promise<void> {
    return Account.dispatch('reset')
  }
}
