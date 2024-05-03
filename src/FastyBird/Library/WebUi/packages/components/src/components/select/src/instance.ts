import type FbOptionGroup from './option-group.vue';
import type FbSelectDropdown from './select-dropdown.vue';
import type FbOption from './option.vue';
import type FbSelect from './select.vue';

export type FbSelectInstance = InstanceType<typeof FbSelect>;
export type FbSelectDropdownInstance = InstanceType<typeof FbSelectDropdown>;
export type FbOptionInstance = InstanceType<typeof FbOption>;
export type FbOptionGroupInstance = InstanceType<typeof FbOptionGroup>;
