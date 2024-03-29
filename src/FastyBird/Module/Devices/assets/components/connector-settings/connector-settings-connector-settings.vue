<template>
	<div class="fb-devices-module-connector-settings-connector-settings__container">
		<h3 class="fb-devices-module-connector-settings-connector-settings__heading">
			{{ t('headings.aboutConnector') }}
		</h3>

		<fb-ui-content
			:ph="FbSizeTypes.SMALL"
			:pv="FbSizeTypes.SMALL"
		>
			<connector-settings-connector-rename
				v-model="aboutField"
				:errors="{ identifier: identifierError, name: nameError, comment: commentError }"
			/>

			<property-settings-variable-properties-edit
				v-model="variablePropertiesFields"
				:properties="variableProperties"
			/>
		</fb-ui-content>

		<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

		<fb-ui-items-container>
			<template #heading>
				{{ t('headings.devices') }}
			</template>

			<template #buttons>
				<fb-ui-content :mr="FbSizeTypes.SMALL">
					<fb-ui-button
						v-if="devicesData.length > 0"
						:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
						:size="FbSizeTypes.EXTRA_SMALL"
						@click="emit('addDevice')"
					>
						<template #icon>
							<font-awesome-icon icon="plus" />
						</template>
						{{ t('buttons.addDevice.title') }}
					</fb-ui-button>
				</fb-ui-content>
			</template>

			<div
				v-if="devicesData.length === 0"
				class="fb-devices-module-connector-settings-connector-settings__add-item-row"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.LARGE"
					block
					@click="emit('addDevice')"
				>
					<template #icon>
						<font-awesome-icon icon="plus-circle" />
					</template>
					<span>{{ t('buttons.addDevice.title') }}</span>
				</fb-ui-button>
			</div>

			<connector-settings-connector-device
				v-for="deviceData in devicesData"
				:key="deviceData.device.id"
				:connector="props.connectorData.connector"
				:device-data="deviceData"
				@edit="emit('editDevice', $event)"
			/>
		</fb-ui-items-container>

		<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

		<fb-ui-items-container>
			<template #heading>
				{{ t('headings.variableProperties') }}
			</template>

			<template #buttons>
				<fb-ui-content :mr="FbSizeTypes.SMALL">
					<fb-ui-button
						v-if="variableProperties.length > 0"
						:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
						:size="FbSizeTypes.EXTRA_SMALL"
						@click="onOpenView(ConnectorSettingsConnectorSettingsViewTypes.ADD_STATIC_PARAMETER)"
					>
						<template #icon>
							<font-awesome-icon icon="plus" />
						</template>
						{{ t('buttons.addProperty.title') }}
					</fb-ui-button>
				</fb-ui-content>
			</template>

			<div
				v-if="variableProperties.length === 0"
				class="fb-devices-module-connector-settings-connector-settings__add-item-row"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.LARGE"
					block
					@click="onOpenView(ConnectorSettingsConnectorSettingsViewTypes.ADD_STATIC_PARAMETER)"
				>
					<template #icon>
						<font-awesome-icon icon="plus-circle" />
					</template>
					<span>{{ t('buttons.addProperty.title') }}</span>
				</fb-ui-button>
			</div>

			<property-settings-property
				v-for="property in variableProperties"
				:key="property.identifier"
				:connector="props.connectorData.connector"
				:property="property"
			/>
		</fb-ui-items-container>

		<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

		<fb-ui-items-container>
			<template #heading>
				{{ t('headings.dynamicProperties') }}
			</template>

			<template #buttons>
				<fb-ui-content :mr="FbSizeTypes.SMALL">
					<fb-ui-button
						v-if="dynamicProperties.length > 0"
						:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
						:size="FbSizeTypes.EXTRA_SMALL"
						@click="onOpenView(ConnectorSettingsConnectorSettingsViewTypes.ADD_DYNAMIC_PARAMETER)"
					>
						<template #icon>
							<font-awesome-icon icon="plus" />
						</template>
						{{ t('buttons.addProperty.title') }}
					</fb-ui-button>
				</fb-ui-content>
			</template>

			<div
				v-if="dynamicProperties.length === 0"
				class="fb-devices-module-connector-settings-connector-settings__add-item-row"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.LARGE"
					block
					@click="onOpenView(ConnectorSettingsConnectorSettingsViewTypes.ADD_DYNAMIC_PARAMETER)"
				>
					<template #icon>
						<font-awesome-icon icon="plus-circle" />
					</template>
					<span>{{ t('buttons.addProperty.title') }}</span>
				</fb-ui-button>
			</div>

			<property-settings-property
				v-for="property in dynamicProperties"
				:key="property.identifier"
				:connector="props.connectorData.connector"
				:property="property"
			/>
		</fb-ui-items-container>
	</div>

	<property-settings-property-add-modal
		v-if="
			(activeView === ConnectorSettingsConnectorSettingsViewTypes.ADD_STATIC_PARAMETER ||
				activeView === ConnectorSettingsConnectorSettingsViewTypes.ADD_DYNAMIC_PARAMETER) &&
			newProperty !== null
		"
		:property="newProperty"
		:connector="props.connectorData.connector"
		@close="onCloseView"
	/>

	<div
		v-if="[FbFormResultTypes.WORKING, FbFormResultTypes.OK, FbFormResultTypes.ERROR].includes(props.remoteFormResult)"
		class="fb-devices-module-connector-settings-connector-settings__result"
	>
		<fb-ui-loading-box
			v-if="props.remoteFormResult === FbFormResultTypes.WORKING"
			:size="FbSizeTypes.LARGE"
		>
			{{ t('messages.savingData') }}
		</fb-ui-loading-box>

		<fb-ui-result-ok v-if="props.remoteFormResult === FbFormResultTypes.OK" />
		<fb-ui-result-err v-if="props.remoteFormResult === FbFormResultTypes.ERROR" />
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useField, useForm, useFieldError } from 'vee-validate';
import { object as yObject, string as yString, array as yArray } from 'yup';
import { orderBy } from 'natural-orderby';
import get from 'lodash/get';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbUiButton,
	FbUiContent,
	FbUiDivider,
	FbUiItemsContainer,
	FbUiLoadingBox,
	FbUiResultErr,
	FbUiResultOk,
	FbFormResultTypes,
	FbSizeTypes,
	FbUiButtonVariantTypes,
	FbUiDividerVariantTypes,
} from '@fastybird/web-ui-library';
import { DataType, ModuleSource, PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage, useUuid } from '../../composables';
import { useConnectors, useConnectorProperties } from '../../models';
import { IConnectorProperty } from '../../models/types';
import {
	ConnectorSettingsConnectorDevice,
	ConnectorSettingsConnectorRename,
	PropertySettingsProperty,
	PropertySettingsPropertyAddModal,
	PropertySettingsVariablePropertiesEdit,
} from '../../components';
import { IDeviceData } from '../../types';
import {
	IConnectorSettingsConnectorSettingsProps,
	IConnectorSettingsConnectorSettingsForm,
	ConnectorSettingsConnectorSettingsViewTypes,
} from './connector-settings-connector-settings.types';

const props = withDefaults(defineProps<IConnectorSettingsConnectorSettingsProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FbFormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FbFormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addDevice'): void;
	(e: 'editDevice', id: string): void;
}>();

const { t } = useI18n();

const { generate: generateUuid } = useUuid();
const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();
const propertiesStore = useConnectorProperties();

const activeView = ref<ConnectorSettingsConnectorSettingsViewTypes>(ConnectorSettingsConnectorSettingsViewTypes.NONE);

const variableProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

const { validate } = useForm<IConnectorSettingsConnectorSettingsForm>({
	validationSchema: yObject({
		about: yObject({
			identifier: yString().required(t('fields.identifier.validation.required')),
			name: yString().nullable().default(null),
			comment: yString().nullable().default(null),
		}),
		properties: yObject({
			static: yArray(yObject({ id: yString().required(), value: yString().nullable().default(null) })),
		}),
	}),
	initialValues: {
		about: {
			identifier: props.connectorData.connector.identifier,
			name: props.connectorData.connector.name,
			comment: props.connectorData.connector.comment,
		},
		properties: {
			static: variableProperties.value.map((property) => {
				return { id: property.id, value: property.value as string };
			}),
		},
	},
});

const { value: aboutField } = useField<{ identifier: string; name: string | undefined; comment: string | undefined }>('about');
const { value: variablePropertiesFields } = useField<{ id: string; value: string | undefined }[]>('properties.static');

const identifierError = useFieldError('about.identifier');
const nameError = useFieldError('about.name');
const commentError = useFieldError('about.comment');

let timer: number;

const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
	return orderBy<IDeviceData>(
		props.connectorData.devices,
		[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
		['asc']
	);
});

const onOpenView = async (view: ConnectorSettingsConnectorSettingsViewTypes): Promise<void> => {
	if (view === ConnectorSettingsConnectorSettingsViewTypes.ADD_STATIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			connector: props.connectorData.connector,
			type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.VARIABLE, parent: 'connector' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	} else if (view === ConnectorSettingsConnectorSettingsViewTypes.ADD_DYNAMIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			connector: props.connectorData.connector,
			type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.DYNAMIC, parent: 'connector' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	}

	activeView.value = view;
};

const onCloseView = async (): Promise<void> => {
	if (
		(activeView.value === ConnectorSettingsConnectorSettingsViewTypes.ADD_STATIC_PARAMETER ||
			activeView.value === ConnectorSettingsConnectorSettingsViewTypes.ADD_DYNAMIC_PARAMETER) &&
		newProperty.value?.draft
	) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}

	activeView.value = ConnectorSettingsConnectorSettingsViewTypes.NONE;
};

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FbFormResultTypes.NONE);
};

onBeforeUnmount(async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				const errorMessage = t('messages.notEdited', {
					connector: useEntityTitle(props.connectorData.connector).value,
				});

				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				try {
					await connectorsStore.edit({
						id: props.connectorData.connector.id,
						data: {
							name: aboutField.value.name,
							comment: aboutField.value.comment,
						},
					});
				} catch (e: any) {
					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FbFormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);

					return;
				}

				let success = true;

				for (const variablePropertyField of variablePropertiesFields.value) {
					try {
						await propertiesStore.edit({
							id: variablePropertyField.id,
							data: {
								value: variablePropertyField.value,
							},
						});
					} catch (e: any) {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), errorMessage);
						} else {
							flashMessage.error(errorMessage);
						}

						emit('update:remoteFormResult', FbFormResultTypes.ERROR);

						timer = window.setTimeout(clearResult, 2000);

						success = false;

						break;
					}
				}

				if (success) {
					emit('update:remoteFormResult', FbFormResultTypes.OK);

					timer = window.setTimeout(clearResult, 2000);
				}
			}
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connector-settings-connector-settings';
</style>

<i18n>
{
  "en": {
    "headings": {
      "aboutConnector": "About connector",
      "variableProperties": "Connector config parameters",
      "dynamicProperties": "Connector data parameters",
      "devices": "Devices"
    },
    "buttons": {
      "addDevice": {
        "title": "Add device"
      },
      "addProperty": {
        "title": "Add parameter"
      }
    },
    "messages": {
      "savingData": "Saving connector",
      "notEdited": "Connector {connector} couldn't be edited."
    },
    "fields": {
      "identifier": {
        "validation": {
          "required": "Please fill in connector identifier"
        }
      }
    }
  }
}
</i18n>
