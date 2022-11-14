import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';

export interface IChannelSettingsChannelRemoveProps {
	device: IDevice;
	channel: IChannel;
	callRemove?: boolean;
	transparentBg?: boolean;
}
