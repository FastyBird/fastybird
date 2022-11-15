import { IDevice } from '@/models/devices/types';
import { IChannel } from '@/models/channels/types';
import { IChannelControl } from '@/models/channels-controls/types';

export interface IChannelSettingsChannelResetProps {
	device: IDevice;
	channel: IChannel;
	control: IChannelControl;
	transparentBg?: boolean;
}
