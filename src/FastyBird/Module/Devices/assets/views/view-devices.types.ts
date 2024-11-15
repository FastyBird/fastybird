import { IChannel, IDevice } from '../types';

export interface IViewDevicesProps {
	id?: IDevice['id'];
	channelId?: IChannel['id'];
}
