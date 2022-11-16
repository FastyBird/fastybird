import { IDevice } from '@/models/types';

export interface IDevicesListDevicesProps {
	items: IDevice[];
}

export enum DevicesListDevicesViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
