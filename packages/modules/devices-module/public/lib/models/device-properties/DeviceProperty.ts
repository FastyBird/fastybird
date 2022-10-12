import {
  Fields,
  Item,
} from '@vuex-orm/core'

import capitalize from 'lodash/capitalize'

import Device from '@/lib/models/devices/Device'
import { DeviceInterface } from '@/lib/models/devices/types'
import Property from '@/lib/models/properties/Property'
import {
  DevicePropertyCreateInterface,
  DevicePropertyInterface,
  DevicePropertyUpdateInterface,
} from '@/lib/models/device-properties/types'

// ENTITY MODEL
// ============
export default class DeviceProperty extends Property implements DevicePropertyInterface {
  device!: DeviceInterface | null
  deviceBackward!: DeviceInterface | null
  deviceId!: string

  parent!: DevicePropertyInterface | null
  parentBackward!: DevicePropertyInterface | null
  parentId!: string | null

  children!: DevicePropertyInterface[]

  static get entity(): string {
    return 'devices_module_device_property'
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

      device: this.belongsTo(Device, 'id'),
      deviceBackward: this.hasOne(Device, 'id', 'deviceId'),
      deviceId: this.string(''),

      parent: this.belongsTo(DeviceProperty, 'id'),
      parentBackward: this.hasOne(DeviceProperty, 'id', 'parentId'),
      parentId: this.string(null).nullable(),

      children: this.hasMany(DeviceProperty, 'parentId'),
    })
  }

  static async get(device: DeviceInterface, id: string): Promise<boolean> {
    return await DeviceProperty.dispatch('get', {
      device,
      id,
    })
  }

  static async fetch(device: DeviceInterface): Promise<boolean> {
    return await DeviceProperty.dispatch('fetch', {
      device,
    })
  }

  static async add(device: DeviceInterface, data: DevicePropertyCreateInterface, id?: string | null, draft = true): Promise<Item<DeviceProperty>> {
    return await DeviceProperty.dispatch('add', {
      device,
      id,
      draft,
      data,
    })
  }

  static async edit(property: DevicePropertyInterface, data: DevicePropertyUpdateInterface): Promise<Item<DeviceProperty>> {
    return await DeviceProperty.dispatch('edit', {
      property,
      data,
    })
  }

  static async save(property: DevicePropertyInterface): Promise<Item<DeviceProperty>> {
    return await DeviceProperty.dispatch('save', {
      property,
    })
  }

  static async remove(property: DevicePropertyInterface): Promise<boolean> {
    return await DeviceProperty.dispatch('remove', {
      property,
    })
  }

  static transmitData(property: DevicePropertyInterface, value: string): Promise<boolean> {
    return DeviceProperty.dispatch('transmitData', {
      property,
      value,
    })
  }

  static reset(): Promise<void> {
    return DeviceProperty.dispatch('reset')
  }
}
