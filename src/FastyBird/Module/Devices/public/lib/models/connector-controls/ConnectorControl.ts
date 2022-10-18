import {
  Fields,
  Model,
} from '@vuex-orm/core'

import Connector from '@/lib/models/connectors/Connector'
import { ConnectorInterface } from '@/lib/models/connectors/types'
import { ConnectorControlInterface } from '@/lib/models/connector-controls/types'

// ENTITY MODEL
// ============
export default class ConnectorControl extends Model implements ConnectorControlInterface {
  id!: string
  type!: string
  name!: string
  connector!: ConnectorInterface | null
  connectorBackward!: ConnectorInterface | null
  connectorId!: string

  static get entity(): string {
    return 'devices_module_connector_control'
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      name: this.string(''),

      connector: this.belongsTo(Connector, 'id'),
      connectorBackward: this.hasOne(Connector, 'id', 'connectorId'),

      connectorId: this.string(''),
    }
  }

  static async get(connector: ConnectorInterface, id: string): Promise<boolean> {
    return await ConnectorControl.dispatch('get', {
      connector,
      id,
    })
  }

  static async fetch(connector: ConnectorInterface): Promise<boolean> {
    return await ConnectorControl.dispatch('fetch', {
      connector,
    })
  }

  static transmitCommand(control: ConnectorControlInterface, value?: string | number | boolean | null): Promise<boolean> {
    return ConnectorControl.dispatch('transmitCommand', {
      control,
      value,
    })
  }

  static reset(): Promise<void> {
    return ConnectorControl.dispatch('reset')
  }
}
