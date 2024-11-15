import { IDevice, IDeviceForm } from '../../types';

export interface IDeviceDefaultDeviceSettingsRenameProps {
	device: IDevice | null;
	modelValue: IDeviceForm['details'];
}
