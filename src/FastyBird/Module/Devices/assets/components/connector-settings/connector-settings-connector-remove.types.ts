import { IConnector } from '@/models/types';

export interface IConnectorSettingsConnectorRemoveProps {
	connector: IConnector;
	callRemove?: boolean;
	transparentBg?: boolean;
}
