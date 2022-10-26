import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'

import Account from '@/lib/models/accounts/Account'
import { AccountInterface } from '@/lib/models/accounts/types'
import {
  EmailInterface,
  EmailEntityTypes,
  EmailCreateInterface,
  EmailUpdateInterface,
} from '@/lib/models/emails/types'

export default class Email extends Model implements EmailInterface {
  id!: string
  type!: EmailEntityTypes
  draft!: boolean
  address!: string
  default!: boolean
  private!: boolean
  verified!: boolean
  // Relations
  relationshipNames!: string[]
  account!: AccountInterface | null
  accountId!: string

  static get entity(): string {
    return 'accounts_module_email'
  }

  // Entity transformers
  get isDefault(): boolean {
    return this.default
  }

  get isPrivate(): boolean {
    return this.private
  }

  get isVerified(): boolean {
    return this.verified
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      draft: this.boolean(false),

      address: this.string(''),
      default: this.boolean(false),
      private: this.boolean(false),
      verified: this.boolean(false),

      // Relations
      relationshipNames: this.attr([]),

      account: this.belongsTo(Account, 'id'),

      accountId: this.attr(''),
    }
  }

  static async get(account: AccountInterface, id: string): Promise<boolean> {
    return await Email.dispatch('get', {
      account,
      id,
    })
  }

  static async fetch(account: AccountInterface): Promise<boolean> {
    return await Email.dispatch('fetch', {
      account,
    })
  }

  static async add(account: AccountInterface, data: EmailCreateInterface, id?: string | null, draft = true): Promise<Item<Email>> {
    return await Email.dispatch('add', {
      account,
      id,
      draft,
      data,
    })
  }

  static async edit(email: EmailInterface, data: EmailUpdateInterface): Promise<Item<Email>> {
    return await Email.dispatch('edit', {
      email,
      data,
    })
  }

  static async save(email: EmailInterface): Promise<Item<Email>> {
    return await Email.dispatch('save', {
      email,
    })
  }

  static async remove(email: EmailInterface): Promise<boolean> {
    return await Email.dispatch('remove', {
      email,
    })
  }

  static async validate(address: string): Promise<boolean> {
    return await Email.dispatch('validate', {
      address,
    })
  }

  static reset(): Promise<void> {
    return Email.dispatch('reset')
  }
}
