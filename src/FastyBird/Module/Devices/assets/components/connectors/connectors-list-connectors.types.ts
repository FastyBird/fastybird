import { IConnector } from '../../models/types';

export interface IConnectorsListConnectorsProps {
	items: IConnector[];
}

export enum ConnectorsListConnectorsViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}
