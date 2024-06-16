<template>
	<el-form
		ref="connectorFormEl"
		:model="connectorForm"
		:rules="rules"
		label-position="top"
		status-icon
		class="px-5 py-5"
	>
		<h3>
			{{ t('headings.connectors.aboutConnector') }}
		</h3>

		<connector-settings-connector-rename
			v-model="connectorForm.about"
			:connector="props.connectorData.connector"
		/>

		<property-settings-variable-properties-edit
			v-model="connectorForm.properties.static"
			:properties="variableProperties"
		/>
	</el-form>

	<el-divider />

	<fb-list
		v-loading="props.devicesLoading"
		:element-loading-text="t('texts.misc.loadingDevices')"
		class="pb-2"
	>
		<template #title>
			{{ t('headings.connectors.devices') }}
		</template>

		<template
			v-if="devicesData.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addDevice', $event)"
			>
				{{ t('buttons.addDevice.title') }}
			</el-button>
		</template>

		<div
			v-if="devicesData.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				plain
				class="w-full"
				@click="emit('addDevice', $event)"
			>
				<span>{{ t('buttons.addDevice.title') }}</span>
			</el-button>
		</div>

		<connector-settings-connector-device
			v-for="deviceData in devicesData"
			:key="deviceData.device.id"
			:connector="props.connectorData.connector"
			:device-data="deviceData"
			@edit="emit('editDevice', deviceData.device.id, $event)"
			@remove="emit('removeDevice', deviceData.device.id, $event)"
		/>
	</fb-list>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.connectors.variableProperties') }}
		</template>

		<template
			v-if="variableProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addStaticProperty', $event)"
			>
				{{ t('buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="variableProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addStaticProperty', $event)"
			>
				<span>{{ t('buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-settings-property
			v-for="property in variableProperties"
			:key="property.identifier"
			:connector="props.connectorData.connector"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.connectors.dynamicProperties') }}
		</template>

		<template
			v-if="dynamicProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addDynamicProperty', $event)"
			>
				{{ t('buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="dynamicProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addDynamicProperty', $event)"
			>
				<span>{{ t('buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-settings-property
			v-for="property in dynamicProperties"
			:key="property.identifier"
			:connector="props.connectorData.connector"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';
import get from 'lodash.get';
import { ElButton, ElDivider, ElForm, vLoading, FormInstance, FormRules } from 'element-plus';

import { FasPlus } from '@fastybird/web-ui-icons';
import { FbList } from '@fastybird/web-ui-library';
import { PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage } from '../../composables';
import { useConnectors, useConnectorProperties } from '../../models';
import { IConnector, IConnectorProperty } from '../../models/types';
import {
	ConnectorSettingsConnectorDevice,
	ConnectorSettingsConnectorRename,
	PropertySettingsProperty,
	PropertySettingsVariablePropertiesEdit,
} from '../../components';
import { FormResultTypes, IDeviceData } from '../../types';
import { IConnectorSettingsConnectorSettingsProps, IConnectorSettingsConnectorSettingsForm } from './connector-settings-connector-settings.types';

defineOptions({
	name: 'ConnectorSettingsConnectorSettings',
});

const props = withDefaults(defineProps<IConnectorSettingsConnectorSettingsProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addDevice', event: Event): void;
	(e: 'editDevice', id: string, event: Event): void;
	(e: 'removeDevice', id: string, event: Event): void;
	(e: 'addStaticProperty', event: Event): void;
	(e: 'addDynamicProperty', event: Event): void;
	(e: 'editProperty', id: string, event: Event): void;
	(e: 'removeProperty', id: string, event: Event): void;
	(e: 'created', connector: IConnector): void;
}>();

const { t } = useI18n();

const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();
const propertiesStore = useConnectorProperties();

const connectorFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const rules = reactive<FormRules<IConnectorSettingsConnectorSettingsForm>>({
	about: {
		type: 'object',
		required: true,
		fields: {
			identifier: [{ type: 'string', required: true, message: t('fields.connectors.identifier.validation.required') }],
			name: [{ type: 'string', required: false }],
			comment: [{ type: 'string', required: false }],
		},
	},
	properties: {
		type: 'object',
		required: true,
		fields: {
			static: {
				type: 'array',
				required: true,
				fields: {
					id: [{ type: 'string', required: true, message: t('fields.connectors.identifier.validation.required') }],
					value: [{ required: false }],
				},
			},
		},
	},
});

const connectorForm = reactive<IConnectorSettingsConnectorSettingsForm>({
	about: {
		identifier: props.connectorData.connector.identifier,
		name: props.connectorData.connector.name,
		comment: props.connectorData.connector.comment,
	},
	properties: {
		static: variableProperties.value.map((property) => ({ id: property.id, value: property.value })),
	},
});

let timer: number;

const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
	return orderBy<IDeviceData>(
		props.connectorData.devices,
		[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
		['asc']
	);
});

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			await connectorFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				const errorMessage = t('messages.connectors.notEdited', {
					connector: useEntityTitle(props.connectorData.connector).value,
				});

				emit('update:remoteFormResult', FormResultTypes.WORKING);

				try {
					await connectorsStore.edit({
						id: props.connectorData.connector.id,
						data: {
							name: connectorForm.about.name,
							comment: connectorForm.about.comment,
						},
					});
				} catch (e: any) {
					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);

					return;
				}

				let success = true;

				for (const variablePropertyField of connectorForm.properties.static) {
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

						emit('update:remoteFormResult', FormResultTypes.ERROR);

						timer = window.setTimeout(clearResult, 2000);

						success = false;

						break;
					}
				}

				if (success) {
					emit('update:remoteFormResult', FormResultTypes.OK);

					timer = window.setTimeout(clearResult, 2000);
				}
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val: boolean): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			connectorForm.about.identifier = props.connectorData.connector.identifier;
			connectorForm.about.name = props.connectorData.connector.name;
			connectorForm.about.comment = props.connectorData.connector.comment;
		}
	}
);
</script>
