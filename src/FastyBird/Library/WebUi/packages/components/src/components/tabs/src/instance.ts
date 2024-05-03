import { TabPaneName } from './tabs';

import type FbTabBar from './tab-bar.vue';
import type FbTabNav from './tab-nav.vue';
import type FbTabNavItem from './tab-nav-item.vue';
import type FbTabPane from './tab-pane.vue';
import type FbTabs from './tabs.vue';

export type FbTabBarInstance = InstanceType<typeof FbTabBar>;

export type FbTabNavInstance = InstanceType<typeof FbTabNav> & {
	scrollToActiveTab: () => Promise<void>;
	removeFocus: () => void;
};

export type FbTabNavItemInstance = InstanceType<typeof FbTabNavItem>;

export type FbTabPaneInstance = InstanceType<typeof FbTabPane>;

export type FbTabsInstance = InstanceType<typeof FbTabs> & {
	currentName: TabPaneName;
};
