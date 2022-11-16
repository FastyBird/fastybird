import { IChannel, IChannelControl, IDevice } from '@/models/types';

export interface IChannelSettingsChannelResetProps {
	device: IDevice;
	channel: IChannel;
	control: IChannelControl;
	transparentBg?: boolean;
}
