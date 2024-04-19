import { withInstall } from '@fastybird/web-ui-utils';

import LoadingBox from './src/loading-box.vue';

export const FbLoadingBox = withInstall(LoadingBox);
export default FbLoadingBox;

export * from './src/loading-box';

export type { FbLoadingBoxInstance } from './src/instance';
