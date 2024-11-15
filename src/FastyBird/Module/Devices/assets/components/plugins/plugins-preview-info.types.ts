import { IConnectorPlugin, IConnector } from '../../types';

export interface IPluginsPreviewInfoProps {
	items: { plugin: IConnectorPlugin; connectors: IConnector[] }[];
}
