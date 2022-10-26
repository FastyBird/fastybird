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

export enum EmailEntityTypes {
  EMAIL = 'com.fastybird.accounts-module/email',
}

// ENTITY INTERFACE
// ================

export interface EmailInterface {
  readonly id: string
  readonly type: EmailEntityTypes

  draft: boolean

  readonly address: string
  default: boolean
  private: boolean
  verified: boolean

  // Relations
  relationshipNames: string[]

  account: AccountInterface | null

  accountId: string

  // Entity transformers
  isDefault: boolean
  isPrivate: boolean
  isVerified: boolean
}

// API RESPONSES
// =============

interface EmailAttributesResponseInterface {
  address: string
  default: boolean
  private: boolean
  verified: boolean
}

interface EmailAccountRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: AccountEntityTypes
}

interface EmailAccountRelationshipsResponseInterface extends TJsonApiRelation {
  data: EmailAccountRelationshipResponseInterface
}

interface EmailRelationshipsResponseInterface extends TJsonApiRelationships {
  account: EmailAccountRelationshipsResponseInterface
}

export interface EmailDataResponseInterface extends TJsonApiData {
  id: string,
  type: EmailEntityTypes,
  attributes: EmailAttributesResponseInterface,
  relationships: EmailRelationshipsResponseInterface,
}

export interface EmailResponseInterface extends TJsonApiBody {
  data: EmailDataResponseInterface,
  includes?: (AccountDataResponseInterface)[],
}

export interface EmailsResponseInterface extends TJsonApiBody {
  data: EmailDataResponseInterface[],
  includes?: (AccountDataResponseInterface)[],
}

// CREATE ENTITY INTERFACES
// ========================

export interface EmailCreateInterface {
  address: string
  default?: boolean
  private?: boolean
}

// UPDATE ENTITY INTERFACES
// ========================

export interface EmailUpdateInterface {
  default?: boolean
  private?: boolean
}
