import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'

import {
  TriggerDataResponseInterface,
  TriggerEntityTypes,
  TriggerInterface,
  TriggerResponseInterface,
} from '@/lib/models/triggers/types'

// ENTITY TYPES
// ============

export enum TriggerControlEntityTypes {
  CONTROL = 'com.fastybird.triggers-module/control/trigger',
}

// ENTITY INTERFACE
// ================

export interface TriggerControlInterface {
  id: string
  type: TriggerControlEntityTypes

  name: string

  trigger: TriggerInterface | null
  triggerBackward: TriggerInterface | null

  triggerId: string
}

// API RESPONSES
// =============

interface TriggerControlAttributesResponseInterface {
  name: string
}

interface TriggerRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: TriggerEntityTypes
}

interface TriggerRelationshipsResponseInterface extends TJsonApiRelation {
  data: TriggerRelationshipResponseInterface
}

interface TriggerControlRelationshipsResponseInterface extends TJsonApiRelationships {
  trigger: TriggerRelationshipsResponseInterface
}

export interface TriggerControlDataResponseInterface extends TJsonApiData {
  id: string
  type: TriggerControlEntityTypes
  attributes: TriggerControlAttributesResponseInterface
  relationships: TriggerControlRelationshipsResponseInterface
  included?: (TriggerResponseInterface)[]
}

export interface TriggerControlResponseInterface extends TJsonApiBody {
  data: TriggerControlDataResponseInterface
  included?: (TriggerDataResponseInterface)[]
}

export interface TriggerControlsResponseInterface extends TJsonApiBody {
  data: TriggerControlDataResponseInterface[]
  included?: (TriggerDataResponseInterface)[]
}
