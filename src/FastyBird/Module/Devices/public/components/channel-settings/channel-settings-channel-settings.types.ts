import { IDevice } from '@/models/devices/types';
import { IChannelData } from '@/types';
import { FbFormResultTypes } from '@fastybird/web-ui-library';

export interface IChannelSettingsChannelSettingsProps {
	device: IDevice;
	channelData: IChannelData;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}

export interface IChannelSettingsChannelSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | null }[];
	};
}

export enum ChannelSettingsChannelSettingsViewTypes {
	NONE = 'none',
	ADD_STATIC_PARAMETER = 'addStaticParameter',
	ADD_DYNAMIC_PARAMETER = 'addDynamicParameter',
}
