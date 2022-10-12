import {
  Fields,
  Item,
} from '@vuex-orm/core'

import capitalize from 'lodash/capitalize'

import Channel from '@/lib/models/channels/Channel'
import { ChannelInterface } from '@/lib/models/channels/types'
import {
  ChannelPropertyCreateInterface,
  ChannelPropertyInterface,
  ChannelPropertyUpdateInterface,
} from '@/lib/models/channel-properties/types'
import Property from '@/lib/models/properties/Property'

// ENTITY MODEL
// ============
export default class ChannelProperty extends Property implements ChannelPropertyInterface {
  channel!: ChannelInterface | null
  channelBackward!: ChannelInterface | null
  channelId!: string

  parent!: ChannelPropertyInterface | null
  parentBackward!: ChannelPropertyInterface | null
  parentId!: string | null

  children!: ChannelPropertyInterface[]

  static get entity(): string {
    return 'devices_module_channel_property'
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

      channel: this.belongsTo(Channel, 'id'),
      channelBackward: this.hasOne(Channel, 'id', 'channelId'),
      channelId: this.string(''),

      parent: this.belongsTo(ChannelProperty, 'id'),
      parentBackward: this.hasOne(ChannelProperty, 'id', 'parentId'),
      parentId: this.string(null).nullable(),

      children: this.hasMany(ChannelProperty, 'parentId'),
    })
  }

  static async get(channel: ChannelInterface, id: string): Promise<boolean> {
    return await ChannelProperty.dispatch('get', {
      channel,
      id,
    })
  }

  static async fetch(channel: ChannelInterface): Promise<boolean> {
    return await ChannelProperty.dispatch('fetch', {
      channel,
    })
  }

  static async add(channel: ChannelInterface, data: ChannelPropertyCreateInterface, id?: string | null, draft = true): Promise<Item<ChannelProperty>> {
    return await ChannelProperty.dispatch('add', {
      channel,
      id,
      draft,
      data,
    })
  }

  static async edit(property: ChannelPropertyInterface, data: ChannelPropertyUpdateInterface): Promise<Item<ChannelProperty>> {
    return await ChannelProperty.dispatch('edit', {
      property,
      data,
    })
  }

  static async save(property: ChannelPropertyInterface): Promise<Item<ChannelProperty>> {
    return await ChannelProperty.dispatch('save', {
      property,
    })
  }

  static async remove(property: ChannelPropertyInterface): Promise<boolean> {
    return await ChannelProperty.dispatch('remove', {
      property,
    })
  }

  static transmitData(property: ChannelPropertyInterface, value: string): Promise<boolean> {
    return ChannelProperty.dispatch('transmitData', {
      property,
      value,
    })
  }

  static reset(): Promise<void> {
    return ChannelProperty.dispatch('reset')
  }
}
