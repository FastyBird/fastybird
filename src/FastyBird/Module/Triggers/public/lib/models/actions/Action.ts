import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'

import Trigger from '@/lib/models/triggers/Trigger'
import { TriggerInterface } from '@/lib/models/triggers/types'
import {
  ActionEntityTypes,
  ActionInterface,
  CreateChannelPropertyActionInterface,
  CreateDevicePropertyActionInterface,
  UpdateChannelPropertyActionInterface,
  UpdateDevicePropertyActionInterface,
} from '@/lib/models/actions/types'

// ENTITY MODEL
// ============
export default class Action extends Model implements ActionInterface {
  id!: string
  type!: ActionEntityTypes
  draft!: boolean
  enabled!: boolean
  value!: string
  device!: string
  channel!: string
  property!: string
  isTriggered!: boolean | null
  relationshipNames!: string[]
  trigger!: TriggerInterface | null
  triggerBackward!: TriggerInterface | null
  triggerId!: string

  static get entity(): string {
    return 'triggers_module_action'
  }

  get isDeviceProperty(): boolean {
    return this.type === ActionEntityTypes.DEVICE_PROPERTY
  }

  get isChannelProperty(): boolean {
    return this.type === ActionEntityTypes.CHANNEL_PROPERTY
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      draft: this.boolean(false),

      enabled: this.boolean(true),

      // Device or channel property action
      value: this.string(''),
      device: this.string(''),
      channel: this.string(null).nullable(),
      property: this.string(''),

      isTriggered: this.attr(null),

      relationshipNames: this.attr([]),

      trigger: this.belongsTo(Trigger, 'id'),
      triggerBackward: this.hasOne(Trigger, 'id', 'triggerId'),

      triggerId: this.string(''),
    }
  }

  static async get(trigger: TriggerInterface, id: string): Promise<boolean> {
    return await Action.dispatch('get', {
      trigger,
      id,
    })
  }

  static async fetch(trigger: TriggerInterface): Promise<boolean> {
    return await Action.dispatch('fetch', {
      trigger,
    })
  }

  static async add(trigger: TriggerInterface, data: CreateDevicePropertyActionInterface | CreateChannelPropertyActionInterface, id?: string | null, draft = true): Promise<Item<Action>> {
    return await Action.dispatch('add', {
      trigger,
      id,
      draft,
      data,
    })
  }

  static async edit(action: ActionInterface, data: UpdateDevicePropertyActionInterface | UpdateChannelPropertyActionInterface): Promise<Item<Action>> {
    return await Action.dispatch('edit', {
      action,
      data,
    })
  }

  static async save(action: ActionInterface): Promise<Item<Action>> {
    return await Action.dispatch('save', {
      action,
    })
  }

  static async remove(action: ActionInterface): Promise<boolean> {
    return await Action.dispatch('remove', {
      action,
    })
  }

  static reset(): Promise<void> {
    return Action.dispatch('reset')
  }
}
