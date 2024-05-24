import { withInstall } from '@fastybird/web-ui-utils';

import MediaItem from './src/media-item.vue';

export const FbMediaItem = withInstall(MediaItem);
export default FbMediaItem;

export type { MediaItemInstance } from './src/instance';
