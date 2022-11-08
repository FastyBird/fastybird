import {FbMenuItemTypes} from '@/types'

export interface IFbLayoutHeaderButtonProps {
    actionType: FbMenuItemTypes;
    action: string | { [key: string]: any } | null;
    small: boolean;
    left: boolean;
    right: boolean;
    teleport: boolean;
}
