import { IBridge, IConnectorData, IConnectorPlugin, IDebugLog } from '../../types';

export interface IPluginsPluginHeaderProps {
	plugin: IConnectorPlugin;
	connectorsData: IConnectorData[];
	alerts: IDebugLog[];
	bridges: IBridge[];
}
