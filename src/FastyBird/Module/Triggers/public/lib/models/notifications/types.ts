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

export enum NotificationEntityTypes {
  EMAIL = 'com.fastybird.triggers-module/notification/email',
  SMS = 'com.fastybird.triggers-module/notification/sms',
}

// ENTITY INTERFACE
// ================

export interface NotificationInterface {
  id: string
  type: NotificationEntityTypes

  draft: boolean

  enabled: boolean

  // Email notifications specific
  email?: string

  // SMS notifications specific
  phone?: string

  // Relations
  relationshipNames: string[]

  triggerId: string

  trigger: TriggerInterface | null
  triggerBackward: TriggerInterface | null

  isSms: boolean
  isEmail: boolean
}

// API RESPONSES
// =============

interface NotificationAttributesResponseInterface {
  enabled: boolean

  // Email notifications specific
  email?: string

  // SMS notifications specific
  phone?: string
}

interface NotificationTriggerRelationshipResponseInterface extends TJsonApiRelationshipData {
  id: string
  type: TriggerEntityTypes
}

interface NotificationTriggerRelationshipsResponseInterface extends TJsonApiRelation {
  data: NotificationTriggerRelationshipResponseInterface
}

interface NotificationRelationshipsResponseInterface extends TJsonApiRelationships {
  trigger: NotificationTriggerRelationshipsResponseInterface
}

export interface NotificationDataResponseInterface extends TJsonApiData {
  id: string
  type: NotificationEntityTypes
  attributes: NotificationAttributesResponseInterface
  relationships: NotificationRelationshipsResponseInterface
}

export interface NotificationResponseInterface extends TJsonApiBody {
  data: NotificationDataResponseInterface
  included?: (TriggerDataResponseInterface)[]
}

export interface NotificationsResponseInterface extends TJsonApiBody {
  data: NotificationDataResponseInterface[]
  included?: (TriggerDataResponseInterface)[]
}

// CREATE ENTITY INTERFACES
// ========================

export interface NotificationCreateInterface {
  type: NotificationEntityTypes

  enabled: boolean
}

export interface CreateSmsNotificationInterface extends NotificationCreateInterface {
  phone: string
}

export interface CreateEmailNotificationInterface extends NotificationCreateInterface {
  email: string
}

// UPDATE ENTITY INTERFACES
// ========================

export interface NotificationUpdateInterface {
  enabled?: boolean
}

export interface UpdateSmsNotificationInterface extends NotificationUpdateInterface {
  phone?: string
}

export interface UpdateEmailNotificationInterface extends NotificationUpdateInterface {
  email?: string
}
