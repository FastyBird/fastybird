import {
  FbFormInputTypeTypes,
  FbFormOrientationTypes,
  FbSizeTypes,
} from '@/types';

export interface IFbFormInputProps {
  orientation: FbFormOrientationTypes;
  size: FbSizeTypes;
  name: string;
  id: string | null;
  label: string | null;
  type: FbFormInputTypeTypes;
  required: boolean;
  modelValue?: string | number;
  tabIndex: number | undefined;
  error: string | null;
  placeholder: string | undefined;
  disabled: boolean;
  readonly: boolean;
}
