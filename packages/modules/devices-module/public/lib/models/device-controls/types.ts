import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'

import {
  DeviceDataResponseInterface,
  DeviceInterface,
} from '@/lib/models/devices/types'

// ENTITY INTERFACE
// ================

export interface DeviceControlInterface {
  id: string
  type: string

  name: string

  // Relations
  relationshipNames: string[]

  device: DeviceInterface | null
  deviceBackward: DeviceInterface | null
  deviceId: string
}

// API RESPONSES
// =============

interface DeviceControlAttributesResponseInterface {
  name: string
}

interface DeviceRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface DeviceRelationshipsResponseInterface extends TJsonApiRelation {
  data: DeviceRelationshipResponseInterface
}

interface DeviceControlRelationshipsResponseInterface extends TJsonApiRelationships {
  device: DeviceRelationshipsResponseInterface
}

export interface DeviceControlDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: DeviceControlAttributesResponseInterface
  relationships: DeviceControlRelationshipsResponseInterface
}

export interface DeviceControlResponseInterface extends TJsonApiBody {
  data: DeviceControlDataResponseInterface
  included?: (DeviceDataResponseInterface)[]
}

export interface DeviceControlsResponseInterface extends TJsonApiBody {
  data: DeviceControlDataResponseInterface[]
  included?: (DeviceDataResponseInterface)[]
}
