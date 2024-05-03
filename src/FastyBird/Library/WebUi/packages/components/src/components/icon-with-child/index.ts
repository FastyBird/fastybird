import { withInstall } from '@fastybird/web-ui-utils';

import Icon from './src/icon-with-child.vue';

export const FbIconWithChild = withInstall(Icon);

export default FbIconWithChild;

export * from './src/icon-with-child';

export type { FbIconWithChildInstance } from './src/instance';
