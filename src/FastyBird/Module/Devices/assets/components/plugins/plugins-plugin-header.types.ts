import { IDebugLog, IBridge, IConnectorData, IConnectorPlugin } from '../../types';

export interface IPluginsPluginHeaderProps {
	plugin: IConnectorPlugin;
	connectorsData: IConnectorData[];
	alerts: IDebugLog[];
	bridges: IBridge[];
}
