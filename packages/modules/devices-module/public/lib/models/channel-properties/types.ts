import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { DataType } from '@fastybird/metadata'

import {
  ChannelDataResponseInterface,
  ChannelInterface,
} from '@/lib/models/channels/types'
import { PropertyInterface } from '@/lib/models/properties/types'

// ENTITY INTERFACE
// ================

export interface ChannelPropertyInterface extends PropertyInterface {
  channel: ChannelInterface | null
  channelBackward: ChannelInterface | null
  channelId: string

  title: string

  parent: ChannelPropertyInterface | null
  parentBackward: ChannelPropertyInterface | null
  parentId: string | null

  children: ChannelPropertyInterface[]
}

// API RESPONSES
// =============

interface ChannelPropertyAttributesResponseInterface {
  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  dataType: DataType | null
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  numberOfDecimals: number | null

  value: string | number | boolean | null

  actualValue: string | number | boolean | null
  expectedValue: string | number | boolean | null
  pending: boolean
}

interface ChannelPropertyRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ChannelRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelPropertyRelationshipResponseInterface
}

interface ChannelPropertyParentRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelPropertyRelationshipResponseInterface
}

interface ChannelPropertyChildrenRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelPropertyRelationshipResponseInterface[]
}

interface ChannelPropertyRelationshipsResponseInterface extends TJsonApiRelationships {
  channel: ChannelRelationshipsResponseInterface
  parent: ChannelPropertyParentRelationshipsResponseInterface
  children: ChannelPropertyChildrenRelationshipsResponseInterface
}

export interface ChannelPropertyDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: ChannelPropertyAttributesResponseInterface
  relationships: ChannelPropertyRelationshipsResponseInterface
}

export interface ChannelPropertyResponseInterface extends TJsonApiBody {
  data: ChannelPropertyDataResponseInterface
  included?: (ChannelDataResponseInterface)[]
}

export interface ChannelPropertiesResponseInterface extends TJsonApiBody {
  data: ChannelPropertyDataResponseInterface[]
  included?: (ChannelDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface ChannelPropertyCreateInterface {
  type: string

  identifier: string
  name?: string | null
  settable?: boolean
  queryable?: boolean
  dataType?: string | null
  unit?: string | null
  format?: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid?: string | number | null
  numberOfDecimals?: number | null
}

// UPDATE ENTITY INTERFACES
// ========================

export interface ChannelPropertyUpdateInterface {
  name?: string | null
  settable?: boolean
  queryable?: boolean
  dataType?: string | null
  unit?: string | null
  format?: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid?: string | number | null
  numberOfDecimals?: number | null
}
