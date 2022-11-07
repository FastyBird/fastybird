import { FbSizeTypes } from "../../../types";

import FbFormCheckboxesGroup from "../FbCheckboxesGroup/index.vue";

export interface IFbFormCheckboxProps {
  size: FbSizeTypes;
  name: string;
  option: string | number | boolean;
  modelValue: string | number | boolean;
  id: string | null;
  label: string | null;
  tabIndex: number | null;
  hasError: boolean;
  readonly: boolean;
  disabled: boolean;
  group: InstanceType<typeof FbFormCheckboxesGroup> | null;
}
