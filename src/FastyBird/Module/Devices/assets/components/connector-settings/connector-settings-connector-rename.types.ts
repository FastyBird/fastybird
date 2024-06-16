import { IConnector } from '../../models/connectors/types';

export interface IConnectorSettingsConnectorRenameModel {
	identifier: string;
	name: string | null;
	comment: string | null;
}

export interface IConnectorSettingsConnectorRenameProps {
	connector: IConnector | null;
	modelValue: IConnectorSettingsConnectorRenameModel;
}
