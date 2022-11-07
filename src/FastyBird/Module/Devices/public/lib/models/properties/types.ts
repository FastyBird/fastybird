import { TJsonaModel } from 'jsona/lib/JsonaTypes'

import { DataType } from '@fastybird/metadata-library'

// COMMANDS
// ========

export enum PropertyCommandState {
  SENDING = 'sending',
  COMPLETED = 'completed',
}

export enum PropertyCommandResult {
  OK = 'ok',
  ERR = 'err',
}

// STORE MODELS
// ============

export interface IProperty {
  id: string
  type: { source: string, type: string, parent: string, entity: string }

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

  // Static property
  value: string | number | boolean | Date | null

  /* Dynamic property start */
  actualValue: string | number | boolean | Date | null
  expectedValue: string | number | boolean | Date | null
  pending: boolean

  command: PropertyCommandState | null
  lastResult: PropertyCommandResult | null
  backup: string | null
  /* Dynamic property end */

  // Relations
  relationshipNames: string[]
}

// STORE DATA FACTORIES
// ====================

export interface IPropertyRecordFactoryPayload {
  id?: string
  type: { source: string, type: string, parent?: string, entity?: string }

  draft?: boolean

  identifier: string
  name?: string | null
  settable?: boolean
  queryable?: boolean
  dataType: DataType
  unit?: string | null
  format?: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid?: string | number | null
  numberOfDecimals?: number | null

  // Static property
  value?: string | number | boolean | Date | null

  /* Dynamic property start */
  actualValue?: string | number | boolean | Date | null
  expectedValue?: string | number | boolean | Date | null
  pending?: boolean

  command?: PropertyCommandState | null
  lastResult?: PropertyCommandResult | null
  backup?: string | null
  /* Dynamic property end */

  // Relations
  relationshipNames?: string[]
}

// STORE ACTIONS
// =============

export interface IPropertiesAddActionPayload {
  id?: string
  type: { source: string, type: string, parent?: string, entity?: string }

  draft?: boolean

  data: {
    identifier: string
    name?: string | null
    settable?: boolean
    queryable?: boolean
    dataType: DataType
    unit?: string | null
    format?: string[] | ((string | null)[])[] | (number | null)[] | null
    invalid?: string | number | null
    numberOfDecimals?: number | null

    // Static property
    value?: string | number | boolean | Date | null
  }
}

export interface IPropertiesEditActionPayload {
  id: string

  data: {
    identifier?: string
    name?: string | null
    settable?: boolean
    queryable?: boolean
    dataType?: DataType
    unit?: string | null
    format?: string[] | ((string | null)[])[] | (number | null)[] | null
    invalid?: string | number | null
    numberOfDecimals?: number | null

    // Static property
    value?: string | number | boolean | Date | null

    /* Dynamic property start */
    actualValue?: string | number | boolean | Date | null
    expectedValue?: string | number | boolean | Date | null
    pending?: boolean

    command?: PropertyCommandState | null
    lastResult?: PropertyCommandResult | null
    backup?: string | null
    /* Dynamic property end */
  }
}

// API RESPONSE MODELS
// ===================

export interface IPropertyResponseModel extends TJsonaModel {
  id: string
  type: { source: string, type: string, parent: string, entity: string }

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

  // Relations
  relationshipNames: string[]
}
