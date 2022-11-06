import {
  FbFormInputTypeTypes,
  FbFormOrientationTypes,
  FbSizeTypes,
} from '../../../types';

export interface IFbFormInputProps {
  orientation: FbFormOrientationTypes;
  size: FbSizeTypes;
  name: string;
  id: string | null;
  label: string | null;
  type: FbFormInputTypeTypes;
  required: boolean;
  modelValue: string | number | null;
  tabIndex: number | null;
  error: string | null;
  placeholder: string | null;
  disabled: boolean;
  readonly: boolean;
}
