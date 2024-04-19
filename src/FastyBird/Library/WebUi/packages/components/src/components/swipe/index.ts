import { withInstall } from '@fastybird/web-ui-utils';

import Swipe from './src/swipe.vue';

export const FbSwipe = withInstall(Swipe);

export default FbSwipe;

export * from './src/swipe';

export type { FbSwipeInstance } from './src/instance';
