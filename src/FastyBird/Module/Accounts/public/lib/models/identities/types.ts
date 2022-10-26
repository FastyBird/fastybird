import { IdentityState } from '@fastybird/metadata'

import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
  TJsonApiRelationshipData,
} from 'jsona/lib/JsonaTypes'

import {
  AccountDataResponseInterface,
  AccountEntityTypes,
  AccountInterface,
} from '@/lib/models/accounts/types'

// ENTITY TYPES
// ============

export enum IdentityEntityTypes {
  USER = 'com.fastybird.accounts-module/identity',
}

// ENTITY INTERFACE
// ================

export interface IdentityInterface {
  id: string
  type: IdentityEntityTypes

  draft: boolean

  state: IdentityState
  uid: string
  password: string

  relationshipNames: string[]

  account: AccountInterface | null

  accountId: string
}

// API RESPONSES
// =============

interface IdentityAttributesResponseInterface {
  state: IdentityState
  uid?: string

  // Machine user identity specific
  password?: string
}

interface IdentityAccountRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: AccountEntityTypes
}

interface IdentityAccountRelationshipsResponseInterface extends TJsonApiRelation {
  data: IdentityAccountRelationshipResponseInterface
}

interface IdentityRelationshipsResponseInterface extends TJsonApiRelationships {
  account: IdentityAccountRelationshipsResponseInterface
}

export interface IdentityDataResponseInterface extends TJsonApiData {
  id: string,
  type: IdentityEntityTypes,
  attributes: IdentityAttributesResponseInterface,
  relationships: IdentityRelationshipsResponseInterface,
}

export interface IdentityResponseInterface extends TJsonApiBody {
  data: IdentityDataResponseInterface,
  includes?: (AccountDataResponseInterface)[],
}

export interface IdentitiesResponseInterface extends TJsonApiBody {
  data: IdentityDataResponseInterface[],
  includes?: (AccountDataResponseInterface)[],
}

// CREATE ENTITY INTERFACES
// ========================

export interface IdentityCreateInterface {
  type: IdentityEntityTypes

  uid: string,
  password: string
}

// UPDATE ENTITY INTERFACES
// ========================

export interface IdentityUpdateInterface {
  password: {
    current: string
    new: string
  }
}
