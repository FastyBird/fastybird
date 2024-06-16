import { FormResultTypes, IConnectorData } from '../../types';

export interface IConnectorSettingsConnectorSettingsProps {
	connectorData: IConnectorData;
	loading: boolean;
	devicesLoading: boolean;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}

export interface IConnectorSettingsConnectorSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | number | boolean | Date | null | undefined }[];
	};
}
