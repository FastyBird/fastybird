import { withInstall } from '@fastybird/web-ui-utils';

import CheckTag from './src/check-tag.vue';

export const FbCheckTag = withInstall(CheckTag);

export default FbCheckTag;

export * from './src/check-tag';

export type { FbCheckTagInstance } from './src/instance';
