import { withInstallFunction } from '@fastybird/web-ui-utils';

import Message from './src/method';

export const FbMessage = withInstallFunction(Message, '$message');

export default FbMessage;

export * from './src/message';
