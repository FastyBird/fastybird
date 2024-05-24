import { withInstall } from '@fastybird/web-ui-utils';

import Spinner from './src/spinner.vue';

export const FbSpinner = withInstall(Spinner);

export default FbSpinner;

export * from './src/spinner';

export type { SpinnerInstance } from './src/instance';
