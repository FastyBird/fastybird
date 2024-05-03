import { withInstall } from '@fastybird/web-ui-utils';

import Modal from './src/modal.vue';

export const FbModal = withInstall(Modal);
export default FbModal;

export * from './src/hooks';
export * from './src/modal';
export * from './src/constants';
