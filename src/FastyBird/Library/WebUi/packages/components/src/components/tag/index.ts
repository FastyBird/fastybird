import { withInstall } from '@fastybird/web-ui-utils';

import Tag from './src/tag.vue';

export const FbTag = withInstall(Tag);

export default FbTag;

export * from './src/tag';

export type { FbTagInstance } from './src/instance';
