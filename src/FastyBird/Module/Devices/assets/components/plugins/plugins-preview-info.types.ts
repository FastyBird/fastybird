import { IConnector, IConnectorPlugin } from '../../types';

export interface IPluginsPreviewInfoProps {
	items: { plugin: IConnectorPlugin; connectors: IConnector[] }[];
}
