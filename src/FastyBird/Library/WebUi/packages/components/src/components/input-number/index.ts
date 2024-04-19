import { withInstall } from '@fastybird/web-ui-utils';

import InputNumber from './src/input-number.vue';

export const FbInputNumber = withInstall(InputNumber);

export default FbInputNumber;

export * from './src/input-number';

export type { FbInputNumberInstance } from './src/instance';
