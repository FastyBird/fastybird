import { withInstall } from '@fastybird/web-ui-utils';

import Switch from './src/switch.vue';

export const FbSwitch = withInstall(Switch);

export default FbSwitch;

export * from './src/switch';

export type { FbSwitchInstance } from './src/instance';
