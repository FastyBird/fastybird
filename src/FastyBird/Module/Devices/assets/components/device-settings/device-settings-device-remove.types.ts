import { IDevice } from '../../models/types';

export interface IDeviceSettingsDeviceRemoveProps {
	device: IDevice;
	callRemove?: boolean;
	transparentBg?: boolean;
}
