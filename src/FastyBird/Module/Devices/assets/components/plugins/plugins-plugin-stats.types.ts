import { IBridge, IConnectorData, IConnectorPlugin, IDebugLog } from '../../types';

export interface IPluginsPluginStatsProps {
	plugin: IConnectorPlugin;
	connectorsData: IConnectorData[];
	alerts: IDebugLog[];
	bridges: IBridge[];
}
