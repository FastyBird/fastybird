import { FbSizeTypes } from '../../../types';

import FbFormRadioButtonsGroup from "@/components/forms/FbFormRadioButtonsGroup/index.vue";

export interface IFbFormRadioButtonProps {
  size: FbSizeTypes;
  name: string;
  option: string | number | boolean;
  modelValue: string | number | boolean | null;
  id: string | null;
  label: string | null;
  tabIndex: number | null;
  hasError: boolean;
  readonly: boolean;
  disabled: boolean;
  group: InstanceType<typeof FbFormRadioButtonsGroup> | null;
}
