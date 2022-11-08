import { FbUiModalLayoutTypes } from '@/types';

export interface IFbUiModalHeaderProps {
	layout?: FbUiModalLayoutTypes;
	leftBtnLabel?: string;
	showLeftBtn?: boolean;
	rightBtnLabel?: string;
	showRightBtn?: boolean;
	closeBtnLabel?: string;
	enableClosing?: boolean;
}
