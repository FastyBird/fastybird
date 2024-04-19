import { withInstall } from '@fastybird/web-ui-utils';

import BottomNavigation from './src/bottom-navigation.vue';
import BottomNavigationHeading from './src/heading.vue';

export const FbBottomNavigation = withInstall(BottomNavigation);
export const FbBottomNavigationHeading = withInstall(BottomNavigationHeading);

export default FbBottomNavigation;

export * from './src/bottom-navigation';
export * from './src/heading';

export type { FbBottomNavigationInstance, FbBottomNavigationHeadingInstance } from './src/instance';
