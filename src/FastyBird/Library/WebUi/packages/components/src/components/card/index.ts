import { withInstall } from '@fastybird/web-ui-utils';

import Card from './src/card.vue';

export const FbCard = withInstall(Card);

export default FbCard;

export * from './src/card';

export type { FbCardInstance } from './src/instance';
