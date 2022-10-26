import {
  AccountState,
  IdentityState,
  ModulePrefix,
} from '@fastybird/metadata'

import { TJsonaModel } from 'jsona/lib/JsonaTypes'

import { AccountEntityTypes } from '@/lib/models/accounts/types'
import { EmailEntityTypes } from '@/lib/models/emails/types'
import { IdentityEntityTypes } from '@/lib/models/identities/types'
import { RoleEntityTypes } from '@/lib/models/roles/types'

export interface AccountJsonModelInterface extends TJsonaModel {
  id: string,
  type: AccountEntityTypes,

  state: AccountState,

  lastVisit: string | null,
  registered: string | null,
}

export interface EmailJsonModelInterface extends TJsonaModel {
  id: string,
  type: EmailEntityTypes,
}

export interface IdentityJsonModelInterface extends TJsonaModel {
  id: string,
  type: IdentityEntityTypes,

  state: IdentityState,
}

export interface RoleJsonModelInterface extends TJsonaModel {
  id: string,
  type: RoleEntityTypes,
}

export interface RelationInterface extends TJsonaModel {
  id: string
  type: AccountEntityTypes | EmailEntityTypes | IdentityEntityTypes | RoleEntityTypes
}

export const ModuleApiPrefix = `/${ModulePrefix.MODULE_ACCOUNTS}`

// STORE
// =====

export enum SemaphoreTypes {
  FETCHING = 'fetching',
  GETTING = 'getting',
  CREATING = 'creating',
  UPDATING = 'updating',
  DELETING = 'deleting',
}
