import { FbMenuItemTypes } from '@/types';

export interface IFbLayoutNavigationItemProps {
	type: FbMenuItemTypes;
	label: string;
	link: string | null;
}
