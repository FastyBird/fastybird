import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { _GettersTree } from 'pinia'

import { DataType } from '@fastybird/metadata-library'

import {
  IConnector,
  IConnectorResponseData,
  IConnectorResponseModel,
  IPlainRelation,
  IPropertiesAddActionPayload,
  IPropertiesEditActionPayload,
  IProperty,
  IPropertyRecordFactoryPayload,
  IPropertyResponseModel,
} from '@/lib/models/types'

// STORE
// =====

export interface IConnectorPropertiesState {
  semaphore: IConnectorPropertiesStateSemaphore
  firstLoad: string[]
  data: { [key: string]: IConnectorProperty }
}

export interface IConnectorPropertiesGetters extends _GettersTree<IConnectorPropertiesState> {
  firstLoadFinished: (state: IConnectorPropertiesState) => ((connectorId: string) => boolean)
  getting: (state: IConnectorPropertiesState) => ((propertyId: string) => boolean)
  fetching: (state: IConnectorPropertiesState) => ((connectorId: string | null) => boolean)
  findById: (state: IConnectorPropertiesState) => ((id: string) => IConnectorProperty | null)
  findByIdentifier: (state: IConnectorPropertiesState) => ((connector: IConnector, identifier: string) => IConnectorProperty | null)
  findForConnector: (state: IConnectorPropertiesState) => ((connectorId: string) => IConnectorProperty[])
}

export interface IConnectorPropertiesActions {
  set: (payload: IConnectorPropertiesSetActionPayload) => Promise<IConnectorProperty>
  unset: (payload: IConnectorPropertiesUnsetActionPayload) => void
  get: (payload: IConnectorPropertiesGetActionPayload) => Promise<boolean>
  fetch: (payload: IConnectorPropertiesFetchActionPayload) => Promise<boolean>
  add: (payload: IConnectorPropertiesAddActionPayload) => Promise<IConnectorProperty>
  edit: (payload: IConnectorPropertiesEditActionPayload) => Promise<IConnectorProperty>
  save: (payload: IConnectorPropertiesSaveActionPayload) => Promise<IConnectorProperty>
  remove: (payload: IConnectorPropertiesRemoveActionPayload) => Promise<boolean>
  socketData: (payload: IConnectorPropertiesSocketDataActionPayload) => Promise<boolean>
}

// STORE STATE
// ===========

export interface IConnectorPropertiesStateSemaphore {
  fetching: IConnectorPropertiesStateSemaphoreFetching
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface IConnectorPropertiesStateSemaphoreFetching {
  items: string[]
  item: string[]
}

// STORE MODELS
// ============

export interface IConnectorProperty extends IProperty {
  // Relations
  connector: IPlainRelation
}

// STORE DATA FACTORIES
// ====================

export interface IConnectorPropertyRecordFactoryPayload extends IPropertyRecordFactoryPayload {
  // Relations
  connectorId: string
}

// STORE ACTIONS
// =============

export interface IConnectorPropertiesSetActionPayload {
  data: IConnectorPropertyRecordFactoryPayload
}

export interface IConnectorPropertiesUnsetActionPayload {
  connector?: IConnector
  id?: string
}

export interface IConnectorPropertiesGetActionPayload {
  connector: IConnector
  id: string
}

export interface IConnectorPropertiesFetchActionPayload {
  connector: IConnector
}

export interface IConnectorPropertiesAddActionPayload extends IPropertiesAddActionPayload {
  connector: IConnector
}

export interface IConnectorPropertiesEditActionPayload extends IPropertiesEditActionPayload {
}

export interface IConnectorPropertiesSaveActionPayload {
  id: string
}

export interface IConnectorPropertiesRemoveActionPayload {
  id: string
}

export interface IConnectorPropertiesSocketDataActionPayload {
  source: string
  routingKey: string
  data: string
}

// API RESPONSES JSONS
// ===================

export interface IConnectorPropertyResponseJson extends TJsonApiBody {
  data: IConnectorPropertyResponseData
  includes?: IConnectorResponseData[]
}

export interface IConnectorPropertiesResponseJson extends TJsonApiBody {
  data: IConnectorPropertyResponseData[]
  includes?: IConnectorResponseData[]
}

export interface IConnectorPropertyResponseData extends TJsonApiData {
  id: string
  type: string
  attributes: IConnectorPropertyResponseDataAttributes
  relationships: IConnectorPropertyResponseDataRelationships
}

interface IConnectorPropertyResponseDataAttributes {
  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  data_type: DataType | null
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  number_of_decimals: number | null

  value: string | number | boolean | null

  actual_value: string | number | boolean | null
  expected_value: string | number | boolean | null
  pending: boolean
}

interface IConnectorPropertyResponseDataRelationships extends TJsonApiRelationships {
  connector: TJsonApiRelation
}

// API RESPONSE MODELS
// ===================

export interface IConnectorPropertyResponseModel extends IPropertyResponseModel {
  // Relations
  connector: IPlainRelation | IConnectorResponseModel
}
