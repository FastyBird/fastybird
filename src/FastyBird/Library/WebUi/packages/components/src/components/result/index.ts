import { withInstall } from '@fastybird/web-ui-utils';

import Result from './src/result.vue';

export const FbResult = withInstall(Result);

export default FbResult;

export * from './src/result';

export type { FbResultInstance } from './src/instance';
