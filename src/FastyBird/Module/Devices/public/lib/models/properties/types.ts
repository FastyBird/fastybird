import { DataType } from '@fastybird/metadata'

// ENTITY TYPES
// ============

export enum PropertyCommandState {
  SENDING = 'sending',
  COMPLETED = 'completed',
}

export enum PropertyCommandResult {
  OK = 'ok',
  ERR = 'err',
}

export enum SensorNameTypes {
  SENSOR = 'sensor',
  AIR_QUALITY = 'air_quality',
  LIGHT_LEVEL = 'light_level',
  NOISE_LEVEL = 'noise_level',
  TEMPERATURE = 'temperature',
  HUMIDITY = 'humidity',
  POWER = 'power',
  CURRENT = 'current',
  VOLTAGE = 'voltage',
  ENERGY = 'energy',
}

export enum ActorNameTypes {
  ACTOR = 'actor',
}

// ENTITY INTERFACE
// ================

export interface PropertyInterface {
  id: string
  type: string
  property: { source: string, parent: string, type: string }

  draft: boolean

  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  dataType: DataType
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  numberOfDecimals: number | null

  value: string | number | boolean | Date | null

  actualValue: string | number | boolean | Date | null
  expectedValue: string | number | boolean | Date | null
  pending: boolean

  command: PropertyCommandState | null
  lastResult: PropertyCommandResult | null
  backup: string | null

  // Relations
  relationshipNames: string[]

  // Entity transformers
  icon: string
}
