import { IDevice } from '@/models/types';

export interface IViewDeviceDetailProps {
	id: string;
	channelId?: string;
	devices: IDevice[];
}

export enum ViewDeviceDetailViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
