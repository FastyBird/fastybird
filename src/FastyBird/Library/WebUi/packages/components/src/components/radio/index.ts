import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Radio from './src/radio.vue';
import RadioButton from './src/radio-button.vue';
import RadioGroup from './src/radio-group.vue';

export const FbRadio = withInstall(Radio, {
	RadioButton,
	RadioGroup,
});

export default FbRadio;

export const FbRadioGroup = withNoopInstall(RadioGroup);

export const FbRadioButton = withNoopInstall(RadioButton);

export * from './src/radio';
export * from './src/radio-group';
export * from './src/radio-button';
export * from './src/constants';

export type { FbRadioInstance, FbRadioButtonInstance, FbRadioGroupInstance } from './src/instance';
