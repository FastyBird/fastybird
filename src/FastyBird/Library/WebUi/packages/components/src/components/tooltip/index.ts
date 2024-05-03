import { withInstall } from '@fastybird/web-ui-utils';

import Tooltip from './src/tooltip.vue';

export const FbTooltip = withInstall(Tooltip);

export * from './src/tooltip';
export * from './src/trigger';
export * from './src/content';
export * from './src/constants';

export default FbTooltip;

export type { FbTooltipInstance, FbTooltipContentInstance } from './src/instance';
