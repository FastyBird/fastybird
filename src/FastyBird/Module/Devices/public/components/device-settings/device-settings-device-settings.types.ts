import { FbFormResultTypes } from '@fastybird/web-ui-library';

import { IConnector } from '@/models/types';
import { IDeviceData } from '@/types';

export interface IDeviceSettingsDeviceSettingsProps {
	connector: IConnector;
	deviceData: IDeviceData;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}

export interface IDeviceSettingsDeviceSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | null }[];
	};
}

export enum DeviceSettingsDeviceSettingsViewTypes {
	NONE = 'none',
	ADD_STATIC_PARAMETER = 'addStaticParameter',
	ADD_DYNAMIC_PARAMETER = 'addDynamicParameter',
}
