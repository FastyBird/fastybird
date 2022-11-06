import {
  FbSizeTypes,
  FbUiModalLayoutTypes,
} from "../../../types";

export interface IFbUiModalInfoProps {
  size?: FbSizeTypes;
  layout?: FbUiModalLayoutTypes;
  enableClosing?: boolean;
  closeBtnLabel?: string;
  transparentBg?: boolean;
  show?: boolean;
}
