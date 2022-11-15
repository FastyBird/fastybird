import { IDevice } from '@/models/devices/types';
import { IDeviceProperty } from '@/models/devices-properties/types';
import { IDeviceControl } from '@/models/devices-controls/types';
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
