/* eslint-disable @typescript-eslint/ban-types */

import { Pinia } from 'pinia';
import { ComponentOptionsMixin, DefineComponent } from 'vue';
import { Router } from 'vue-router';
import { I18n } from 'vue-i18n';

import { Client } from '@fastybird/vue-wamp-v1';

import {
	connectorDevicesEmits,
	deviceChannelsEmits,
	editChannelEmits,
	editConnectorEmits,
	editDeviceEmits,
	IChannelDetailProps,
	IConnectorDetailProps,
	IConnectorDevicesProps,
	IDeviceChannelsProps,
	IDeviceDetailProps,
	IEditChannelProps,
	IEditConnectorProps,
	IEditDeviceProps,
} from './components';

export interface IConnectorOptions {
	router?: Router;
	store: Pinia;
	wsClient?: Client;
	i18n?: I18n;
}

export interface IConnectorPlugin {
	type: string;
	source: string;
	name: string;
	description: string;
	icon?: {
		small?: string;
		large?: string;
	};
	links: {
		documentation: string;
		devDocumentation: string;
		bugsTracking: string;
	};
	components: {
		connectorDetail?: DefineComponent<IConnectorDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		connectorDevices?: DefineComponent<
			IConnectorDevicesProps,
			{},
			{},
			{},
			{},
			ComponentOptionsMixin,
			ComponentOptionsMixin,
			typeof connectorDevicesEmits
		>;
		addConnector?: DefineComponent<IEditConnectorProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editConnectorEmits>;
		editConnector?: DefineComponent<IEditConnectorProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editConnectorEmits>;
		deviceDetail?: DefineComponent<IDeviceDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		deviceChannels?: DefineComponent<IDeviceChannelsProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof deviceChannelsEmits>;
		addDevice?: DefineComponent<IEditDeviceProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editDeviceEmits>;
		editDevice?: DefineComponent<IEditDeviceProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editDeviceEmits>;
		channelDetail?: DefineComponent<IChannelDetailProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, {}>;
		addChannel?: DefineComponent<IEditChannelProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editChannelEmits>;
		editChannel?: DefineComponent<IEditChannelProps, {}, {}, {}, {}, ComponentOptionsMixin, ComponentOptionsMixin, typeof editChannelEmits>;
	};
	core: boolean;
}
