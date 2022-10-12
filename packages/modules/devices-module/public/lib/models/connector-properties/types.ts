import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { DataType } from '@fastybird/metadata'

import {
  ConnectorDataResponseInterface,
  ConnectorInterface,
} from '@/lib/models/connectors/types'
import { PropertyInterface } from '@/lib/models/properties/types'

// ENTITY INTERFACE
// ================

export interface ConnectorPropertyInterface extends PropertyInterface {
  connector: ConnectorInterface | null
  connectorBackward: ConnectorInterface | null

  connectorId: string

  title: string
}

// API RESPONSES
// =============

interface ConnectorPropertyAttributesResponseInterface {
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

interface ConnectorRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ConnectorRelationshipsResponseInterface extends TJsonApiRelation {
  data: ConnectorRelationshipResponseInterface
}

interface ConnectorPropertyRelationshipsResponseInterface extends TJsonApiRelationships {
  connector: ConnectorRelationshipsResponseInterface
}

export interface ConnectorPropertyDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: ConnectorPropertyAttributesResponseInterface
  relationships: ConnectorPropertyRelationshipsResponseInterface
}

export interface ConnectorPropertyResponseInterface extends TJsonApiBody {
  data: ConnectorPropertyDataResponseInterface
  included?: (ConnectorDataResponseInterface)[]
}

export interface ConnectorPropertiesResponseInterface extends TJsonApiBody {
  data: ConnectorPropertyDataResponseInterface[]
  included?: (ConnectorDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface ConnectorPropertyCreateInterface {
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

export interface ConnectorPropertyUpdateInterface {
  name?: string | null
  settable?: boolean
  queryable?: boolean
  dataType?: string | null
  unit?: string | null
  format?: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid?: string | number | null
  numberOfDecimals?: number | null
}
