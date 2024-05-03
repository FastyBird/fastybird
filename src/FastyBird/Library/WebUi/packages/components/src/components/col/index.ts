import { withInstall } from '@fastybird/web-ui-utils';

import Col from './src/col.vue';

export const FbCol = withInstall(Col);

export default FbCol;

export * from './src/col';

export type { FbColInstance } from './src/instance';
