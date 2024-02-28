<template>
	<fb-ui-content :mb="FbSizeTypes.LARGE">
		<fb-form-input
			v-model="identifierField"
			:error="identifierError"
			:label="t('fields.identifier.title')"
			:placeholder="t('fields.identifier.placeholder')"
			:required="true"
			:readonly="!props.property.draft"
			name="identifier"
		/>
	</fb-ui-content>

	<fb-ui-content :mb="FbSizeTypes.LARGE">
		<fb-form-input
			v-model="nameField"
			:error="nameError"
			:label="t('fields.name.title')"
			:placeholder="t('fields.name.placeholder')"
			name="name"
		/>
	</fb-ui-content>

	<template v-if="'parent' in props.property && props.property.parent === null">
		<fb-ui-content :mb="FbSizeTypes.LARGE">
			<fb-form-select
				v-model="dataTypeField"
				:error="dataTypeError"
				:label="t('fields.dataType.title')"
				:items="dataTypeOptions"
				name="dataType"
			/>
		</fb-ui-content>

		<fb-ui-content :mb="FbSizeTypes.LARGE">
			<fb-form-input
				v-model="unitField"
				:error="unitError"
				:label="t('fields.unit.title')"
				:placeholder="t('fields.unit.placeholder')"
				name="unit"
			/>
		</fb-ui-content>

		<template v-if="props.property.type.type === PropertyType.DYNAMIC">
			<fb-ui-content :mb="FbSizeTypes.LARGE">
				<fb-form-checkbox
					v-model="settableField"
					:option="true"
					name="settable"
				>
					{{ t('fields.settable.title') }}
				</fb-form-checkbox>
			</fb-ui-content>

			<fb-ui-content :mb="FbSizeTypes.LARGE">
				<fb-form-checkbox
					v-model="queryableField"
					:option="true"
					name="queryable"
				>
					{{ t('fields.queryable.title') }}
				</fb-form-checkbox>
			</fb-ui-content>
		</template>

		<fb-ui-content :mb="FbSizeTypes.LARGE">
			<fb-form-input
				v-model="invalidField"
				:error="invalidError"
				:label="t('fields.invalid.title')"
				:placeholder="t('fields.invalid.placeholder')"
				name="invalid"
			/>
		</fb-ui-content>

		<fb-ui-content :mb="FbSizeTypes.LARGE">
			<fb-form-input
				v-model="scaleField"
				:error="scaleError"
				:label="t('fields.scale.title')"
				:placeholder="t('fields.scale.placeholder')"
				name="scale"
			/>
		</fb-ui-content>

		<fb-ui-content :mb="FbSizeTypes.LARGE">
			<fb-form-input
				v-model="formatField"
				:error="formatError"
				:label="t('fields.format.title')"
				:placeholder="t('fields.format.placeholder')"
				name="format"
			/>
		</fb-ui-content>
	</template>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { object as yObject, string as yString, number as yNumber, boolean as yBoolean, mixed as yMixed } from 'yup';
import get from 'lodash/get';

import { FbFormInput, FbUiContent, FbFormSelect, FbFormCheckbox, FbSizeTypes, FbFormResultTypes, IFbFormSelectItem } from '@fastybird/web-ui-library';
import { DataType, PropertyType } from '@fastybird/metadata-library';

import { useFlashMessage } from '../../composables';
import { useChannelProperties, useConnectorProperties, useDeviceProperties } from '../../models';
import { IPropertySettingsPropertyFormForm, IPropertySettingsPropertyFormProps } from './property-settings-property-form.types';

const props = withDefaults(defineProps<IPropertySettingsPropertyFormProps>(), {
	connector: undefined,
	device: undefined,
	channel: undefined,
	remoteFormSubmit: false,
	remoteFormReset: false,
	remoteFormResult: FbFormResultTypes.NONE,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FbFormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'added'): void;
}>();

const { t } = useI18n();

const flashMessage = useFlashMessage();

const channelPropertiesStore = useChannelProperties();
const connectorPropertiesStore = useConnectorProperties();
const devicePropertiesStore = useDeviceProperties();

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined);
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined);
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined);

const { validate } = useForm<IPropertySettingsPropertyFormForm>({
	validationSchema: yObject({
		identifier: yString().required(t('fields.identifier.validation.required')),
		name: yString().nullable().default(null),
		settable: yBoolean().default(false),
		queryable: yBoolean().default(false),
		dataType: yMixed().oneOf(Object.values(DataType)).default(DataType.UNKNOWN),
		unit: yString().nullable().default(null),
		invalid: yString().nullable().default(null),
		scale: yNumber().nullable().default(null),
		format: yString().nullable().default(null),
	}),
	initialValues: {
		identifier: props.property.identifier,
		name: props.property.name,
		settable: props.property.settable,
		queryable: props.property.queryable,
		dataType: props.property.dataType,
		unit: props.property.unit,
		invalid: props.property.invalid as string,
		scale: props.property.scale,
		format: props.property.format ? JSON.stringify(props.property.format) : null,
	},
});

const { value: identifierField, errorMessage: identifierError, setValue: setIdentifier } = useField<string>('identifier');
const { value: nameField, errorMessage: nameError, setValue: setName } = useField<string | undefined>('name');
const { value: settableField, setValue: setSettable } = useField<boolean>('settable');
const { value: queryableField, setValue: setQueryable } = useField<boolean>('queryable');
const { value: dataTypeField, errorMessage: dataTypeError, setValue: setDataType } = useField<DataType>('dataType');
const { value: unitField, errorMessage: unitError, setValue: setUnit } = useField<string | undefined>('unit');
const { value: invalidField, errorMessage: invalidError, setValue: setInvalid } = useField<string | undefined>('invalid');
const { value: scaleField, errorMessage: scaleError, setValue: setScale } = useField<number | undefined>('scale');
const { value: formatField, errorMessage: formatError, setValue: setFormat } = useField<string | undefined>('format');

const dataTypeOptions: IFbFormSelectItem[] = [
	{
		name: t('fields.dataType.values.unknown'),
		value: DataType.UNKNOWN,
	},
	{
		name: t('fields.dataType.values.char'),
		value: DataType.CHAR,
	},
	{
		name: t('fields.dataType.values.uchar'),
		value: DataType.UCHAR,
	},
	{
		name: t('fields.dataType.values.short'),
		value: DataType.SHORT,
	},
	{
		name: t('fields.dataType.values.ushort'),
		value: DataType.USHORT,
	},
	{
		name: t('fields.dataType.values.int'),
		value: DataType.INT,
	},
	{
		name: t('fields.dataType.values.uint'),
		value: DataType.UINT,
	},
	{
		name: t('fields.dataType.values.float'),
		value: DataType.FLOAT,
	},
	{
		name: t('fields.dataType.values.bool'),
		value: DataType.BOOLEAN,
	},
	{
		name: t('fields.dataType.values.string'),
		value: DataType.STRING,
	},
	{
		name: t('fields.dataType.values.enum'),
		value: DataType.ENUM,
	},
	{
		name: t('fields.dataType.values.date'),
		value: DataType.DATE,
	},
	{
		name: t('fields.dataType.values.time'),
		value: DataType.TIME,
	},
	{
		name: t('fields.dataType.values.datetime'),
		value: DataType.DATETIME,
	},
	{
		name: t('fields.dataType.values.color'),
		value: DataType.COLOR,
	},
	{
		name: t('fields.dataType.values.button'),
		value: DataType.BUTTON,
	},
	{
		name: t('fields.dataType.values.switch'),
		value: DataType.SWITCH,
	},
];

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FbFormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				const data = {
					identifier: identifierField.value,
					name: nameField.value,
					settable: settableField.value,
					queryable: queryableField.value,
					dataType: dataTypeField.value,
					unit: unitField.value,
					invalid: invalidField.value,
					scale: scaleField.value,
					format: formatField.value ? JSON.parse(formatField.value) : null,
				};

				try {
					if (props.property.draft) {
						if (isChannelProperty.value) {
							await channelPropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.channel?.draft) {
								emit('update:remoteFormResult', FbFormResultTypes.WORKING);

								await channelPropertiesStore.save({ id: props.property.id });
							}
						} else if (isDeviceProperty.value) {
							await devicePropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.device?.draft) {
								emit('update:remoteFormResult', FbFormResultTypes.WORKING);

								await devicePropertiesStore.save({ id: props.property.id });
							}
						} else if (isConnectorProperty.value) {
							await connectorPropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.connector?.draft) {
								emit('update:remoteFormResult', FbFormResultTypes.WORKING);

								await connectorPropertiesStore.save({ id: props.property.id });
							}
						}
					} else {
						emit('update:remoteFormResult', FbFormResultTypes.WORKING);

						if (isChannelProperty.value) {
							await channelPropertiesStore.edit({
								id: props.property.id,
								data,
							});
						} else if (isDeviceProperty.value) {
							await devicePropertiesStore.edit({
								id: props.property.id,
								data,
							});
						} else if (isConnectorProperty.value) {
							await connectorPropertiesStore.edit({
								id: props.property.id,
								data,
							});
						}
					}

					emit('update:remoteFormResult', FbFormResultTypes.OK);

					if (isChannelProperty.value) {
						if (!props.channel?.draft) {
							timer = window.setTimeout(clearResult, 2000);
						} else {
							clearResult();
							emit('added');
						}
					} else if (isDeviceProperty.value) {
						if (!props.device?.draft) {
							timer = window.setTimeout(clearResult, 2000);
						} else {
							clearResult();
							emit('added');
						}
					} else if (isConnectorProperty.value) {
						if (!props.connector?.draft) {
							timer = window.setTimeout(clearResult, 2000);
						} else {
							clearResult();
							emit('added');
						}
					}
				} catch (e: any) {
					const errorMessage = props.property.draft
						? t('messages.propertyNotCreated', { property: nameField.value })
						: t('messages.propertyNotEdited', { property: nameField.value });

					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FbFormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);
				}
			}
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			setIdentifier(props.property.identifier);
			setName(props.property.name ?? undefined);
			setSettable(props.property.settable);
			setQueryable(props.property.queryable);
			setDataType(props.property.dataType);
			setUnit(props.property.unit ?? undefined);
			setInvalid(props.property.invalid as string);
			setScale(props.property.scale ?? undefined);
			setFormat(props.property.format ? JSON.stringify(props.property.format) : undefined);
		}
	}
);
</script>

<i18n>
{
  "en": {
    "fields": {
      "identifier": {
        "title": "Parameter identifier",
        "placeholder": "Enter parameter identifier"
      },
      "name": {
        "title": "Parameter name",
        "placeholder": "Enter parameter name"
      },
      "dataType": {
        "title": "Value data type",
        "values": {
          "unknown": "Unknown",
          "char": "Char",
          "uchar": "Unsigned char",
          "short": "Short integer",
          "ushort": "Unsigned short integer",
          "int": "Integer",
          "uint": "Unsigned integer",
          "float": "Float",
          "bool": "Boolean",
          "string": "Text",
          "enum": "Enum",
          "date": "Date",
          "time": "Time",
          "datetime": "Date & Time",
          "color": "Color",
          "button": "Button",
          "switch": "Switch"
        }
      },
      "unit": {
        "title": "Value unit",
        "placeholder": "Enter parameter unit"
      },
      "settable": {
        "title": "Is parameter settable?"
      },
      "queryable": {
        "title": "Is parameter queryable?"
      },
      "invalid": {
        "title": "Invalid value",
        "placeholder": "Enter parameter invalid value"
      },
      "scale": {
        "title": "Number of decimal places",
        "placeholder": "Enter parameter number of decimal places"
      },
      "format": {
        "title": "Value format",
        "placeholder": "Enter parameter value format for value transformation"
      }
    },
    "messages": {
      "propertyNotCreated": "Property {property} couldn't be created",
      "propertyNotEdited": "Property {property} couldn't be updated"
    }
  }
}
</i18n>
