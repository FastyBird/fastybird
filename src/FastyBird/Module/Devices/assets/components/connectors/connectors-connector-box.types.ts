import { IDebugLog, IBridge, IConnectorData, IService } from '../../types';

export interface IConnectorsConnectorBoxProps {
	connectorData: IConnectorData;
	alerts: IDebugLog[];
	bridges: IBridge[];
	service: IService | null;
}
