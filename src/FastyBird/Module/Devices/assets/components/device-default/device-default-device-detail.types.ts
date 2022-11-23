import { IDevice, IDeviceControl, IDeviceProperty } from '@/models/types';
import { IChannelData } from '@/types';

export interface IDevicesDeviceDetailDefaultProps {
	deviceData: {
		device: IDevice;
		properties: IDeviceProperty[];
		controls: IDeviceControl[];
		channels: IChannelData[];
	};
	editMode?: boolean;
}
