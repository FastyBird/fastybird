import {
	IChannel,
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDevice,
	IDeviceControl,
	IDeviceAttribute,
	IDeviceProperty,
} from '@/models/types';

export * from '@/composables/types';
export * from '@/models/types';

export interface IChannelData {
	channel: IChannel;
	properties: IChannelProperty[];
	controls: IChannelControl[];
}

export interface IDeviceData {
	device: IDevice;
	properties: IDeviceProperty[];
	controls: IDeviceControl[];
	attributes: IDeviceAttribute[];
	channels: IChannelData[];
}

export interface IConnectorData {
	connector: IConnector;
	properties: IConnectorProperty[];
	controls: IConnectorControl[];
	devices: IDeviceData[];
}
