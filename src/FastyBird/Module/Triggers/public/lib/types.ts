import { ModulePrefix } from '@fastybird/metadata'

import { TJsonaModel } from 'jsona/lib/JsonaTypes'

import { TriggerEntityTypes } from '@/lib/models/triggers/types'
import { TriggerControlEntityTypes } from '@/lib/models/trigger-controls/types'
import { ActionEntityTypes } from '@/lib/models/actions/types'
import { ConditionEntityTypes } from '@/lib/models/conditions/types'
import { NotificationEntityTypes } from '@/lib/models/notifications/types'

export interface TriggerJsonModelInterface extends TJsonaModel {
  id: string
  type: TriggerEntityTypes
}

export interface TriggerControlJsonModelInterface extends TJsonaModel {
  id: string
  type: TriggerControlEntityTypes
}

export interface ActionJsonModelInterface extends TJsonaModel {
  id: string
  type: ActionEntityTypes
}

export interface ConditionJsonModelInterface extends TJsonaModel {
  id: string
  type: ConditionEntityTypes
}

export interface NotificationJsonModelInterface extends TJsonaModel {
  id: string
  type: NotificationEntityTypes
}

export interface RelationInterface extends TJsonaModel {
  id: string
  type: TriggerEntityTypes | TriggerControlEntityTypes | ActionEntityTypes | NotificationEntityTypes | ConditionEntityTypes
}

export const ModuleApiPrefix = `/${ModulePrefix.MODULE_TRIGGERS}`

// STORE
// =====

export enum SemaphoreTypes {
  FETCHING = 'fetching',
  GETTING = 'getting',
  CREATING = 'creating',
  UPDATING = 'updating',
  DELETING = 'deleting',
}
