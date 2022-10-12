import { ButtonPayload, DataType, SwitchPayload } from '@fastybird/metadata'
import { parse } from 'date-fns'

export const CONNECTOR_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/connector/(?<type>[a-z0-9.]+)$'
export const CONNECTOR_PROPERTY_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/connector/(?<type>[a-z0-9-]+)$'
export const CONNECTOR_CONTROL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const DEVICE_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/device/(?<type>[a-z0-9-]+)$'
export const DEVICE_PROPERTY_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/device/(?<type>[a-z0-9-]+)$'
export const DEVICE_CONTROL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const CHANNEL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/channel$'
export const CHANNEL_PROPERTY_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/channel/(?<type>[a-z0-9-]+)$'
export const CHANNEL_CONTROL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const ANY_PROPERTY_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/(?<parent>[a-z0-9-]+)/(?<type>[a-z0-9-]+)$'

export const cleanInvalid = (
  dataType: string | null,
  rawInvalid: string | number | null,
): string | number | null => {
  if (rawInvalid === null) {
    return null
  }

  if (dataType !== null) {
    switch (dataType) {
      case DataType.CHAR:
      case DataType.UCHAR:
      case DataType.SHORT:
      case DataType.USHORT:
      case DataType.INT:
      case DataType.UINT: {
        if (!isNaN(Number(rawInvalid))) {
          return parseInt(String(rawInvalid), 10)
        }

        break
      }

      case DataType.FLOAT: {
        if (!isNaN(Number(rawInvalid))) {
          return parseFloat(String(rawInvalid))
        }

        break
      }

      default: {
        return String(rawInvalid)
      }
    }
  }

  return null
}


export const normalizeValue = (
  dataType: DataType,
  value: string | number | boolean | Date | null,
  format?: string[] | ((string | null)[])[] | (number | null)[] | null,
): string | number | boolean | Date | null => {
  if (value === null) {
    return null
  }

  switch (dataType) {
    case DataType.BOOLEAN:
      return ['true', 't', 'yes', 'y', '1', 'on'].includes(String(value).toLowerCase())

    case DataType.FLOAT: {
      const floatValue = parseFloat(String(value))

      if (Array.isArray(format) && format.length === 2) {
        if (format[0] !== null && format[0] > floatValue) {
          return null
        }

        if (format[1] !== null && format[1] < floatValue) {
          return null
        }
      }

      return floatValue
    }

    case DataType.CHAR:
    case DataType.UCHAR:
    case DataType.SHORT:
    case DataType.USHORT:
    case DataType.INT:
    case DataType.UINT: {
      const intValue = parseInt(String(value), 10)

      if (Array.isArray(format) && format.length === 2) {
        if (format[0] !== null && format[0] > intValue) {
          return null
        }

        if (format[1] !== null && format[1] < intValue) {
          return null
        }
      }

      return intValue
    }

    case DataType.STRING:
      return value

    case DataType.ENUM:
      if (Array.isArray(format)) {
        const filtered = (format as (string | (string | null)[])[])
          .filter((item): boolean => {
            if (Array.isArray(item)) {
              if (item.length !== 3) {
                return false
              }

              return (
                String(value).toLowerCase() === item[0]
                || String(value).toLowerCase() === item[1]
                || String(value).toLowerCase() === item[2]
              )
            }

            return String(value).toLowerCase() === item
          })

        return filtered.length === 1 ? (Array.isArray(filtered[0]) ? filtered[0][0] : filtered[0]) : null
      }

      return null

    case DataType.DATE:
      return parse(String(value), 'yyyy-MM-DD', new Date())

    case DataType.TIME:
      return parse(String(value), 'HH:mm:ssxxx', new Date())

    case DataType.DATETIME:
      return parse(String(value), 'yyyy-MM-DD\'T\'HH:mm:ssxxx', new Date())

    case DataType.COLOR:
      break

    case DataType.BUTTON:
      if (Object.values<string>(ButtonPayload).includes(String(value).toLowerCase())) {
        return String(value).toLowerCase()
      }

      return null

    case DataType.SWITCH:
      if (Object.values<string>(SwitchPayload).includes(String(value).toLowerCase())) {
        return String(value).toLowerCase()
      }

      return null
  }

  return value
}
