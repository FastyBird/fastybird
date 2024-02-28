import { IChannel } from '../../models/types';

export interface IChannelSettingsChannelRenameModel {
	identifier: string;
	name?: string;
	comment?: string;
}

export interface IChannelSettingsChannelRenameProps {
	channel: IChannel;
	modelValue: IChannelSettingsChannelRenameModel;
	errors: {
		identifier?: string;
		name?: string;
		comment?: string;
	};
}
