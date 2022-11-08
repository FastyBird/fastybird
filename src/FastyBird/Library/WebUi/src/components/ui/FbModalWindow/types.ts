import {
    FbSizeTypes,
    FbUiModalLayoutTypes,
} from '@/types'

export interface IFbUiModalWindowProps {
    size?: FbSizeTypes;
    layout?: FbUiModalLayoutTypes;
    width?: string | number | null;
    showHeader?: boolean;
    showFooter?: boolean;
    rightBtnLabel?: string;
    showRightBtn?: boolean;
    leftBtnLabel?: string;
    showLeftBtn?: boolean;
    closeBtnLabel?: string;
    enableClosing?: boolean;
    transparentBg?: boolean;
    loader?: boolean;
    show?: boolean;
}
