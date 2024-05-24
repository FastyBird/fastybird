import { withInstall } from '@fastybird/web-ui-utils';

import Swipe from './src/swipe.vue';
import SwipeItem from './src/item.vue';

export const FbSwipe = withInstall(Swipe);
export const FbSwipeItem = withInstall(SwipeItem);

export default FbSwipe;

export * from './src/swipe';
export * from './src/item';

export type { SwipeInstance, SwipeItemInstance } from './src/instance';
