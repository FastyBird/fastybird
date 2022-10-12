import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
  TJsonApiRelationshipData,
} from 'jsona/lib/JsonaTypes'

import {
  ChannelInterface,
  ChannelDataResponseInterface,
} from '@/lib/models/channels/types'
import {
  DeviceControlDataResponseInterface,
  DeviceControlInterface,
} from '@/lib/models/device-controls/types'
import {
  DevicePropertyInterface,
  DevicePropertyDataResponseInterface,
} from '@/lib/models/device-properties/types'
import { ConnectorInterface } from '@/lib/models/connectors/types'

// ENTITY INTERFACE
// ================

export interface DeviceInterface {
  id: string
  type: string
  device: { source: string, type: string }

  draft: boolean

  identifier: string
  name: string | null
  comment: string | null

  enabled: boolean

  hardwareModel: string
  hardwareManufacturer: string
  hardwareVersion: string | null
  macAddress: string | null

  firmwareManufacturer: string
  firmwareVersion: string | null

  // Relations
  relationshipNames: string[]

  parent: DeviceInterface | null
  parentBackward: DeviceInterface | null
  parentId: string | null

  children: DeviceInterface[]

  channels: ChannelInterface[]
  controls: DeviceControlInterface[]
  properties: DevicePropertyInterface[]

  connectorId: string
  connector: ConnectorInterface
  connectorBackward: ConnectorInterface

  owner: string | null

  // Entity transformers
  isEnabled: boolean
  stateProperty: DevicePropertyInterface | null
  title: string
  hasComment: boolean
}

// API RESPONSES
// =============

interface DeviceAttributesResponseInterface {
  identifier: string
  name: string | null
  comment: string | null

  enabled: boolean

  owner: string | null
}

interface DeviceRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DeviceParentRelationshipsResponseInterface extends TJsonApiRelation {
  data: DeviceRelationshipResponseInterface
}

interface DeviceChildrenRelationshipsResponseInterface extends TJsonApiRelation {
  data: DeviceRelationshipResponseInterface[]
}

interface DevicePropertyRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DevicePropertiesRelationshipsResponseInterface extends TJsonApiRelation {
  data: DevicePropertyRelationshipResponseInterface[]
}

interface DeviceControlRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DeviceControlsRelationshipsResponseInterface extends TJsonApiRelation {
  data: DeviceControlRelationshipResponseInterface[]
}

interface DeviceChannelRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DeviceChannelsRelationshipsResponseInterface extends TJsonApiRelation {
  data: DeviceChannelRelationshipResponseInterface[]
}

interface DeviceRelationshipsResponseInterface extends TJsonApiRelationships {
  parent: DeviceParentRelationshipsResponseInterface
  children: DeviceChildrenRelationshipsResponseInterface
  properties: DevicePropertiesRelationshipsResponseInterface
  controls: DeviceControlsRelationshipsResponseInterface
  channels: DeviceChannelsRelationshipsResponseInterface
}

export interface DeviceDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: DeviceAttributesResponseInterface
  relationships: DeviceRelationshipsResponseInterface
}

export interface DeviceResponseInterface extends TJsonApiBody {
  data: DeviceDataResponseInterface
  included?: (ChannelDataResponseInterface | DevicePropertyDataResponseInterface | DeviceControlDataResponseInterface | DeviceDataResponseInterface)[]
}

export interface DevicesResponseInterface extends TJsonApiBody {
  data: DeviceDataResponseInterface[]
  included?: (ChannelDataResponseInterface | DevicePropertyDataResponseInterface | DeviceControlDataResponseInterface | DeviceDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface DeviceCreateInterface {
  type: string

  identifier: string
  name?: string | null
  comment?: string | null
  enabled?: boolean
}

// UPDATE ENTITY INTERFACES
// ========================

export interface DeviceUpdateInterface {
  name?: string | null
  comment?: string | null
  enabled?: boolean
}
