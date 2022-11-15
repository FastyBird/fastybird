import { IJsonPropertiesMapper, TAnyKeyValueObject, TJsonaModel, TJsonaRelationships } from 'jsona/lib/JsonaTypes';
import { JsonPropertiesMapper, RELATIONSHIP_NAMES_PROP } from 'jsona/lib/simplePropertyMappers';

const CASE_REG_EXP = '_([a-z0-9])';

class JsonApiJsonPropertiesMapper extends JsonPropertiesMapper implements IJsonPropertiesMapper {
	createModel(type: string): TJsonaModel {
		return { type };
	}

	setAttributes(model: TJsonaModel, attributes: TAnyKeyValueObject): void {
		Object.assign(model, this.camelizeAttributes(attributes));
	}

	setRelationships(model: TJsonaModel, relationships: TJsonaRelationships): void {
		// Call super.setRelationships first, just for not to copy&paste setRelationships logic
		super.setRelationships(model, relationships);

		const caseRegex = new RegExp(CASE_REG_EXP);

		model[RELATIONSHIP_NAMES_PROP].forEach((relationName: string, index: number): void => {
			const camelName = relationName.replace(caseRegex, (g) => g[1].toUpperCase());

			if (camelName !== relationName) {
				Object.assign(model, { [camelName]: model[relationName] });

				delete model[relationName];

				model[RELATIONSHIP_NAMES_PROP][index] = camelName;
			}
		});

		Object.assign(model, {
			[RELATIONSHIP_NAMES_PROP]: (model[RELATIONSHIP_NAMES_PROP] as string[]).filter((value, i, self) => self.indexOf(value) === i),
		});
	}

	private camelizeAttributes(attributes: TAnyKeyValueObject): TAnyKeyValueObject {
		const caseRegex = new RegExp(CASE_REG_EXP);

		const data: TAnyKeyValueObject = {};

		Object.keys(attributes).forEach((attrName): void => {
			const camelName = attrName.replace(caseRegex, (g) => g[1].toUpperCase());

			if (typeof attributes[attrName] === 'object' && attributes[attrName] !== null && !Array.isArray(attributes[attrName])) {
				Object.assign(data, { [camelName]: this.camelizeAttributes(attributes[attrName]) });
			} else {
				Object.assign(data, { [camelName]: attributes[attrName] });
			}
		});

		return data;
	}
}

export default JsonApiJsonPropertiesMapper;
