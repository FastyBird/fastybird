import { withInstall } from '@fastybird/web-ui-utils';

import Icon from './src/icon.vue';

export const FbIcon = withInstall(Icon);
export default FbIcon;

export * from './src/icon';

export type { FbIconInstance } from './src/instance';
