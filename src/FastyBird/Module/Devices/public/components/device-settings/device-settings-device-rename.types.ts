import { IDevice } from '@/models/devices/types';

export interface IDeviceSettingsDeviceRenameModel {
	identifier: string;
	name?: string;
	comment?: string;
}

export interface IDeviceSettingsDeviceRenameProps {
	device: IDevice | null;
	modelValue: IDeviceSettingsDeviceRenameModel;
	errors: {
		identifier?: string;
		name?: string;
		comment?: string;
	};
}
