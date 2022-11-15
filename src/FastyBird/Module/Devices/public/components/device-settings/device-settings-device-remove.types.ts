import { IDevice } from '@/models/devices/types';

export interface IDeviceSettingsDeviceRemoveProps {
	device: IDevice;
	callRemove?: boolean;
	transparentBg?: boolean;
}
