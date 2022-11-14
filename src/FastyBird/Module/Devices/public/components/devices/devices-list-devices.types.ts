import { IDevice } from '@/models/devices/types';

export interface IDevicesListDevicesProps {
	items: IDevice[];
}

export enum DevicesListDevicesViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
