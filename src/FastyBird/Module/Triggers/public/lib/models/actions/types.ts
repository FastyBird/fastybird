import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationships,
  TJsonApiRelationshipData,
} from 'jsona/lib/JsonaTypes'

import {
  TriggerInterface,
  TriggerEntityTypes,
  TriggerDataResponseInterface,
} from '@/lib/models/triggers/types'

// ENTITY TYPES
// ============

export enum ActionEntityTypes {
  DEVICE_PROPERTY = 'com.fastybird.triggers-module/action/device-property',
  CHANNEL_PROPERTY = 'com.fastybird.triggers-module/action/channel-property',
}

// ENTITY INTERFACE
// ================

export interface ActionInterface {
  id: string
  type: ActionEntityTypes

  draft: boolean

  enabled: boolean

  value: string
  device: string
  channel?: string
  property: string

  isTriggered: boolean | null

  // Relations
  relationshipNames: string[]

  triggerId: string

  trigger: TriggerInterface | null
  triggerBackward: TriggerInterface | null

  isDeviceProperty: boolean
  isChannelProperty: boolean
}

// API RESPONSES
// =============

interface ActionAttributesResponseInterface {
  enabled: boolean

  // Channel property conditions specific
  device?: string
  channel?: string
  property: string
  value?: string
}

interface ActionTriggerRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: TriggerEntityTypes
}

interface ActionTriggerRelationshipsResponseInterface extends TJsonApiRelation {
  data: ActionTriggerRelationshipResponseInterface
}

interface ActionRelationshipsResponseInterface extends TJsonApiRelationships {
  trigger: ActionTriggerRelationshipsResponseInterface
}

export interface ActionDataResponseInterface extends TJsonApiData {
  id: string
  type: ActionEntityTypes
  attributes: ActionAttributesResponseInterface
  relationships: ActionRelationshipsResponseInterface
}

export interface ActionResponseInterface extends TJsonApiBody {
  data: ActionDataResponseInterface
  included?: (TriggerDataResponseInterface)[]
}

export interface ActionsResponseInterface extends TJsonApiBody {
  data: ActionDataResponseInterface[]
  included?: (TriggerDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface ActionCreateInterface {
  type: ActionEntityTypes

  enabled: boolean
}

export interface CreateDevicePropertyActionInterface extends ActionCreateInterface {
  value: string
  device: string
  property: string
}

export interface CreateChannelPropertyActionInterface extends ActionCreateInterface {
  value: string
  device: string
  channel: string
  property: string
}

// UPDATE ENTITY INTERFACES
// ========================

export interface ActionUpdateInterface {
  enabled?: boolean
}

export interface UpdateDevicePropertyActionInterface extends ActionUpdateInterface {
  value?: string
}

export interface UpdateChannelPropertyActionInterface extends ActionUpdateInterface {
  value?: string
}
