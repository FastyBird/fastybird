import {
  FbFormOrientationTypes,
  FbSizeTypes,
} from '@/types';

export interface IFbFormTextAreaProps {
  orientation: FbFormOrientationTypes;
  size: FbSizeTypes;
  name: string;
  modelValue?: string | number;
  id: string | null;
  label: string | null;
  rows: number;
  required: boolean;
  tabIndex: number | undefined;
  error: string | null;
  placeholder: string | undefined;
  readonly: boolean;
  disabled: boolean;
}
