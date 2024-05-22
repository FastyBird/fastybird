import { withInstall } from '@fastybird/web-ui-utils';

import DialogHeader from './src/dialog-header.vue';
import DialogFooter from './src/dialog-footer.vue';

export const FbDialogHeader = withInstall(DialogHeader);
export const FbDialogFooter = withInstall(DialogFooter);

export * from './src/dialog-header';
export * from './src/dialog-footer';

export type { DialogHeaderInstance, DialogFooterInstance } from './src/instance';
