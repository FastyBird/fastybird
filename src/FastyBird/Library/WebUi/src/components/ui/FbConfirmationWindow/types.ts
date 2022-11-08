import {
    FbSizeTypes,
    FbUiConfirmationWindowPrimaryButtonTypes,
    FbUiVariantTypes,
} from '@/types'

export interface IFbUiConfirmationWindowProps {
    size?: FbSizeTypes;
    primaryButton?: FbUiConfirmationWindowPrimaryButtonTypes;
    variant?: FbUiVariantTypes;
    showYes?: boolean;
    yesBtnLabel?: string;
    showNo?: boolean;
    noBtnLabel?: string;
    enableClosing?: boolean;
    transparentBg?: boolean;
    show?: boolean;
}
