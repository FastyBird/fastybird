import { withInstall } from '@fastybird/web-ui-utils';

import Row from './src/row.vue';

export const FbRow = withInstall(Row);

export default FbRow;

export * from './src/row';
export * from './src/constants';

export type { FbRowInstance } from './src/instance';
