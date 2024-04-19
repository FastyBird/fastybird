import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Checkbox from './src/checkbox.vue';
import CheckboxButton from './src/checkbox-button.vue';
import CheckboxGroup from './src/checkbox-group.vue';

export const FbCheckbox = withInstall(Checkbox, {
	CheckboxButton,
	CheckboxGroup,
});

export default FbCheckbox;

export const FbCheckboxButton = withNoopInstall(CheckboxButton);
export const FbCheckboxGroup = withNoopInstall(CheckboxGroup);

export * from './src/checkbox-group';
export * from './src/checkbox';
export * from './src/constants';

export type { FbCheckboxInstance, FbCheckboxGroupInstance } from './src/instance';
