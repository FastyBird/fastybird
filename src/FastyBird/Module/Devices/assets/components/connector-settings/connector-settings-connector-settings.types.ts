import { FbFormResultTypes } from '@fastybird/web-ui-library';

import { IConnectorData } from '@/types';

export interface IConnectorSettingsConnectorSettingsProps {
	connectorData: IConnectorData;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}

export interface IConnectorSettingsConnectorSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | null }[];
	};
}

export enum ConnectorSettingsConnectorSettingsViewTypes {
	NONE = 'none',
	ADD_STATIC_PARAMETER = 'addStaticParameter',
	ADD_DYNAMIC_PARAMETER = 'addDynamicParameter',
}
