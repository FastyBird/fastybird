import { ModelPropertiesMapper } from 'jsona'
import {
  IModelPropertiesMapper,
  TJsonaModel,
  TJsonaRelationships,
} from 'jsona/lib/JsonaTypes'
import { format as dateFormat } from 'date-fns'

import Trigger from '@/lib/models/triggers/Trigger'
import { ConditionEntityTypes } from '@/lib/models/conditions/types'
import { RelationInterface } from '@/lib/types'

const RELATIONSHIP_NAMES_PROP = 'relationshipNames'

export class JsonApiModelPropertiesMapper extends ModelPropertiesMapper implements IModelPropertiesMapper {
  getAttributes(model: TJsonaModel): { [index: string]: any } {
    const exceptProps = ['id', '$id', 'type', 'draft', RELATIONSHIP_NAMES_PROP]

    exceptProps.push('triggerId')
    exceptProps.push('triggerBackward')

    if (
      model.type === ConditionEntityTypes.DATE ||
      model.type === ConditionEntityTypes.TIME
    ) {
      exceptProps.push('channel')
      exceptProps.push('device')
      exceptProps.push('property')
      exceptProps.push('operator')
      exceptProps.push('operand')
    }

    if (Array.isArray(model[RELATIONSHIP_NAMES_PROP])) {
      exceptProps.push(...model[RELATIONSHIP_NAMES_PROP])
    }

    const attributes: { [index: string]: any } = {}

    Object.keys(model)
      .forEach((attrName) => {
        if (!exceptProps.includes(attrName)) {
          const snakeName = attrName.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)

          let jsonAttributes = model[attrName]

          if (attrName === 'days') {
            jsonAttributes = Object.values(jsonAttributes)
          } else if (attrName === 'time' || attrName === 'date') {
            if (jsonAttributes !== null) {
              if (typeof jsonAttributes === 'string') {
                jsonAttributes = dateFormat(new Date(jsonAttributes), 'yyyy-MM-dd\'T\'HH:mm:ssXXXXX')
              } else {
                jsonAttributes = dateFormat(jsonAttributes, 'yyyy-MM-dd\'T\'HH:mm:ssXXXXX')
              }
            }
          } else if (typeof jsonAttributes === 'object' && jsonAttributes !== null) {
            jsonAttributes = {}

            Object.keys(model[attrName]).forEach((subAttrName) => {
              const snakeSubName = subAttrName.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`)

              Object.assign(jsonAttributes, { [snakeSubName]: model[attrName][subAttrName] })
            })
          }

          attributes[snakeName] = jsonAttributes
        }
      })

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

    if (Object.prototype.hasOwnProperty.call(model, 'triggerId')) {
      const trigger = Trigger.find(model.triggerId)

      if (trigger !== null) {
        relationships.trigger = {
          id: trigger.id,
          type: trigger.type,
        }
      }
    }

    return relationships
  }
}

export default JsonApiModelPropertiesMapper
