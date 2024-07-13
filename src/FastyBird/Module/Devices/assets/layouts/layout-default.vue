<template>
	<RouterView v-if="populated" />
</template>

<script setup lang="ts">
import { onBeforeMount, ref } from 'vue';
import get from 'lodash.get';

import {
	ChannelControlDocument,
	ChannelDocument,
	ChannelPropertyDocument,
	ConnectorControlDocument,
	ConnectorDocument,
	ConnectorPropertyDocument,
	DeviceControlDocument,
	DeviceDocument,
	DevicePropertyDocument,
} from '@fastybird/metadata-library';

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

defineOptions({
	name: 'LayoutDefault',
});

const connectorsStore = useConnectors();
const connectorPropertiesStore = useConnectorProperties();
const connectorControlsStore = useConnectorControls();
const devicesStore = useDevices();
const devicePropertiesStore = useDeviceProperties();
const deviceControlsStore = useDeviceControls();
const channelsStore = useChannels();
const channelPropertiesStore = useChannelProperties();
const channelControlsStore = useChannelControls();

const populated = ref<boolean>(false);

onBeforeMount(async (): Promise<void> => {
	const ssrConnectorsData: ConnectorDocument | ConnectorDocument[] | null = get(window, '__DEVICES_MODULE_CONNECTORS__', null);

	if (ssrConnectorsData !== null) {
		await connectorsStore.insertData({
			data: ssrConnectorsData,
		});
	}

	const ssrConnectorsPropertiesData: ConnectorPropertyDocument | ConnectorPropertyDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CONNECTORS_PROPERTIES__',
		null
	);

	if (ssrConnectorsPropertiesData !== null) {
		await connectorPropertiesStore.insertData({
			data: ssrConnectorsPropertiesData,
		});
	}

	const ssrConnectorsControlsData: ConnectorControlDocument | ConnectorControlDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CONNECTORS_CONTROLS__',
		null
	);

	if (ssrConnectorsControlsData !== null) {
		await connectorControlsStore.insertData({
			data: ssrConnectorsControlsData,
		});
	}

	const ssrConnectorData: ConnectorDocument | ConnectorDocument[] | null = get(window, '__DEVICES_MODULE_CONNECTOR__', null);

	if (ssrConnectorData !== null) {
		await connectorsStore.insertData({
			data: ssrConnectorData,
		});
	}

	const ssrConnectorPropertiesData: ConnectorPropertyDocument | ConnectorPropertyDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CONNECTOR_PROPERTIES__',
		null
	);

	if (ssrConnectorPropertiesData !== null) {
		await connectorPropertiesStore.insertData({
			data: ssrConnectorPropertiesData,
		});
	}

	const ssrConnectorControlsData: ConnectorControlDocument | ConnectorControlDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CONNECTOR_CONTROLS__',
		null
	);

	if (ssrConnectorControlsData !== null) {
		await connectorControlsStore.insertData({
			data: ssrConnectorControlsData,
		});
	}

	const ssrDevicesData: DeviceDocument | DeviceDocument[] | null = get(window, '__DEVICES_MODULE_DEVICES__', null);

	if (ssrDevicesData !== null) {
		await devicesStore.insertData({
			data: ssrDevicesData,
		});
	}

	const ssrDevicesPropertiesData: DevicePropertyDocument | DevicePropertyDocument[] | null = get(
		window,
		'__DEVICES_MODULE_DEVICES_PROPERTIES__',
		null
	);

	if (ssrDevicesPropertiesData !== null) {
		await devicePropertiesStore.insertData({
			data: ssrDevicesPropertiesData,
		});
	}

	const ssrDevicesControlsData: DeviceControlDocument | DeviceControlDocument[] | null = get(window, '__DEVICES_MODULE_DEVICES_CONTROLS__', null);

	if (ssrDevicesControlsData !== null) {
		await deviceControlsStore.insertData({
			data: ssrDevicesControlsData,
		});
	}

	const ssrDeviceData: DeviceDocument | DeviceDocument[] | null = get(window, '__DEVICES_MODULE_DEVICE__', null);

	if (ssrDeviceData !== null) {
		await devicesStore.insertData({
			data: ssrDeviceData,
		});
	}

	const ssrDevicePropertiesData: DevicePropertyDocument | DevicePropertyDocument[] | null = get(window, '__DEVICES_MODULE_DEVICE_PROPERTIES__', null);

	if (ssrDevicePropertiesData !== null) {
		await devicePropertiesStore.insertData({
			data: ssrDevicePropertiesData,
		});
	}

	const ssrDeviceControlsData: DeviceControlDocument | DeviceControlDocument[] | null = get(window, '__DEVICES_MODULE_DEVICE_CONTROLS__', null);

	if (ssrDeviceControlsData !== null) {
		await deviceControlsStore.insertData({
			data: ssrDeviceControlsData,
		});
	}

	const ssrChannelsData: ChannelDocument | ChannelDocument[] | null = get(window, '__DEVICES_MODULE_CHANNELS__', null);

	if (ssrChannelsData !== null) {
		await channelsStore.insertData({
			data: ssrChannelsData,
		});
	}

	const ssrChannelsPropertiesData: ChannelPropertyDocument | ChannelPropertyDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CHANNELS_PROPERTIES__',
		null
	);

	if (ssrChannelsPropertiesData !== null) {
		await channelPropertiesStore.insertData({
			data: ssrChannelsPropertiesData,
		});
	}

	const ssrChannelsControlsData: ChannelControlDocument | ChannelControlDocument[] | null = get(window, '__DEVICES_MODULE_CHANNELS_CONTROLS__', null);

	if (ssrChannelsControlsData !== null) {
		await channelControlsStore.insertData({
			data: ssrChannelsControlsData,
		});
	}

	const ssrChannelData: ChannelDocument | ChannelDocument[] | null = get(window, '__DEVICES_MODULE_CHANNEL__', null);

	if (ssrChannelData !== null) {
		await channelsStore.insertData({
			data: ssrChannelData,
		});
	}

	const ssrChannelPropertiesData: ChannelPropertyDocument | ChannelPropertyDocument[] | null = get(
		window,
		'__DEVICES_MODULE_CHANNEL_PROPERTIES__',
		null
	);

	if (ssrChannelPropertiesData !== null) {
		await channelPropertiesStore.insertData({
			data: ssrChannelPropertiesData,
		});
	}

	const ssrChannelControlsData: ChannelControlDocument | ChannelControlDocument[] | null = get(window, '__DEVICES_MODULE_CHANNEL_CONTROLS__', null);

	if (ssrChannelControlsData !== null) {
		await channelControlsStore.insertData({
			data: ssrChannelControlsData,
		});
	}

	populated.value = true;
});
</script>
