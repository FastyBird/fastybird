import {
  TJsonApiBody,
  TJsonApiData,
  TJsonApiRelation,
  TJsonApiRelationshipData,
  TJsonApiRelationships,
} from 'jsona/lib/JsonaTypes'

import {
  ChannelDataResponseInterface,
  ChannelInterface,
} from '@/lib/models/channels/types'

// ENTITY INTERFACE
// ================

export interface ChannelControlInterface {
  id: string
  type: string

  name: string

  // Relations
  relationshipNames: string[]

  channel: ChannelInterface | null
  channelBackward: ChannelInterface | null
  channelId: string
}

// API RESPONSES
// =============

interface ChannelControlAttributesResponseInterface {
  name: string
}

interface ChannelRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: string
}

interface ChannelRelationshipsResponseInterface extends TJsonApiRelation {
  data: ChannelRelationshipResponseInterface
}

interface ChannelControlRelationshipsResponseInterface extends TJsonApiRelationships {
  channel: ChannelRelationshipsResponseInterface
}

export interface ChannelControlDataResponseInterface extends TJsonApiData {
  id: string
  type: string
  attributes: ChannelControlAttributesResponseInterface
  relationships: ChannelControlRelationshipsResponseInterface
}

export interface ChannelControlResponseInterface extends TJsonApiBody {
  data: ChannelControlDataResponseInterface
  included?: (ChannelDataResponseInterface)[]
}

export interface ChannelControlsResponseInterface extends TJsonApiBody {
  data: ChannelControlDataResponseInterface[]
  included?: (ChannelDataResponseInterface)[]
}
