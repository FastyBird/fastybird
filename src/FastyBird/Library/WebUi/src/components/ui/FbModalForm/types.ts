import {
  FbFormResultTypes,
  FbSizeTypes,
  FbUiModalLayoutTypes,
} from "../../../types";

export interface IFbUiModalFormProps {
  size?: FbSizeTypes;
  layout?: FbUiModalLayoutTypes;
  submitBtnLabel?: string;
  submitBtnShow?: boolean;
  cancelBtnLabel?: string;
  cancelBtnShow?: boolean;
  lockButtons?: boolean;
  lockSubmitButton?: boolean;
  transparentBg?: boolean;
  show?: boolean;
  state?: FbFormResultTypes;
  processingText?: string | null;
}
