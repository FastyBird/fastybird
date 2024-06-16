import { IChannel } from '../../models/types';

export interface IChannelSettingsChannelRenameModel {
	identifier: string;
	name: string | null;
	comment: string | null;
}

export interface IChannelSettingsChannelRenameProps {
	channel: IChannel | null;
	modelValue: IChannelSettingsChannelRenameModel;
}
