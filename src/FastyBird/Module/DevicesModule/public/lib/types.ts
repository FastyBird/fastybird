import { ModulePrefix } from '@fastybird/metadata'

import { TJsonaModel } from 'jsona/lib/JsonaTypes'

export interface DeviceJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface DevicePropertyJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface DeviceControlJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ChannelJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ChannelPropertyJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ChannelControlJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ConnectorJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ConnectorPropertyJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface ConnectorControlJsonModelInterface extends TJsonaModel {
  id: string
  type: string
}

export interface RelationInterface extends TJsonaModel {
  id: string
  type: string
}

export const ModuleApiPrefix = `/${ModulePrefix.MODULE_DEVICES}`

// STORE
// =====

export enum SemaphoreTypes {
  FETCHING = 'fetching',
  GETTING = 'getting',
  CREATING = 'creating',
  UPDATING = 'updating',
  DELETING = 'deleting',
}
