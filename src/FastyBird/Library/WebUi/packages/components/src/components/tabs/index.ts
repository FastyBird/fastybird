import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Tabs from './src/tabs.vue';
import TabPane from './src/tab-pane.vue';

export const FbTabs = withInstall(Tabs, {
	TabPane,
});

export const FbTabPane = withNoopInstall(TabPane);

export default FbTabs;

export * from './src/tabs';
export * from './src/tab-bar';
export * from './src/tab-nav';
export * from './src/tab-pane';
export * from './src/constants';
