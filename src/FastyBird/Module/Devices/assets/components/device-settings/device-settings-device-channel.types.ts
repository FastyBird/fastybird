import { IDevice } from '../../models/types';
import { IChannelData } from '../../types';

export interface IDeviceSettingsDevicePropertyProps {
	device: IDevice;
	channelData: IChannelData;
}

export enum DeviceSettingsDeviceChannelViewTypes {
	NONE = 'none',
	RESET = 'reset',
	REMOVE = 'remove',
}
