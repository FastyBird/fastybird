import { IDevice } from '../../models/types';

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
