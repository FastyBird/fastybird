import { withInstall } from '@fastybird/web-ui-utils';

import List from './src/list.vue';
import Item from './src/item.vue';

export const FbList = withInstall(List);
export default FbList;

export const FbListItem = withInstall(Item);

export * from './src/item';

export type { ListInstance, ListItemInstance } from './src/instance';
