import { Fields, Model } from '@vuex-orm/core'
import { DataType, HardwareManufacturer, normalizeValue, PropertyType } from '@fastybird/metadata'

import {
  PropertyCommandResult,
  PropertyCommandState,
  PropertyInterface,
  SensorNameTypes,
} from '@/lib/models/properties/types'
import { DeviceInterface } from '@/lib/models/devices/types'
import { ANY_PROPERTY_ENTITY_REG_EXP, cleanInvalid } from '@/lib/helpers'

// ENTITY MODEL
// ============
export default class Property extends Model implements PropertyInterface {
  id!: string
  type!: string
  property!: { source: string, parent: string, type: string }

  draft!: boolean

  identifier!: string
  name!: string | null
  settable!: boolean
  queryable!: boolean
  dataType!: DataType
  unit!: string | null
  format!: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid!: string | number | null
  numberOfDecimals!: number | null

  value!: string | number | boolean | Date | null

  actualValue!: string | number | boolean | Date | null
  expectedValue!: string | number | boolean | Date | null
  pending!: boolean

  command!: PropertyCommandState | null
  lastResult!: PropertyCommandResult | null
  backup!: string | null

  // Relations
  relationshipNames!: string[]

  get deviceInstance(): DeviceInterface | null {
    return null
  }

  get formattedActualValue(): string | null {
    if (this.property.type !== PropertyType.DYNAMIC) {
      return null
    }

    const storeInstance = Property.store()
    const actualValue = normalizeValue(this.dataType, this.actualValue !== null ? String(this.actualValue) : null, this.format)

    if (
      this.deviceInstance !== null &&
      this.deviceInstance.hardwareManufacturer === HardwareManufacturer.ITEAD &&
      Object.prototype.hasOwnProperty.call(storeInstance, '$i18n')
    ) {
      switch (this.identifier) {
        case SensorNameTypes.AIR_QUALITY:
          if (actualValue as number > 7) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.unhealthy`).toString()
          } else if (actualValue as number > 4) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.moderate`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.good`).toString()

        case SensorNameTypes.LIGHT_LEVEL:
          if (actualValue as number > 8) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.dusky`).toString()
          } else if (actualValue as number > 4) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.normal`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.bright`).toString()

        case SensorNameTypes.NOISE_LEVEL:
          if (actualValue as number > 6) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.noisy`).toString()
          } else if (actualValue as number > 3) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.normal`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.quiet`).toString()
      }
    }

    return String(actualValue)
  }

  get formattedExpectedValue(): string | null {
    if (this.expectedValue === null) {
      return null
    }

    if (this.property.type !== PropertyType.DYNAMIC) {
      return null
    }

    const storeInstance = Property.store()
    const expectedValue = normalizeValue(this.dataType, this.expectedValue !== null ? String(this.expectedValue) : null, this.format)

    if (
      this.deviceInstance !== null &&
      this.deviceInstance.hardwareManufacturer === HardwareManufacturer.ITEAD &&
      Object.prototype.hasOwnProperty.call(storeInstance, '$i18n')
    ) {
      switch (this.identifier) {
        case SensorNameTypes.AIR_QUALITY:
          if (expectedValue as number > 7) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.unhealthy`).toString()
          } else if (expectedValue as number > 4) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.moderate`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.good`).toString()

        case SensorNameTypes.LIGHT_LEVEL:
          if (expectedValue as number > 8) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.dusky`).toString()
          } else if (expectedValue as number > 4) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.normal`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.bright`).toString()

        case SensorNameTypes.NOISE_LEVEL:
          if (expectedValue as number > 6) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.noisy`).toString()
          } else if (expectedValue as number > 3) {
            // @ts-ignore
            return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.normal`).toString()
          }

          // @ts-ignore
          return storeInstance.$i18n.t(`devicesModule.vendors.${this.deviceInstance.hardwareManufacturer}.properties.${this.identifier}.values.quiet`).toString()
      }
    }

    return String(expectedValue)
  }

  get icon(): string {
    switch (this.identifier) {
      case SensorNameTypes.TEMPERATURE:
        return 'thermometer-half'

      case SensorNameTypes.HUMIDITY:
        return 'tint'

      case SensorNameTypes.AIR_QUALITY:
        return 'fan'

      case SensorNameTypes.LIGHT_LEVEL:
        return 'sun'

      case SensorNameTypes.NOISE_LEVEL:
        return 'microphone-alt'

      case SensorNameTypes.POWER:
        return 'plug'

      case SensorNameTypes.CURRENT:
      case SensorNameTypes.VOLTAGE:
        return 'bolt'

      case SensorNameTypes.ENERGY:
        return 'calculator'
    }

    return 'chart-bar'
  }

  static fields(): Fields {
    return {
      id: this.string(''),
      property: this.attr({ source: 'N/A', parent: 'N/A', type: 'N/A' }),

      draft: this.boolean(false),

      identifier: this.string(''),
      name: this.string(null).nullable(),
      settable: this.boolean(false),
      queryable: this.boolean(false),
      dataType: this.string(null).nullable(),
      unit: this.string(null).nullable(),
      format: this.attr(null).nullable(),
      invalid: this.string(null).nullable(),
      numberOfDecimals: this.number(null).nullable(),

      value: this.attr(null).nullable(),

      actualValue: this.attr(null).nullable(),
      expectedValue: this.attr(null).nullable(),
      pending: this.boolean(false),

      command: this.string(null).nullable(),
      lastResult: this.string(null).nullable(),
      backup: this.string(null).nullable(),

      // Relations
      relationshipNames: this.attr([]),
    }
  }

  static beforeCreate(items: PropertyInterface[] | PropertyInterface): PropertyInterface[] | PropertyInterface {
    if (Array.isArray(items)) {
      return items.map((item: PropertyInterface) => {
        return Object.assign(item, clearPropertyAttributes(item))
      })
    } else {
      return Object.assign(items, clearPropertyAttributes(items))
    }
  }

  static beforeUpdate(items: PropertyInterface[] | PropertyInterface): PropertyInterface[] | PropertyInterface {
    if (Array.isArray(items)) {
      return items.map((item: PropertyInterface) => {
        return Object.assign(item, clearPropertyAttributes(item))
      })
    } else {
      return Object.assign(items, clearPropertyAttributes(items))
    }
  }
}

const clearPropertyAttributes = (item: {[key: string]: any}): {[key: string]: any} => {
  const typeRegex = new RegExp(ANY_PROPERTY_ENTITY_REG_EXP)

  const parsedTypes = typeRegex.exec(`${item.type}`)

  item.property = { source: 'N/A', parent: 'N/A', type: 'N/A' }

  if (
    parsedTypes !== null
    && 'groups' in parsedTypes
    && typeof parsedTypes.groups !== 'undefined'
    && 'source' in parsedTypes.groups
    && 'parent' in parsedTypes.groups
    && 'type' in parsedTypes.groups
  ) {
    item.property = {
      source: parsedTypes.groups.source,
      parent: parsedTypes.groups.parent,
      type: parsedTypes.groups.type,
    }
  }

  if (item.format !== null && typeof item.format === 'object') {
    item.format = Object.values(item.format)
  }
  item.invalid = cleanInvalid(item.dataType, item.invalid)

  if (item.property.type === PropertyType.DYNAMIC) {
    item.actualValue = normalizeValue(item.dataType, String(item.actualValue), item.format)
    item.expectedValue = normalizeValue(item.dataType, String(item.expectedValue), item.format)
  }

  if (item.property.type === PropertyType.STATIC) {
    item.value = normalizeValue(item.dataType, String(item.value), item.format)
  }

  return item
}
