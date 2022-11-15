import { IConnector } from '@/models/connectors/types';

export interface IConnectorSettingsConnectorRemoveProps {
	connector: IConnector;
	callRemove?: boolean;
	transparentBg?: boolean;
}
