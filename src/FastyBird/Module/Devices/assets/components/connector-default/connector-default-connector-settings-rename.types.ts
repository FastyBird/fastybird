import { IConnector, IConnectorForm } from '../../types';

export interface IConnectorDefaultConnectorSettingsRenameProps {
	connector: IConnector | null;
	modelValue: IConnectorForm['details'];
}
