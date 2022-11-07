import {
  FbFormOrientationTypes,
  FbSizeTypes,
} from '../../../types';

export interface IFbFormTextAreaProps {
  orientation: FbFormOrientationTypes;
  size: FbSizeTypes;
  name: string;
  modelValue: string | number | null;
  id: string | null;
  label: string | null;
  rows: number;
  required: boolean;
  tabIndex: number | null;
  error: string | null;
  placeholder: string | null;
  readonly: boolean;
  disabled: boolean;
}
