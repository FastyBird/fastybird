import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'
import { _GettersTree } from 'pinia'

import {
  IChannel,
  IChannelResponseData,
  IControlsAddActionPayload,
  IControl,
  IControlRecordFactoryPayload,
  IPlainRelation,
  IChannelResponseModel,
  IControlResponseModel,
} from '@/lib/models/types'

// STORE
// =====

export interface IChannelControlsState {
  semaphore: IChannelControlsStateSemaphore
  firstLoad: string[]
  data: { [key: string]: IChannelControl }
}

export interface IChannelControlsGetters extends _GettersTree<IChannelControlsState> {
  firstLoadFinished: (state: IChannelControlsState) => ((channelId: string) => boolean)
  getting: (state: IChannelControlsState) => ((controlId: string) => boolean)
  fetching: (state: IChannelControlsState) => ((channelId: string | null) => boolean)
  findById: (state: IChannelControlsState) => ((id: string) => IChannelControl | null)
  findByName: (state: IChannelControlsState) => ((channel: IChannel, name: string) => IChannelControl | null)
  findForChannel: (state: IChannelControlsState) => ((channelId: string) => IChannelControl[])
}

export interface IChannelControlsActions {
  set: (payload: IChannelControlsSetActionPayload) => Promise<IChannelControl>
  unset: (payload: IChannelControlsUnsetActionPayload) => void
  get: (payload: IChannelControlsGetActionPayload) => Promise<boolean>
  fetch: (payload: IChannelControlsFetchActionPayload) => Promise<boolean>
  add: (payload: IChannelControlsAddActionPayload) => Promise<IChannelControl>
  save: (payload: IChannelControlsSaveActionPayload) => Promise<IChannelControl>
  remove: (payload: IChannelControlsRemoveActionPayload) => Promise<boolean>
  transmitCommand: (payload: IChannelControlsTransmitCommandActionPayload) => Promise<boolean>
  socketData: (payload: IChannelControlsSocketDataActionPayload) => Promise<boolean>
}

// STORE STATE
// ===========

export interface IChannelControlsStateSemaphore {
  fetching: IChannelControlsStateSemaphoreFetching
  creating: string[]
  updating: string[]
  deleting: string[]
}

interface IChannelControlsStateSemaphoreFetching {
  items: string[]
  item: string[]
}

// STORE MODELS
// ============

export interface IChannelControl extends IControl {
  // Relations
  channel: IPlainRelation
}

// STORE DATA FACTORIES
// ====================

export interface IChannelControlRecordFactoryPayload extends IControlRecordFactoryPayload {
  // Relations
  channelId: string
}

// STORE ACTIONS
// =============

export interface IChannelControlsSetActionPayload {
  data: IChannelControlRecordFactoryPayload
}

export interface IChannelControlsUnsetActionPayload {
  channel?: IChannel
  id?: string
}

export interface IChannelControlsGetActionPayload {
  channel: IChannel
  id: string
}

export interface IChannelControlsFetchActionPayload {
  channel: IChannel
}

export interface IChannelControlsAddActionPayload extends IControlsAddActionPayload {
  channel: IChannel
}

export interface IChannelControlsSaveActionPayload {
  id: string
}

export interface IChannelControlsRemoveActionPayload {
  id: string
}

export interface IChannelControlsTransmitCommandActionPayload {
  id: string
  value?: string
}

export interface IChannelControlsSocketDataActionPayload {
  source: string
  routingKey: string
  data: string
}

// API RESPONSES JSONS
// ===================

export interface IChannelControlResponseJson extends TJsonApiBody {
  data: IChannelControlResponseData
  includes?: IChannelResponseData[]
}

export interface IChannelControlsResponseJson extends TJsonApiBody {
  data: IChannelControlResponseData[]
  includes?: IChannelResponseData[]
}

export interface IChannelControlResponseData extends TJsonApiData {
  id: string
  type: string
  attributes: IChannelControlResponseDataAttributes
  relationships: IChannelControlResponseDataRelationships
}

interface IChannelControlResponseDataAttributes {
  name: string
}

interface IChannelControlResponseDataRelationships extends TJsonApiRelationships {
  channel: TJsonApiRelation
}

// API RESPONSE MODELS
// ===================

export interface IChannelControlResponseModel extends IControlResponseModel {
  // Relations
  channel: IPlainRelation | IChannelResponseModel
}
