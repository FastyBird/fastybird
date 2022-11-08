import {
    FbSizeTypes,
    FbUiButtonActionsTypes,
    FbUiButtonButtonTypes,
    FbUiButtonVariantTypes,
} from "@/types";

export interface IFbUiButtonProps {
    action?: string | { [key: string]: any } | null;
    actionType?: FbUiButtonActionsTypes;
    type?: FbUiButtonButtonTypes;
    size?: FbSizeTypes;
    variant?: FbUiButtonVariantTypes;
    block?: boolean;
    uppercase?: boolean;
    pill?: boolean;
    thick?: boolean;
    active?: boolean;
    disabled?: boolean;
    loading?: boolean;
}
