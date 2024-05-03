import { withInstall } from '@fastybird/web-ui-utils';

import Divider from './src/divider.vue';

export const FbDivider = withInstall(Divider);

export default FbDivider;

export * from './src/divider';

export type { FbDividerInstance } from './src/instance';
