<template>
	<div
		v-loading="isLoading || deviceData === null"
		:element-loading-text="t('texts.misc.loadingDevice')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="deviceData !== null">
			<fb-app-bar-heading
				v-if="isXSDevice"
				teleport
			>
				<template #icon>
					<devices-device-icon :device="deviceData.device" />
				</template>

				<template #title>
					{{ t('headings.devices.configuration') }}
				</template>

				<template #subtitle>
					{{ useEntityTitle(deviceData.device).value }}
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
				<device-settings-device-settings
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
					:connector="connector"
					:device-data="deviceData"
					:loading="isLoading"
					:channels-loading="areChannelsLoading"
					@created="onCreated"
					@add-channel="onAddChannel"
					@edit-channel="onEditChannel"
					@remove-channel="onRemoveChannel"
					@reset-channel="onResetChannel"
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
				:device="deviceData.device"
				@created="onPropertyCreated"
				@close="onCloseAddProperty"
			/>

			<property-settings-property-edit-modal
				v-if="editProperty !== null"
				:property="editProperty"
				:device="deviceData.device"
				@close="onCloseEditProperty"
			/>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, onBeforeUnmount, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import { orderBy } from 'natural-orderby';
import get from 'lodash.get';
import { ElButton, ElMessageBox, ElScrollbar, vLoading } from 'element-plus';

import { FarCircleCheck, FarCircleXmark } from '@fastybird/web-ui-icons';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { DataType, ModuleSource, PropertyType } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '../composables';
import { ApplicationError } from '../errors';
import { useChannelControls, useChannelProperties, useChannels, useConnectors, useDeviceControls, useDeviceProperties, useDevices } from '../models';
import { IConnector, IChannelControl, IChannelProperty, IDevice, IDeviceControl, IDeviceProperty } from '../models/types';
import { DevicesDeviceIcon, DeviceSettingsDeviceSettings, PropertySettingsPropertyAddModal, PropertySettingsPropertyEditModal } from '../components';
import { FormResultTypes, IChannelData, IDeviceData } from '../types';
import { IViewDeviceSettingsProps } from './view-device-settings.types';

defineOptions({
	name: 'ViewDeviceSettings',
});

const props = withDefaults(defineProps<IViewDeviceSettingsProps>(), {
	connectorId: null,
});

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const flashMessage = useFlashMessage();
const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { meta } = useMeta({});

const connectorsStore = useConnectors();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

if (props.id !== null && !validateUuid(props.id)) {
	throw new Error('Device identifier is not valid');
}

const id = ref<string>(props.id ?? generateUuid());

const isLoading = computed<boolean>((): boolean => {
	if (devicesStore.getting(id.value)) {
		return true;
	}

	if (devicesStore.findById(id.value)) {
		return false;
	}

	return devicesStore.fetching(props.connectorId ?? null);
});
const areChannelsLoading = computed<boolean>((): boolean => {
	if (channelsStore.fetching(id.value)) {
		return true;
	}

	if (channelsStore.firstLoadFinished(id.value)) {
		return false;
	}

	return channelsStore.fetching();
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IDeviceProperty | null>((): IDeviceProperty | null =>
	newPropertyId.value ? devicePropertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IDeviceProperty | null>((): IDeviceProperty | null =>
	editPropertyId.value ? devicePropertiesStore.findById(editPropertyId.value) : null
);

const isConnectorSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find((matched) => {
			return matched.name === routeNames.connectorSettingsAddDevice || matched.name === routeNames.connectorSettingsEditDevice;
		}) !== undefined
	);
});

const connector = computed<IConnector>((): IConnector => {
	if (props.connectorId !== null && !validateUuid(props.connectorId)) {
		throw new Error('Connector identifier is not valid');
	}

	if (props.connectorId !== null) {
		const connector = connectorsStore.findById(props.connectorId);

		if (connector === null) {
			throw new Error('Connector was not found');
		}

		return connector;
	}

	if (props.id === null) {
		throw new Error('Connector was not found');
	}

	const device = devicesStore.findById(props.id);

	if (device === null) {
		throw new Error('Connector was not found');
	}

	const connector = connectorsStore.findById(device.connector.id);

	if (connector === null) {
		throw new Error('Connector was not found');
	}

	return connector;
});

if (props.id === null) {
	await devicesStore.add({
		id: id.value,
		connector: connector.value,
		type: { source: ModuleSource.MODULE_DEVICES, type: 'generic', entity: 'device' },
		draft: true,
		data: {
			identifier: generateUuid().toString(),
		},
	});
}

const deviceData = computed<IDeviceData | null>((): IDeviceData | null => {
	const device = devicesStore.findById(id.value);

	if (device === null) {
		return null;
	}

	return {
		device: device,
		controls: orderBy<IDeviceControl>(
			deviceControlsStore.findForDevice(device.id).filter((control) => (device?.draft ? true : !control.draft)),
			[(v): string => v.name],
			['asc']
		),
		properties: orderBy<IDeviceProperty>(
			devicePropertiesStore.findForDevice(device.id).filter((property) => (device?.draft ? true : !property.draft)),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		),
		channels: orderBy<IChannelData>(
			channelsStore.findForDevice(device.id).map((channel): IChannelData => {
				return {
					channel,
					controls: orderBy<IChannelControl>(
						channelControlsStore.findForChannel(channel.id).filter((control) => (channel.draft ? true : !control.draft)),
						[(v): string => v.name],
						['asc']
					),
					properties: orderBy<IChannelProperty>(
						channelPropertiesStore.findForChannel(channel.id).filter((property) => (channel.draft ? true : !property.draft)),
						[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
						['asc']
					),
				};
			}),
			[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
			['asc']
		),
	};
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettings, params: { id: props.connectorId } });
	} else {
		if (deviceData.value!.device.draft) {
			router.push({ name: routeNames.devices });
		} else {
			router.push({ name: routeNames.deviceDetail, params: { id: props.id } });
		}
	}
};

const onAddChannel = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDeviceAddChannel, params: { id: connector.value.id, deviceId: props.id } });
	} else {
		router.push({ name: routeNames.deviceSettingsAddChannel, params: { id: props.id } });
	}
};

const onEditChannel = (id: string): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDeviceEditChannel, params: { id: connector.value.id, deviceId: props.id, channelId: id } });
	} else {
		router.push({ name: routeNames.deviceSettingsEditChannel, params: { id: props.id, channelId: id } });
	}
};

const onRemoveChannel = async (id: string): Promise<void> => {
	const channel = channelsStore.findById(id);

	if (channel === null) {
		return;
	}

	ElMessageBox.confirm(t('messages.channels.confirmRemove', { channel: useEntityTitle(channel).value }), t('headings.channels.remove'), {
		confirmButtonText: t('buttons.yes.title'),
		cancelButtonText: t('buttons.no.title'),
		type: 'warning',
	})
		.then(async (): Promise<void> => {
			const errorMessage = t('messages.channels.notRemoved', {
				channel: useEntityTitle(channel).value,
			});

			channelsStore.remove({ id: channel.id }).catch((e): void => {
				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(get(e, 'exception', null), errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			});
		})
		.catch(() => {
			flashMessage.info(
				t('messages.channels.removeCanceled', {
					channel: useEntityTitle(channel).value,
				})
			);
		});
};

const onResetChannel = async (id: string): Promise<void> => {
	const channel = channelsStore.findById(id);

	if (channel === null) {
		return;
	}

	// TODO: Reset channel
};

const onCreated = (device: IDevice): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: props.connectorId, deviceId: device.id } });
	} else {
		router.push({ name: routeNames.deviceSettings, params: { id: device.id } });
	}
};

const onAddStaticProperty = async (): Promise<void> => {
	if (deviceData.value === null) {
		return;
	}

	const { id } = await devicePropertiesStore.add({
		device: deviceData.value.device,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.VARIABLE, parent: 'device', entity: 'property' },
		draft: true,
		data: {
			identifier: generateUuid(),
			dataType: DataType.UNKNOWN,
		},
	});

	newPropertyId.value = id;
};

const onAddDynamicProperty = async (): Promise<void> => {
	if (deviceData.value === null) {
		return;
	}

	const { id } = await devicePropertiesStore.add({
		device: deviceData.value.device,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.DYNAMIC, parent: 'device', entity: 'property' },
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

const onCloseAddProperty = async (canceled: boolean): Promise<void> => {
	if (canceled && newProperty.value?.draft) {
		await devicePropertiesStore.remove({ id: newProperty.value.id });
	}

	newPropertyId.value = null;
};

const onEditProperty = async (id: string): Promise<void> => {
	const property = devicePropertiesStore.findById(id);

	if (property === null) {
		return;
	}

	editPropertyId.value = id;
};

const onCloseEditProperty = async (): Promise<void> => {
	editPropertyId.value = null;
};

const onRemoveProperty = async (id: string): Promise<void> => {
	const property = devicePropertiesStore.findById(id);

	if (property === null) {
		return;
	}

	ElMessageBox.confirm(
		t('messages.properties.confirmRemoveDeviceProperty', { property: useEntityTitle(property).value }),
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

			devicePropertiesStore.remove({ id: property.id }).catch((e): void => {
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
	fetchDevice(id.value)
		.then((): void => {
			if (!isLoading.value && devicesStore.findById(id.value) === null) {
				throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
			}
		})
		.catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	fetchChannels(id.value).catch((e): void => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});
});

onBeforeUnmount(async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await devicePropertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (deviceData.value?.device.draft) {
		devicesStore.remove({ id: deviceData.value?.device.id });
	}
});

const fetchDevice = async (id: IDevice['id']): Promise<void> => {
	await devicesStore.get({ id, refresh: !devicesStore.firstLoadFinished() });

	const device = devicesStore.findById(id);

	if (device) {
		await devicePropertiesStore.fetch({ device, refresh: false });
		await deviceControlsStore.fetch({ device, refresh: false });
	}
};

const fetchChannels = async (deviceId: IDevice['id']): Promise<void> => {
	await channelsStore.fetch({ deviceId: deviceId, refresh: !channelsStore.firstLoadFinished(deviceId) });

	const channels = channelsStore.findForDevice(deviceId);

	for (const channel of channels) {
		await channelPropertiesStore.fetch({ channel, refresh: false });
		await channelControlsStore.fetch({ channel, refresh: false });
	}
};

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && deviceData.value === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);

watch(
	(): IDeviceData | null => deviceData.value,
	async (val: IDeviceData | null): Promise<void> => {
		if (val !== null) {
			meta.title = t('meta.devices.settings.title', { device: useEntityTitle(val.device).value });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);

watch(
	(): string => id.value,
	async (val: string): Promise<void> => {
		fetchDevice(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

		fetchChannels(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}
);
</script>
