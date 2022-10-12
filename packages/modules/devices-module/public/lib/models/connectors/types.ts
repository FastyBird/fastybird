import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
  TJsonApiRelationshipData,
} from 'jsona/lib/JsonaTypes'

import { ConnectorControlDataResponseInterface } from '@/lib/models/connector-controls/types'
import { ConnectorPropertyInterface } from '@/lib/models/connector-properties/types'
import { DeviceInterface } from '@/lib/models/devices/types'

// ENTITY INTERFACE
// ================

export interface ConnectorInterface {
  id: string
  type: string
  connector: { source: string, type: string }

  identifier: string
  name: string
  comment: string | null
  enabled: boolean

  // Relations
  relationshipNames: string[]

  devices: DeviceInterface[]

  owner: string | null

  // Entity transformers
  isEnabled: boolean
  stateProperty: ConnectorPropertyInterface | null
  title: string
  hasComment: boolean
}

// API RESPONSES
// =============

interface ConnectorAttributesResponseInterface {
  name: string
  enabled: boolean
}

interface ConnectorDeviceRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ConnectorDevicesRelationshipsResponseInterface extends TJsonApiRelation {
  data: ConnectorDeviceRelationshipResponseInterface[]
}

interface ConnectorControlRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ConnectorControlsRelationshipsResponseInterface extends TJsonApiRelation {
  data: ConnectorControlRelationshipResponseInterface[]
}

interface ConnectorRelationshipsResponseInterface extends TJsonApiRelationships {
  devices: ConnectorDevicesRelationshipsResponseInterface
  controls: ConnectorControlsRelationshipsResponseInterface
}

export interface ConnectorDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: ConnectorAttributesResponseInterface
  relationships: ConnectorRelationshipsResponseInterface
}

export interface ConnectorResponseInterface extends TJsonApiBody {
  data: ConnectorDataResponseInterface
  included?: (ConnectorControlDataResponseInterface)[]
}

export interface ConnectorsResponseInterface extends TJsonApiBody {
  data: ConnectorDataResponseInterface[]
  included?: (ConnectorControlDataResponseInterface)[]
}

// UPDATE ENTITY INTERFACES
// ========================

export interface ConnectorUpdateInterface {
  name?: string
  enabled?: boolean
}

export interface FbMqttConnectorUpdateInterface extends ConnectorUpdateInterface {
  server?: string | null
  port?: number | null
  securedPort?: number | null
  username?: string | null
  password?: string | null
}

export interface FbBusConnectorUpdateInterface extends ConnectorUpdateInterface {
  address?: number | null
  serialInterface?: string | null
  baudRate?: number | null
}

export interface ShellyConnectorUpdateInterface extends ConnectorUpdateInterface {
  name?: string
}

export interface TuyaConnectorUpdateInterface extends ConnectorUpdateInterface {
  name?: string
}

export interface SonoffConnectorUpdateInterface extends ConnectorUpdateInterface {
  name?: string
}

export interface ModbusConnectorUpdateInterface extends ConnectorUpdateInterface {
  serialInterface?: string | null
  baudRate?: number | null
}
