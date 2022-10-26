import { ModelPropertiesMapper } from 'jsona'
import {
  IModelPropertiesMapper,
  TJsonaModel,
  TJsonaRelationships,
} from 'jsona/lib/JsonaTypes'

import Account from '@/lib/models/accounts/Account'
import { AccountEntityTypes } from '@/lib/models/accounts/types'
import { IdentityEntityTypes } from '@/lib/models/identities/types'
import { EmailEntityTypes } from '@/lib/models/emails/types'
import { RelationInterface } from '@/lib/types'

const RELATIONSHIP_NAMES_PROP = 'relationshipNames'

class JsonApiModelPropertiesMapper extends ModelPropertiesMapper implements IModelPropertiesMapper {
  getAttributes(model: TJsonaModel): { [index: string]: any } {
    const exceptProps = ['id', '$id', 'type', 'draft', RELATIONSHIP_NAMES_PROP]

    if (model.type === AccountEntityTypes.USER) {
      exceptProps.push('firstName')
      exceptProps.push('lastName')
      exceptProps.push('middleName')
      exceptProps.push('timezone')
      exceptProps.push('dateFormat')
      exceptProps.push('timeFormat')
    } else if (model.type === IdentityEntityTypes.USER) {
      exceptProps.push('account')
      exceptProps.push('accountId')
    } else if (model.type === EmailEntityTypes.EMAIL) {
      exceptProps.push('account')
      exceptProps.push('accountId')
    }

    if (Array.isArray(model[RELATIONSHIP_NAMES_PROP])) {
      exceptProps.push(...model[RELATIONSHIP_NAMES_PROP])
    }

    const attributes: { [index: string]: any } = {}

    Object.keys(model)
      .forEach((attrName) => {
        if (!exceptProps.includes(attrName)) {
          const snakeName = attrName.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)

          attributes[snakeName] = model[attrName]
        }
      })

    if (model.type === AccountEntityTypes.USER) {
      attributes.details = {}
      attributes.datetime = {}

      if (Object.prototype.hasOwnProperty.call(model, 'firstName')) {
        attributes.details.first_name = model.firstName
      }

      if (Object.prototype.hasOwnProperty.call(model, 'lastName')) {
        attributes.details.last_name = model.lastName
      }

      if (Object.prototype.hasOwnProperty.call(model, 'middleName')) {
        attributes.details.middle_name = model.middleName
      }

      if (Object.prototype.hasOwnProperty.call(model, 'timezone')) {
        attributes.datetime.timezone = model.timezone
      }

      if (Object.prototype.hasOwnProperty.call(model, 'dateFormat')) {
        attributes.datetime.date_format = model.dateFormat
      }

      if (Object.prototype.hasOwnProperty.call(model, 'timeFormat')) {
        attributes.datetime.time_format = model.timeFormat
      }
    }

    return attributes
  }

  getRelationships(model: TJsonaModel): TJsonaRelationships {
    if (
      !Object.prototype.hasOwnProperty.call(model, RELATIONSHIP_NAMES_PROP) ||
      !Array.isArray(model[RELATIONSHIP_NAMES_PROP])
    ) {
      return {}
    }

    const relationshipNames = model[RELATIONSHIP_NAMES_PROP]

    const relationships: { [index: string]: RelationInterface | RelationInterface[] } = {}

    relationshipNames
      .forEach((relationName: string) => {
        const snakeName = relationName.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)

        if (model[relationName] !== undefined) {
          if (Array.isArray(model[relationName])) {
            relationships[snakeName] = model[relationName]
              .map((item: TJsonaModel) => {
                return {
                  id: item.id,
                  type: item.type,
                }
              })
          } else if (typeof model[relationName] === 'object' && model[relationName] !== null) {
            relationships[snakeName] = {
              id: model[relationName].id,
              type: model[relationName].type,
            }
          }
        }
      })

    if (Object.prototype.hasOwnProperty.call(model, 'accountId')) {
      const account = Account.find(model.accountId)

      if (account !== null) {
        relationships.account = {
          id: account.id,
          type: account.type,
        }
      }
    }

    return relationships
  }
}

export default JsonApiModelPropertiesMapper
