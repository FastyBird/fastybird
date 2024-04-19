import { withInstall } from '@fastybird/web-ui-utils';

import Scrollbar from './src/scrollbar.vue';

export const FbScrollbar = withInstall(Scrollbar);

export default FbScrollbar;

export * from './src/util';
export * from './src/scrollbar';
export * from './src/thumb';
export * from './src/constants';

export type { FbScrollbarInstance, FbBarInstance, FbThumbInstance } from './src/instance';
