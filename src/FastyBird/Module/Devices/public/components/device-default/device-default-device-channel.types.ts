import { IDevice } from '@/models/devices/types';
import { IDeviceControl } from '@/models/devices-controls/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IChannelData } from '@/types';

export interface IDeviceDefaultDeviceChannelProps {
	device: IDevice;
	deviceControls: IDeviceControl[];
	deviceProperties: IDeviceProperty[];
	channelData: IChannelData;
	editMode?: boolean;
}

export enum DeviceDefaultDeviceChannelViewTypes {
	NONE = 'none',
	ADD_PARAMETER = 'addParameter',
}
