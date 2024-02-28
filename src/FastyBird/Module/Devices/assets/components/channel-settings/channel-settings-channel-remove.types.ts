import { IChannel, IDevice } from '../../models/types';

export interface IChannelSettingsChannelRemoveProps {
	device: IDevice;
	channel: IChannel;
	callRemove?: boolean;
	transparentBg?: boolean;
}
