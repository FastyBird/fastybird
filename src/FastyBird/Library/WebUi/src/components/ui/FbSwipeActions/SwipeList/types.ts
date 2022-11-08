import { TFbUiSwipeActionsOutDir } from '../types';

export interface IFbUiSwipeActionsListProps<TItem> {
	items: TItem[];
	threshold?: number;
	revealed?: { [key: number]: TFbUiSwipeActionsOutDir };
	disabled?: boolean;
	itemDisabled?: (item: TItem) => boolean;
}
