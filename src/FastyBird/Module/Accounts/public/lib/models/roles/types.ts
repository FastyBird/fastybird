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
} from '@/lib/models/accounts/types'

// ENTITY TYPES
// ============

export enum RoleEntityTypes {
  ROLE = 'com.fastybird.accounts-module/role',
}

// ENTITY INTERFACE
// ================

export interface RoleInterface {
  readonly id: string
  readonly type: RoleEntityTypes

  name: string
  description: string

  anonymous: boolean
  authenticated: boolean
  administrator: boolean

  // Relations
  relationshipNames: string[]
}

// API RESPONSES
// =============

interface RoleAttributesResponseInterface {
  name: string
  description: string
  anonymous: boolean
  authenticated: boolean
  administrator: boolean
}

interface RoleAccountRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: AccountEntityTypes
}

interface RoleAccountRelationshipsResponseInterface extends TJsonApiRelation {
  data: RoleAccountRelationshipResponseInterface[]
}

interface RoleRelationshipsResponseInterface extends TJsonApiRelationships {
  accounts: RoleAccountRelationshipsResponseInterface
}

export interface RoleDataResponseInterface extends TJsonApiData {
  id: string,
  type: RoleEntityTypes,
  attributes: RoleAttributesResponseInterface,
  relationships: RoleRelationshipsResponseInterface,
}

export interface RoleResponseInterface extends TJsonApiBody {
  data: RoleDataResponseInterface,
  includes?: (AccountDataResponseInterface)[],
}

export interface RolesResponseInterface extends TJsonApiBody {
  data: RoleDataResponseInterface[],
  includes?: (AccountDataResponseInterface)[],
}
