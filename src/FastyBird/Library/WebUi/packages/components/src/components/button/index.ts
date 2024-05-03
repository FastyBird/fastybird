import { withInstall } from '@fastybird/web-ui-utils';

import Button from './src/button.vue';

export const FbButton = withInstall(Button);

export default FbButton;

export * from './src/button';

export type { FbButtonInstance } from './src/instance';
