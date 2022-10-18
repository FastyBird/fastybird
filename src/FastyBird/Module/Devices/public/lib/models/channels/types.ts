import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
  TJsonApiRelationshipData,
} from 'jsona/lib/JsonaTypes'

import {
  ChannelPropertyInterface,
  ChannelPropertyDataResponseInterface,
} from '@/lib/models/channel-properties/types'
import {
  ChannelControlDataResponseInterface,
  ChannelControlInterface,
} from '@/lib/models/channel-controls/types'
import {
  DeviceInterface,
  DeviceDataResponseInterface,
} from '@/lib/models/devices/types'

// ENTITY INTERFACE
// ================

export interface ChannelInterface {
  id: string
  type: string

  draft: boolean

  identifier: string
  name: string | null
  comment: string | null

  // Relations
  relationshipNames: string[]

  controls: ChannelControlInterface[]
  properties: ChannelPropertyInterface[]

  device: DeviceInterface | null
  deviceBackward: DeviceInterface | null
  deviceId: string

  // Entity transformers
  title: string
  hasComment: boolean
}

// API RESPONSES
// =============

interface ChannelAttributesResponseInterface {
  identifier: string
  name: string | null
  comment: string | null
}

interface ChannelDeviceRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ChannelDeviceRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelDeviceRelationshipResponseInterface
}

interface ChannelPropertyRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ChannelPropertiesRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelPropertyRelationshipResponseInterface[]
}

interface ChannelControlRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ChannelControlsRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelControlRelationshipResponseInterface[]
}

interface ChannelRelationshipsResponseInterface extends TJsonApiRelationships {
  device: ChannelDeviceRelationshipsResponseInterface
  properties: ChannelPropertiesRelationshipsResponseInterface
  controls: ChannelControlsRelationshipsResponseInterface
}

export interface ChannelDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: ChannelAttributesResponseInterface
  relationships: ChannelRelationshipsResponseInterface
}

export interface ChannelResponseInterface extends TJsonApiBody {
  data: ChannelDataResponseInterface
  included?: (DeviceDataResponseInterface | ChannelPropertyDataResponseInterface | ChannelControlDataResponseInterface)[]
}

export interface ChannelsResponseInterface extends TJsonApiBody {
  data: ChannelDataResponseInterface[]
  included?: (DeviceDataResponseInterface | ChannelPropertyDataResponseInterface | ChannelControlDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface ChannelCreateInterface {
  type: string

  identifier: string
  name?: string | null
  comment?: string | null
}

// UPDATE ENTITY INTERFACES
// ========================

export interface ChannelUpdateInterface {
  name?: string | null
  comment?: string | null
}
