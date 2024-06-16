import { IDevice } from '../../models/types';
import { FormResultTypes, IChannelData } from '../../types';

export interface IChannelSettingsChannelSettingsProps {
	device: IDevice;
	channelData: IChannelData;
	loading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IChannelSettingsChannelSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | number | boolean | Date | null | undefined }[];
	};
}
