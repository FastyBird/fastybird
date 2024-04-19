import { withInstall } from '@fastybird/web-ui-utils';

import Badge from './src/badge.vue';

export const FbBadge = withInstall(Badge);

export default FbBadge;

export * from './src/badge';

export type { BadgeInstance } from './src/instance';
