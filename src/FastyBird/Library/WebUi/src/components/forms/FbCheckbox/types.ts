import { FbSizeTypes } from "@/types";

import FbFormCheckboxesGroup from "../FbCheckboxesGroup/index.vue";

export interface IFbFormCheckboxProps {
  size: FbSizeTypes;
  name: string;
  option: string | number | boolean;
  modelValue?: string | number | boolean | Array<string | number | boolean>;
  id: string | null;
  label: string | null;
  tabIndex: number | undefined;
  hasError: boolean;
  readonly: boolean;
  disabled: boolean;
  group: InstanceType<typeof FbFormCheckboxesGroup> | null;
}
