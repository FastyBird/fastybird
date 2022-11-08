import {
    FbSizeTypes,
    FbUiVariantTypes,
} from "@/types";

export interface IFbUiSwitchElementProps {
    status?: boolean;
    disabled?: boolean;
    variant?: FbUiVariantTypes;
    size?: FbSizeTypes;
}
