import {FbMenuItemTypes} from "../../../../types";

export interface IFbLayoutTabsItemProps {
    type: FbMenuItemTypes;
    label: string | null;
    link: string | null;
    active: boolean;
}
