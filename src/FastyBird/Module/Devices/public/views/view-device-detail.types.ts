import { IDevice } from '@/models/devices/types';

export interface IViewDeviceDetailProps {
	id: string;
	channelId?: string;
	devices: IDevice[];
}

export enum ViewDeviceDetailViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
