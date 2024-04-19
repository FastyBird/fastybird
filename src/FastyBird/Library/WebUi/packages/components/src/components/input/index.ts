import { withInstall } from '@fastybird/web-ui-utils';

import Input from './src/input.vue';

export const FbInput = withInstall(Input);

export default FbInput;

export * from './src/input';

export type { InputInstance } from './src/instance';
