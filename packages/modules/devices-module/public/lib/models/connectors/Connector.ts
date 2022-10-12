import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'
import { ConnectorPropertyName } from '@fastybird/metadata'

import capitalize from 'lodash/capitalize'

import {
  ConnectorInterface,
  ConnectorUpdateInterface,
} from '@/lib/models/connectors/types'
import ConnectorProperty from '@/lib/models/connector-properties/ConnectorProperty'
import { ConnectorPropertyInterface } from '@/lib/models/connector-properties/types'
import Device from '@/lib/models/devices/Device'
import { DeviceInterface } from '@/lib/models/devices/types'
import { CONNECTOR_ENTITY_REG_EXP } from '@/lib/helpers'

// ENTITY MODEL
// ============
export default class Connector extends Model implements ConnectorInterface {
  id!: string
  type!: string
  connector!: { source: string, type: string }

  identifier!: string
  name!: string
  comment!: string | null
  enabled!: boolean

  // Relations
  relationshipNames!: string[]

  devices!: DeviceInterface[]

  owner!: string | null

  static get entity(): string {
    return 'devices_module_connector'
  }

  get isEnabled(): boolean {
    return this.enabled
  }

  get stateProperty(): ConnectorPropertyInterface | null {
    return ConnectorProperty
      .query()
      .where('identifier', ConnectorPropertyName.STATE)
      .where('deviceId', this.id)
      .first()
  }

  get title(): string {
    if (this.name !== null) {
      return this.name
    }

    return capitalize(this.identifier)
  }

  get hasComment(): boolean {
    return this.comment !== null && this.comment !== ''
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),
      connector: this.attr({ source: 'N/A', type: 'N/A' }),

      identifier: this.string(''),
      name: this.string(''),
      comment: this.string(null).nullable(),
      enabled: this.boolean(true),

      // Relations
      relationshipNames: this.attr([]),

      devices: this.hasMany(Device, 'connectorId'),
    }
  }

  static async get(id: string): Promise<boolean> {
    return await Connector.dispatch('get', {
      id,
    })
  }

  static async fetch(): Promise<boolean> {
    return await Connector.dispatch('fetch')
  }

  static async edit(connector: ConnectorInterface, data: ConnectorUpdateInterface): Promise<Item<Connector>> {
    return await Connector.dispatch('edit', {
      connector,
      data,
    })
  }

  static reset(): Promise<void> {
    return Connector.dispatch('reset')
  }

  static beforeCreate(items: ConnectorInterface[] | ConnectorInterface): ConnectorInterface[] | ConnectorInterface {
    if (Array.isArray(items)) {
      return items.map((item: ConnectorInterface) => {
        return Object.assign(item, clearConnectorAttributes(item))
      })
    } else {
      return Object.assign(items, clearConnectorAttributes(items))
    }
  }

  static beforeUpdate(items: ConnectorInterface[] | ConnectorInterface): ConnectorInterface[] | ConnectorInterface {
    if (Array.isArray(items)) {
      return items.map((item: ConnectorInterface) => {
        return Object.assign(item, clearConnectorAttributes(item))
      })
    } else {
      return Object.assign(items, clearConnectorAttributes(items))
    }
  }
}

const clearConnectorAttributes = (item: {[key: string]: any}): {[key: string]: any} => {
  const typeRegex = new RegExp(CONNECTOR_ENTITY_REG_EXP)

  const parsedTypes = typeRegex.exec(`${item.type}`)

  item.connector = { source: 'N/A', type: 'N/A' }

  if (
    parsedTypes !== null
    && 'groups' in parsedTypes
    && typeof parsedTypes.groups !== 'undefined'
    && 'source' in parsedTypes.groups
    && 'type' in parsedTypes.groups
  ) {
    item.connector = {
      source: parsedTypes.groups.source,
      type: parsedTypes.groups.type,
    }
  }

  return item
}
