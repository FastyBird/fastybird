<template>
	<div
		v-loading="isLoading || connectorData === null"
		:element-loading-text="t('texts.misc.loadingConnector')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorData !== null">
			<fb-app-bar-heading
				v-if="isXSDevice"
				teleport
			>
				<template #icon>
					<connectors-connector-icon :connector="connectorData.connector" />
				</template>

				<template #title>
					{{ t('headings.connectors.configuration') }}
				</template>

				<template #subtitle>
					{{ useEntityTitle(connectorData.connector).value }}
				</template>
			</fb-app-bar-heading>

			<fb-app-bar-button
				v-if="isXSDevice"
				teleport
				:align="AppBarButtonAlignTypes.LEFT"
				small
				@click="onClose"
			>
				<span class="uppercase">{{ t('buttons.close.title') }}</span>
			</fb-app-bar-button>

			<fb-app-bar-button
				v-if="isXSDevice"
				teleport
				:align="AppBarButtonAlignTypes.LEFT"
				small
				@click="onSubmit"
			>
				<span class="uppercase">{{ t('buttons.save.title') }}</span>
			</fb-app-bar-button>

			<el-scrollbar class="flex-1">
				<connector-settings-connector-settings
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
					:connector-data="connectorData"
					:loading="isLoading"
					:devices-loading="areDevicesLoading"
					@created="onCreated"
					@add-device="onAddDevice"
					@edit-device="onEditDevice"
					@remove-device="onRemoveDevice"
					@add-static-property="onAddStaticProperty"
					@add-dynamic-property="onAddDynamicProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>
			</el-scrollbar>

			<div
				v-if="!isXSDevice"
				class="flex flex-row gap-2 justify-end b-t b-t-solid p-2 shadow-top z-10"
			>
				<el-button
					:loading="remoteFormResult === FormResultTypes.WORKING"
					:disabled="remoteFormResult !== FormResultTypes.NONE"
					:icon="remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined"
					type="primary"
					class="order-2"
					@click="onSubmit"
				>
					{{ t('buttons.save.title') }}
				</el-button>

				<el-button
					:disabled="remoteFormResult !== FormResultTypes.NONE"
					class="order-1"
					@click="onClose"
				>
					{{ t('buttons.close.title') }}
				</el-button>
			</div>

			<property-settings-property-add-modal
				v-if="newProperty !== null"
				:property="newProperty"
				:connector="connectorData.connector"
				@created="onPropertyCreated"
				@close="onCancelAddProperty"
			/>

			<property-settings-property-edit-modal
				v-if="editProperty !== null"
				:property="editProperty"
				:connector="connectorData.connector"
				@close="onCloseEditProperty"
			/>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, onBeforeUnmount, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';
import { orderBy } from 'natural-orderby';
import get from 'lodash.get';
import { ElButton, ElMessageBox, ElScrollbar, vLoading } from 'element-plus';

import { FarCircleXmark, FarCircleCheck } from '@fastybird/web-ui-icons';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { DataType, ModuleSource, PropertyType } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '../composables';
import { ApplicationError } from '../errors';
import {
	useChannelControls,
	useChannelProperties,
	useChannels,
	useConnectorControls,
	useConnectorProperties,
	useConnectors,
	useDeviceControls,
	useDeviceProperties,
	useDevices,
} from '../models';
import {
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDeviceControl,
	IDeviceProperty,
} from '../models/types';
import {
	ConnectorsConnectorIcon,
	ConnectorSettingsConnectorSettings,
	PropertySettingsPropertyAddModal,
	PropertySettingsPropertyEditModal,
} from '../components';
import { FormResultTypes, IChannelData, IConnectorData, IDeviceData } from '../types';
import { IViewConnectorSettingsProps } from './view-connector-settings.types';

defineOptions({
	name: 'ViewConnectorSettings',
});

const props = defineProps<IViewConnectorSettingsProps>();

const { t } = useI18n();
const router = useRouter();

const flashMessage = useFlashMessage();
const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { meta } = useMeta({});

const connectorsStore = useConnectors();
const connectorControlsStore = useConnectorControls();
const connectorPropertiesStore = useConnectorProperties();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();
const propertiesStore = useConnectorProperties();

if (props.id !== null && !validateUuid(props.id)) {
	throw new Error('Connector identifier is not valid');
}

const id = ref<string>(props.id ?? generateUuid());

const isLoading = computed<boolean>((): boolean => {
	if (connectorsStore.getting(id.value)) {
		return true;
	}

	if (connectorsStore.findById(id.value)) {
		return false;
	}

	return connectorsStore.fetching();
});
const areDevicesLoading = computed<boolean>((): boolean => {
	if (devicesStore.fetching(id.value)) {
		return true;
	}

	if (devicesStore.firstLoadFinished(id.value)) {
		return false;
	}

	return devicesStore.fetching();
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null =>
	editPropertyId.value ? propertiesStore.findById(editPropertyId.value) : null
);

if (props.id === null) {
	await connectorsStore.add({
		id: id.value,
		type: { source: ModuleSource.MODULE_DEVICES, type: 'generic', entity: 'connector' },
		draft: true,
		data: {
			identifier: generateUuid().toString(),
		},
	});
}

const connectorData = computed<IConnectorData | null>((): IConnectorData | null => {
	const connector = connectorsStore.findById(id.value);

	if (connector === null) {
		return null;
	}

	return {
		connector,
		controls: orderBy<IConnectorControl>(
			connectorControlsStore.findForConnector(connector.id).filter((control) => !control.draft),
			[(v): string => v.name],
			['asc']
		),
		properties: orderBy<IConnectorProperty>(
			connectorPropertiesStore.findForConnector(connector.id).filter((control) => !control.draft),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		),
		devices: orderBy<IDeviceData>(
			devicesStore.findForConnector(connector.id).map((device): IDeviceData => {
				return {
					device,
					controls: orderBy<IDeviceControl>(
						deviceControlsStore.findForDevice(device.id).filter((control) => !control.draft),
						[(v): string => v.name],
						['asc']
					),
					properties: orderBy<IDeviceProperty>(
						devicePropertiesStore.findForDevice(device.id).filter((property) => !property.draft),
						[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
						['asc']
					),
					channels: orderBy<IChannelData>(
						channelsStore.findForDevice(device.id).map((channel): IChannelData => {
							return {
								channel,
								controls: orderBy<IChannelControl>(
									channelControlsStore.findForChannel(channel.id).filter((control) => !control.draft),
									[(v): string => v.name],
									['asc']
								),
								properties: orderBy<IChannelProperty>(
									channelPropertiesStore.findForChannel(channel.id).filter((property) => !property.draft),
									[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
									['asc']
								),
							};
						}),
						[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
						['asc']
					),
				};
			}),
			[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
			['asc']
		),
	};
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	if (connectorData.value!.connector.draft) {
		router.push({ name: routeNames.connectors });
	} else {
		router.push({ name: routeNames.connectorDetail, params: { id: id.value } });
	}
};

const onAddDevice = (): void => {
	router.push({ name: routeNames.connectorSettingsAddDevice, params: { id: id.value } });
};

const onEditDevice = (deviceId: string): void => {
	router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: id.value, deviceId } });
};

const onRemoveDevice = async (id: string): Promise<void> => {
	const device = devicesStore.findById(id);

	if (device === null) {
		return;
	}

	ElMessageBox.confirm(t('messages.devices.confirmRemove', { device: useEntityTitle(device).value }), t('headings.devices.remove'), {
		confirmButtonText: t('buttons.yes.title'),
		cancelButtonText: t('buttons.no.title'),
		type: 'warning',
	})
		.then(async (): Promise<void> => {
			const errorMessage = t('messages.devices.notRemoved', {
				device: useEntityTitle(device).value,
			});

			devicesStore.remove({ id: device.id }).catch((e): void => {
				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(get(e, 'exception', null), errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			});
		})
		.catch(() => {
			flashMessage.info(
				t('messages.devices.removeCanceled', {
					device: useEntityTitle(device).value,
				})
			);
		});
};

const onCreated = (connector: IConnector): void => {
	router.push({ name: routeNames.connectorSettings, params: { id: connector.id } });
};

const onAddStaticProperty = async (): Promise<void> => {
	if (connectorData.value === null) {
		return;
	}

	const { id } = await propertiesStore.add({
		connector: connectorData.value.connector,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.VARIABLE, parent: 'connector', entity: 'property' },
		draft: true,
		data: {
			identifier: generateUuid(),
			dataType: DataType.UNKNOWN,
		},
	});

	newPropertyId.value = id;
};

const onAddDynamicProperty = async (): Promise<void> => {
	if (connectorData.value === null) {
		return;
	}

	const { id } = await propertiesStore.add({
		connector: connectorData.value.connector,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.DYNAMIC, parent: 'connector', entity: 'property' },
		draft: true,
		data: {
			identifier: generateUuid(),
			dataType: DataType.UNKNOWN,
		},
	});

	newPropertyId.value = id;
};

const onPropertyCreated = async (): Promise<void> => {
	newPropertyId.value = null;
};

const onCancelAddProperty = async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
	}

	newPropertyId.value = null;
};

const onEditProperty = async (id: string): Promise<void> => {
	const property = propertiesStore.findById(id);

	if (property === null) {
		return;
	}

	editPropertyId.value = id;
};

const onCloseEditProperty = async (): Promise<void> => {
	editPropertyId.value = null;
};

const onRemoveProperty = async (id: string): Promise<void> => {
	const property = propertiesStore.findById(id);

	if (property === null) {
		return;
	}

	ElMessageBox.confirm(
		t('messages.properties.confirmRemoveConnectorProperty', { property: useEntityTitle(property).value }),
		t('headings.properties.remove'),
		{
			confirmButtonText: t('buttons.yes.title'),
			cancelButtonText: t('buttons.no.title'),
			type: 'warning',
		}
	)
		.then(async (): Promise<void> => {
			const errorMessage = t('messages.properties.notRemoved', {
				property: useEntityTitle(property).value,
			});

			propertiesStore.remove({ id: property.id }).catch((e): void => {
				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(get(e, 'exception', null), errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			});
		})
		.catch(() => {
			flashMessage.info(
				t('messages.properties.removeCanceled', {
					property: useEntityTitle(property).value,
				})
			);
		});
};

onBeforeMount(async (): Promise<void> => {
	fetchConnector(id.value)
		.then(() => {
			if (!isLoading.value && connectorsStore.findById(id.value) === null) {
				throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
			}
		})
		.catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	fetchDevices(id.value).catch((e): void => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});
});

onBeforeUnmount(async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (connectorData.value?.connector.draft) {
		connectorsStore.remove({ id: connectorData.value?.connector.id });
	}
});

const fetchConnector = async (id: IConnector['id']): Promise<void> => {
	await connectorsStore.get({ id, refresh: !connectorsStore.firstLoadFinished() });

	const connector = connectorsStore.findById(id);

	if (connector) {
		await connectorPropertiesStore.fetch({ connector, refresh: false });
		await connectorControlsStore.fetch({ connector, refresh: false });
	}
};

const fetchDevices = async (connectorId: IConnector['id']): Promise<void> => {
	await devicesStore.fetch({ connectorId, refresh: !devicesStore.firstLoadFinished(connectorId) });

	const devices = devicesStore.findForConnector(connectorId);

	for (const device of devices) {
		await devicePropertiesStore.fetch({ device, refresh: false });
		await deviceControlsStore.fetch({ device, refresh: false });
	}
};

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && connectorData.value === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);

watch(
	(): IConnectorData | null => connectorData.value,
	(val: IConnectorData | null): void => {
		if (val !== null) {
			meta.title = t('meta.connectors.settings.title', { connector: useEntityTitle(val.connector).value });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);

watch(
	(): string => id.value,
	async (val: string): Promise<void> => {
		fetchConnector(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

		fetchDevices(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}
);
</script>
