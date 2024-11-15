import { ComputedRef, Ref } from 'vue';
import { AxiosResponse } from 'axios';

import { ConnectionState } from '@fastybird/metadata-library';

import {
	DevicesFilter,
	FormResultTypes,
	IChannel,
	IChannelData,
	IChannelForm,
	IChannelProperty,
	IConnector,
	IConnectorData,
	IConnectorForm,
	IConnectorPlugin,
	IConnectorProperty,
	IDevice,
	IDeviceData,
	IDeviceForm,
	IDeviceProperty,
	IPropertyForm,
} from '../types';

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
	isMDDevice: ComputedRef<boolean>;
	isLGDevice: ComputedRef<boolean>;
	isXLDevice: ComputedRef<boolean>;
	isXXLDevice: ComputedRef<boolean>;
}

export interface UseChannel {
	channel: ComputedRef<IChannel | null>;
	channelData: ComputedRef<IChannelData | null>;
	isLoading: ComputedRef<boolean>;
	fetchChannel: (deviceId?: IDevice['id']) => Promise<void>;
}

export interface UseChannelActions {
	remove: (id: IChannel['id']) => Promise<void>;
}

export interface UseChannelForm {
	submit: (model: IChannelForm) => Promise<'added' | 'saved'>;
	clear: () => void;
	formResult: Ref<FormResultTypes>;
}

export interface UseChannelRoutes {
	isDetailRoute: ComputedRef<boolean>;
	isSettingsRoute: ComputedRef<boolean>;
	isChannelRoute: ComputedRef<boolean>;
}

export interface UseChannels {
	channels: ComputedRef<IChannel[]>;
	channelsData: ComputedRef<IChannelData[]>;
	areLoading: ComputedRef<boolean>;
	fetchChannels: () => Promise<void>;
}

export interface UseConnector {
	connector: ComputedRef<IConnector | null>;
	connectorData: ComputedRef<IConnectorData | null>;
	isLoading: ComputedRef<boolean>;
	fetchConnector: () => Promise<void>;
}

export interface UseConnectorActions {
	restart: (id: IConnector['id']) => void;
	start: (id: IConnector['id']) => void;
	stop: (id: IConnector['id']) => void;
	remove: (id: IConnector['id']) => void;
}

export interface UseConnectorForm {
	submit: (model: IConnectorForm) => Promise<'added' | 'saved'>;
	clear: () => void;
	formResult: Ref<FormResultTypes>;
}

export interface UseConnectorRoutes {
	isDetailRoute: ComputedRef<boolean>;
	isSettingsRoute: ComputedRef<boolean>;
	isConnectorRoute: ComputedRef<boolean>;
}

export interface UseConnectors {
	connectors: ComputedRef<IConnector[]>;
	connectorsData: ComputedRef<IConnectorData[]>;
	areLoading: ComputedRef<boolean>;
	fetchConnectors: () => Promise<void>;
}

export interface UseConnectorState {
	state: ComputedRef<ConnectionState>;
	isReady: ComputedRef<boolean>;
}

export interface UseDevice {
	device: ComputedRef<IDevice | null>;
	deviceData: ComputedRef<IDeviceData | null>;
	isLoading: ComputedRef<boolean>;
	fetchDevice: () => Promise<void>;
}

export interface UseDeviceActions {
	remove: (id: IDevice['id']) => Promise<void>;
}

export interface UseDeviceForm {
	submit: (model: IDeviceForm) => Promise<'added' | 'saved'>;
	clear: () => void;
	formResult: Ref<FormResultTypes>;
}

export interface UseDeviceRoutes {
	isDetailRoute: ComputedRef<boolean>;
	isSettingsRoute: ComputedRef<boolean>;
	isDeviceRoute: ComputedRef<boolean>;
}

export interface UseDevices {
	devices: ComputedRef<IDevice[]>;
	devicesData: ComputedRef<IDeviceData[]>;
	devicesDataPaginated: ComputedRef<IDeviceData[]>;
	totalRows: ComputedRef<number>;
	areLoading: ComputedRef<boolean>;
	loaded: ComputedRef<boolean>;
	fetchDevices: () => Promise<void>;
	filters: DevicesFilter;
	paginateSize: Ref<number>;
	paginatePage: Ref<number>;
	sortDir: Ref<'asc' | 'desc'>;
	resetFilter: () => void;
}

export interface UseDeviceState {
	state: ComputedRef<ConnectionState>;
	isReady: ComputedRef<boolean>;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UsePluginActions {
	remove: (type: IConnectorPlugin['type']) => Promise<void>;
}

export interface UsePropertyActions {
	remove: (id: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']) => void;
}

export interface UsePropertyForm {
	submit: (model: IPropertyForm) => Promise<'added' | 'saved'>;
	clear: () => void;
	formResult: Ref<FormResultTypes>;
}

export interface UseUuid {
	generate: () => string;
	validate: (uuid: string) => boolean;
}
