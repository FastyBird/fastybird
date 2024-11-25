import { IConnector, IConnectorPlugin } from '../../types';

export interface IPluginsListPluginsProps {
	items: { plugin: IConnectorPlugin; connectors: IConnector[] }[];
}
