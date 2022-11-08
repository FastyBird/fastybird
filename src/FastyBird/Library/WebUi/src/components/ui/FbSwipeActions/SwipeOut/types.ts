export type TFbUiSwipeActionsOutDir = 'left' | 'right';

export interface IFbUiSwipeActionsOutProps {
	threshold?: number;
	revealed?: TFbUiSwipeActionsOutDir | boolean;
	disabled?: boolean;
}
