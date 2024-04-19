import { withInstall } from '@fastybird/web-ui-utils';

import Drawer from './src/drawer.vue';

export const FbDrawer = withInstall(Drawer);

export default FbDrawer;

export * from './src/hooks';
export * from './src/drawer';
export * from './src/constants';
