import { IConnector } from '../../models/types';

export interface IConnectorsIconProps {
	connector: IConnector;
	withState?: boolean;
	size?: number | string;
}
