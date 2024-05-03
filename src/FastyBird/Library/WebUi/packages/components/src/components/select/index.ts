import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Select from './src/select.vue';
import Option from './src/option.vue';
import OptionGroup from './src/option-group.vue';

export const FbSelect = withInstall(Select, {
	Option,
	OptionGroup,
});

export default FbSelect;

export const FbOption = withNoopInstall(Option);

export const FbOptionGroup = withNoopInstall(OptionGroup);

export * from './src/constants';

export type { FbSelectInstance, FbSelectDropdownInstance, FbOptionInstance, FbOptionGroupInstance } from './src/instance';
