import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'
import { IdentityState } from '@fastybird/metadata'

import Account from '@/lib/models/accounts/Account'
import { AccountInterface } from '@/lib/models/accounts/types'
import {
  IdentityInterface,
  IdentityEntityTypes,
  IdentityCreateInterface,
  IdentityUpdateInterface,
} from '@/lib/models/identities/types'

export default class Identity extends Model implements IdentityInterface {
  id!: string
  type!: IdentityEntityTypes
  draft!: boolean
  state!: IdentityState
  uid!: string
  password!: string
  // Relations
  relationshipNames!: string[]
  account!: AccountInterface | null
  accountId!: string

  static get entity(): string {
    return 'accounts_module_identity'
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      draft: this.boolean(false),

      state: this.string(IdentityState.ACTIVE),
      uid: this.string(''),
      password: this.string(null).nullable(),

      // Relations
      relationshipNames: this.attr([]),

      account: this.belongsTo(Account, 'id'),

      accountId: this.attr(''),
    }
  }

  static async get(account: AccountInterface, id: string): Promise<boolean> {
    return await Identity.dispatch('get', {
      account,
      id,
    })
  }

  static async fetch(account: AccountInterface): Promise<boolean> {
    return await Identity.dispatch('fetch', {
      account,
    })
  }

  static async add(account: AccountInterface, data: IdentityCreateInterface, id?: string | null, draft = true): Promise<Item<Identity>> {
    return await Identity.dispatch('add', {
      account,
      id,
      draft,
      data,
    })
  }

  static async edit(identity: IdentityInterface, data: IdentityUpdateInterface): Promise<Item<Identity>> {
    return await Identity.dispatch('edit', {
      identity,
      data,
    })
  }

  static async save(identity: IdentityInterface): Promise<Item<Identity>> {
    return await Identity.dispatch('save', {
      identity,
    })
  }

  static async remove(identity: IdentityInterface): Promise<boolean> {
    return await Identity.dispatch('remove', {
      identity,
    })
  }

  static async requestReset(uid: string): Promise<boolean> {
    return await Identity.dispatch('requestReset', {
      uid,
    })
  }

  static reset(): Promise<void> {
    return Identity.dispatch('reset')
  }
}
