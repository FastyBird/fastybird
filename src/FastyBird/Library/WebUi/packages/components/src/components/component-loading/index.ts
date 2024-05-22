import { withInstall } from '@fastybird/web-ui-utils';

import ComponentLoading from './src/component-loading.vue';

export const FbComponentLoading = withInstall(ComponentLoading);

export default FbComponentLoading;

export * from './src/component-loading';

export type { ComponentLoadingInstance } from './src/instance';
