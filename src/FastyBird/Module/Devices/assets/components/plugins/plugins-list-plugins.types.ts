import { IConnectorPlugin, IConnector } from '../../types';

export interface IPluginsListPluginsProps {
	items: { plugin: IConnectorPlugin; connectors: IConnector[] }[];
}
