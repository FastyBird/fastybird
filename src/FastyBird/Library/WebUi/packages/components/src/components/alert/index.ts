import { withInstall } from '@fastybird/web-ui-utils';

import Alert from './src/alert.vue';

export const FbAlert = withInstall(Alert);

export default FbAlert;

export * from './src/alert';

export type { FbAlertInstance } from './src/instance';
