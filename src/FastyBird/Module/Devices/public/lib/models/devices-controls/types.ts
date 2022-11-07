import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { _GettersTree } from 'pinia'

import {
  IControlsAddActionPayload,
  IControl,
  IControlRecordFactoryPayload,
  IDevice,
  IDeviceResponseData,
  IPlainRelation,
  IDeviceResponseModel,
  IControlResponseModel,
} from '@/lib/models/types'

// STORE
// =====

export interface IDeviceControlsState {
  semaphore: IDeviceControlsStateSemaphore
  firstLoad: string[]
  data: { [key: string]: IDeviceControl }
}

export interface IDeviceControlsGetters extends _GettersTree<IDeviceControlsState> {
  firstLoadFinished: (state: IDeviceControlsState) => ((deviceId: string) => boolean)
  getting: (state: IDeviceControlsState) => ((controlId: string) => boolean)
  fetching: (state: IDeviceControlsState) => ((deviceId: string | null) => boolean)
  findById: (state: IDeviceControlsState) => ((id: string) => IDeviceControl | null)
  findByName: (state: IDeviceControlsState) => ((device: IDevice, name: string) => IDeviceControl | null)
  findForDevice: (state: IDeviceControlsState) => ((deviceId: string) => IDeviceControl[])
}

export interface IDeviceControlsActions {
  set: (payload: IDeviceControlsSetActionPayload) => Promise<IDeviceControl>
  unset: (payload: IDeviceControlsUnsetActionPayload) => void
  get: (payload: IDeviceControlsGetActionPayload) => Promise<boolean>
  fetch: (payload: IDeviceControlsFetchActionPayload) => Promise<boolean>
  add: (payload: IDeviceControlsAddActionPayload) => Promise<IDeviceControl>
  save: (payload: IDeviceControlsSaveActionPayload) => Promise<IDeviceControl>
  remove: (payload: IDeviceControlsRemoveActionPayload) => Promise<boolean>
  socketData: (payload: IDeviceControlsSocketDataActionPayload) => Promise<boolean>
}

// STORE STATE
// ===========

export interface IDeviceControlsStateSemaphore {
  fetching: IDeviceControlsStateSemaphoreFetching
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface IDeviceControlsStateSemaphoreFetching {
  items: string[]
  item: string[]
}

// STORE MODELS
// ============

export interface IDeviceControl extends IControl {
  // Relations
  device: IPlainRelation
}

// STORE DATA FACTORIES
// ====================

export interface IDeviceControlRecordFactoryPayload extends IControlRecordFactoryPayload {
  // Relations
  deviceId: string
}

// STORE ACTIONS
// =============

export interface IDeviceControlsSetActionPayload {
  data: IDeviceControlRecordFactoryPayload
}

export interface IDeviceControlsUnsetActionPayload {
  device?: IDevice
  id?: string
}

export interface IDeviceControlsGetActionPayload {
  device: IDevice
  id: string
}

export interface IDeviceControlsFetchActionPayload {
  device: IDevice
}

export interface IDeviceControlsAddActionPayload extends IControlsAddActionPayload {
  device: IDevice
}

export interface IDeviceControlsSaveActionPayload {
  id: string
}

export interface IDeviceControlsRemoveActionPayload {
  id: string
}

export interface IDeviceControlsSocketDataActionPayload {
  source: string
  routingKey: string
  data: string
}

// API RESPONSES JSONS
// ===================

export interface IDeviceControlResponseJson extends TJsonApiBody {
  data: IDeviceControlResponseData
  includes?: IDeviceResponseData[]
}

export interface IDeviceControlsResponseJson extends TJsonApiBody {
  data: IDeviceControlResponseData[]
  includes?: IDeviceResponseData[]
}

export interface IDeviceControlResponseData extends TJsonApiData {
  id: string
  type: string
  attributes: IDeviceControlResponseDataAttributes
  relationships: IDeviceControlResponseDataRelationships
}

interface IDeviceControlResponseDataAttributes {
  name: string
}

interface IDeviceControlResponseDataRelationships extends TJsonApiRelationships {
  device: TJsonApiRelation
}

// API RESPONSE MODELS
// ===================

export interface IDeviceControlResponseModel extends IControlResponseModel {
  // Relations
  device: IPlainRelation | IDeviceResponseModel
}
