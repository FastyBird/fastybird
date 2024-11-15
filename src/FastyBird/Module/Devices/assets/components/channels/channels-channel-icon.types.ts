import { IChannel, IDevice } from '../../models/types';

export interface IChannelsChannelIconProps {
	device: IDevice;
	channel: IChannel;
	withState?: boolean;
	size?: number | string;
}
