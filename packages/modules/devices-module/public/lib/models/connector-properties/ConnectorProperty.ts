import {
  Fields,
  Item,
} from '@vuex-orm/core'

import capitalize from 'lodash/capitalize'

import Connector from '@/lib/models/connectors/Connector'
import { ConnectorInterface } from '@/lib/models/connectors/types'
import {
  ConnectorPropertyCreateInterface,
  ConnectorPropertyInterface,
  ConnectorPropertyUpdateInterface,
} from '@/lib/models/connector-properties/types'
import Property from '@/lib/models/properties/Property'

// ENTITY MODEL
// ============
export default class ConnectorProperty extends Property implements ConnectorPropertyInterface {
  connector!: ConnectorInterface | null
  connectorBackward!: ConnectorInterface | null
  connectorId!: string

  static get entity(): string {
    return 'devices_module_connector_property'
  }

  get title(): string {
    if (this.name !== null) {
      return this.name
    }

    return capitalize(this.identifier)
  }

  static fields(): Fields {
    return Object.assign(Property.fields(), {
      type: this.string(''),

      connector: this.belongsTo(Connector, 'id'),
      connectorBackward: this.hasOne(Connector, 'id', 'connectorId'),

      connectorId: this.string(''),
    })
  }

  static async get(connector: ConnectorInterface, id: string): Promise<boolean> {
    return await ConnectorProperty.dispatch('get', {
      connector,
      id,
    })
  }

  static async fetch(connector: ConnectorInterface): Promise<boolean> {
    return await ConnectorProperty.dispatch('fetch', {
      connector,
    })
  }

  static async add(connector: ConnectorInterface, data: ConnectorPropertyCreateInterface, id?: string | null, draft = true): Promise<Item<ConnectorProperty>> {
    return await ConnectorProperty.dispatch('add', {
      connector,
      id,
      draft,
      data,
    })
  }

  static async edit(property: ConnectorPropertyInterface, data: ConnectorPropertyUpdateInterface): Promise<Item<ConnectorProperty>> {
    return await ConnectorProperty.dispatch('edit', {
      property,
      data,
    })
  }

  static async save(property: ConnectorPropertyInterface): Promise<Item<ConnectorProperty>> {
    return await ConnectorProperty.dispatch('save', {
      property,
    })
  }

  static async remove(property: ConnectorPropertyInterface): Promise<boolean> {
    return await ConnectorProperty.dispatch('remove', {
      property,
    })
  }

  static transmitData(property: ConnectorPropertyInterface, value: string): Promise<boolean> {
    return ConnectorProperty.dispatch('transmitData', {
      property,
      value,
    })
  }

  static reset(): Promise<void> {
    return ConnectorProperty.dispatch('reset')
  }
}
