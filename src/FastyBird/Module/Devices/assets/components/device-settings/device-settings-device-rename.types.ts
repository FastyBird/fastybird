import { IDevice } from '../../models/types';

export interface IDeviceSettingsDeviceRenameModel {
	identifier: string;
	name: string | null;
	comment: string | null;
}

export interface IDeviceSettingsDeviceRenameProps {
	device: IDevice | null;
	modelValue: IDeviceSettingsDeviceRenameModel;
}
