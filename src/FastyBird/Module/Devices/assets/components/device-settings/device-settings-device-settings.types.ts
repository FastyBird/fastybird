import { IConnector } from '../../models/types';
import { FormResultTypes, IDeviceData } from '../../types';

export interface IDeviceSettingsDeviceSettingsProps {
	connector: IConnector;
	deviceData: IDeviceData;
	loading: boolean;
	channelsLoading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IDeviceSettingsDeviceSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | number | boolean | Date | null | undefined }[];
	};
}
