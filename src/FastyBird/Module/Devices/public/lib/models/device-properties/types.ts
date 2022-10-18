import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { DataType } from '@fastybird/metadata'

import {
  DeviceDataResponseInterface,
  DeviceInterface,
} from '@/lib/models/devices/types'
import { PropertyInterface } from '@/lib/models/properties/types'

// ENTITY INTERFACE
// ================

export interface DevicePropertyInterface extends PropertyInterface {
  device: DeviceInterface | null
  deviceBackward: DeviceInterface | null
  deviceId: string

  title: string

  parent: DevicePropertyInterface | null
  parentBackward: DevicePropertyInterface | null
  parentId: string | null

  children: DevicePropertyInterface[]
}

// API RESPONSES
// =============

interface DevicePropertyAttributesResponseInterface {
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

interface DevicePropertyRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DeviceRelationshipsResponseInterface extends TJsonApiRelation {
  data: DevicePropertyRelationshipResponseInterface
}

interface DevicePropertyParentRelationshipsResponseInterface extends TJsonApiRelation {
  data: DevicePropertyRelationshipResponseInterface
}

interface DevicePropertyChildrenRelationshipsResponseInterface extends TJsonApiRelation {
  data: DevicePropertyRelationshipResponseInterface[]
}

interface DevicePropertyRelationshipsResponseInterface extends TJsonApiRelationships {
  device: DeviceRelationshipsResponseInterface
  parent: DevicePropertyParentRelationshipsResponseInterface
  children: DevicePropertyChildrenRelationshipsResponseInterface
}

export interface DevicePropertyDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: DevicePropertyAttributesResponseInterface
  relationships: DevicePropertyRelationshipsResponseInterface
}

export interface DevicePropertyResponseInterface extends TJsonApiBody {
  data: DevicePropertyDataResponseInterface
  included?: (DeviceDataResponseInterface)[]
}

export interface DevicePropertiesResponseInterface extends TJsonApiBody {
  data: DevicePropertyDataResponseInterface[]
  included?: (DeviceDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface DevicePropertyCreateInterface {
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

export interface DevicePropertyUpdateInterface {
  name?: string | null
  settable?: boolean
  queryable?: boolean
  dataType?: string | null
  unit?: string | null
  format?: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid?: string | number | null
  numberOfDecimals?: number | null
}
