import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Menu from './src/menu.vue';
import MenuItem from './src/menu-item.vue';
import MenuItemGroup from './src/menu-item-group.vue';
import SubMenu from './src/sub-menu.vue';

export const FbMenu = withInstall(Menu, {
	MenuItem,
	MenuItemGroup,
	SubMenu,
});

export default FbMenu;
export const FbMenuItem = withNoopInstall(MenuItem);
export const FbMenuItemGroup = withNoopInstall(MenuItemGroup);
export const FbSubMenu = withNoopInstall(SubMenu);

export * from './src/menu';
export * from './src/menu-item';
export * from './src/menu-item-group';
export * from './src/sub-menu';
export * from './src/types';
export * from './src/instance';
