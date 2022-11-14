import { IConnector } from '@/models/connectors/types';

export interface IConnectorsListConnectorsProps {
	items: IConnector[];
}

export enum ConnectorsListConnectorsViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
