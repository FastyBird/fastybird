import { FbMenuItemTypes } from "@/types";

export interface IFbLayoutUserMenuItemProps {
  type: FbMenuItemTypes;
  label: string | null;
  link: string | null;
}
