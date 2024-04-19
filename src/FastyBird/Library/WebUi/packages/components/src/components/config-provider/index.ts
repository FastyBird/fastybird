import { withInstall } from '@fastybird/web-ui-utils';

import ConfigProvider from './src/config-provider';

export const FbConfigProvider = withInstall(ConfigProvider);

export default FbConfigProvider;

export * from './src/config-provider';
export * from './src/config-provider-props';
export * from './src/constants';
export * from './src/hooks/use-global-config';
