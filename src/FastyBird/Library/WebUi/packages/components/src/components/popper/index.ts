import { withInstall } from '@fastybird/web-ui-utils';

import Popper from './src/popper.vue';

import FbPopperArrow from './src/arrow.vue';
import FbPopperTrigger from './src/trigger.vue';
import FbPopperContent from './src/content.vue';

export { FbPopperArrow, FbPopperTrigger, FbPopperContent };

export const FbPopper = withInstall(Popper);

export default FbPopper;

export * from './src/popper';
export * from './src/trigger';
export * from './src/content';
export * from './src/arrow';
export * from './src/constants';

export type { Placement, Options } from '@popperjs/core';
