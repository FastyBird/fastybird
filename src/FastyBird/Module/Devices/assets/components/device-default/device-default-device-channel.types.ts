import { IDevice, IDeviceControl, IDeviceProperty } from '../../models/types';
import { IChannelData } from '../../types';

export interface IDeviceDefaultDeviceChannelProps {
	device: IDevice;
	deviceControls: IDeviceControl[];
	deviceProperties: IDeviceProperty[];
	channelData: IChannelData;
	editMode?: boolean;
}
