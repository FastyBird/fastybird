import {
  Fields,
  Item,
  Model,
} from '@vuex-orm/core'
import { ConditionOperator } from '@fastybird/metadata'

import Trigger from '@/lib/models/triggers/Trigger'
import { TriggerInterface } from '@/lib/models/triggers/types'
import {
  ConditionEntityTypes,
  ConditionInterface,
  CreateChannelPropertyConditionInterface,
  CreateDateConditionInterface,
  CreateDevicePropertyConditionInterface,
  CreateTimeConditionInterface,
  UpdateChannelPropertyConditionInterface,
  UpdateDateConditionInterface,
  UpdateDevicePropertyConditionInterface,
  UpdateTimeConditionInterface,
} from '@/lib/models/conditions/types'

// ENTITY MODEL
// ============
export default class Condition extends Model implements ConditionInterface {
  id!: string
  type!: ConditionEntityTypes
  draft!: boolean
  enabled!: boolean
  operator!: ConditionOperator
  operand!: string
  device!: string
  channel!: string
  property!: string
  time!: string
  days!: number[]
  date!: string
  isFulfilled!: boolean | null
  relationshipNames!: string[]
  trigger!: TriggerInterface | null
  triggerBackward!: TriggerInterface | null
  triggerId!: string

  static get entity(): string {
    return 'triggers_module_condition'
  }

  get isDeviceProperty(): boolean {
    return this.type === ConditionEntityTypes.DEVICE_PROPERTY
  }

  get isChannelProperty(): boolean {
    return this.type === ConditionEntityTypes.CHANNEL_PROPERTY
  }

  get isTime(): boolean {
    return this.type === ConditionEntityTypes.TIME
  }

  get isDate(): boolean {
    return this.type === ConditionEntityTypes.DATE
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      type: this.string(''),

      draft: this.boolean(false),

      enabled: this.boolean(true),

      // Device or channel property condition
      operator: this.string(null).nullable(),
      operand: this.attr(null).nullable(),
      device: this.string(null).nullable(),
      channel: this.string(null).nullable(),
      property: this.string(null).nullable(),

      // Time condition
      time: this.attr(null).nullable(),
      days: this.attr([]),

      // Date condition
      date: this.attr(null),

      isFulfilled: this.attr(null),

      relationshipNames: this.attr([]),

      trigger: this.belongsTo(Trigger, 'id'),
      triggerBackward: this.hasOne(Trigger, 'id', 'triggerId'),

      triggerId: this.string(''),
    }
  }

  static async get(trigger: TriggerInterface, id: string): Promise<boolean> {
    return await Condition.dispatch('get', {
      trigger,
      id,
    })
  }

  static async fetch(trigger: TriggerInterface): Promise<boolean> {
    return await Condition.dispatch('fetch', {
      trigger,
    })
  }

  static async add(trigger: TriggerInterface, data: CreateTimeConditionInterface | CreateDateConditionInterface | CreateChannelPropertyConditionInterface | CreateDevicePropertyConditionInterface, id?: string | null, draft = true): Promise<Item<Condition>> {
    return await Condition.dispatch('add', {
      trigger,
      id,
      draft,
      data,
    })
  }

  static async edit(condition: ConditionInterface, data: UpdateTimeConditionInterface | UpdateDateConditionInterface | UpdateChannelPropertyConditionInterface | UpdateDevicePropertyConditionInterface): Promise<Item<Condition>> {
    return await Condition.dispatch('edit', {
      condition,
      data,
    })
  }

  static async save(condition: ConditionInterface): Promise<Item<Condition>> {
    return await Condition.dispatch('save', {
      condition,
    })
  }

  static async remove(condition: ConditionInterface): Promise<boolean> {
    return await Condition.dispatch('remove', {
      condition,
    })
  }

  static reset(): Promise<void> {
    return Condition.dispatch('reset')
  }
}
