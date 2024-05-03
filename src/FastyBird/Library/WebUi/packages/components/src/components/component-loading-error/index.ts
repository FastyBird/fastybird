import { withInstall } from '@fastybird/web-ui-utils';

import ComponentLoadingError from './src/component-loading-error.vue';

export const FbComponentLoadingError = withInstall(ComponentLoadingError);

export default FbComponentLoadingError;

export * from './src/component-loading-error';

export type { FbComponentLoadingErrorInstance } from './src/instance';
