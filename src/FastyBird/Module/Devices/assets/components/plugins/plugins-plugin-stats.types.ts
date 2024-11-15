import { IDebugLog, IBridge, IConnectorData, IConnectorPlugin } from '../../types';

export interface IPluginsPluginStatsProps {
	plugin: IConnectorPlugin;
	connectorsData: IConnectorData[];
	alerts: IDebugLog[];
	bridges: IBridge[];
}
