import { IChannel, IChannelForm } from '../../types';

export interface IChannelDefaultChannelSettingsRenameProps {
	channel: IChannel | null;
	modelValue: IChannelForm['details'];
}
