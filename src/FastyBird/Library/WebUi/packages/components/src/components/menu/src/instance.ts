import type Menu from './menu.vue';
import type MenuItem from './menu-item.vue';
import type MenuItemGroup from './menu-item-group.vue';
import type SubMenu from './sub-menu.vue';

export type MenuInstance = InstanceType<typeof Menu> & {
	open: (index: string) => void;
	close: (index: string) => void;
	handleResize: () => void;
};

export type MenuItemInstance = InstanceType<typeof MenuItem>;
export type MenuItemGroupInstance = InstanceType<typeof MenuItemGroup>;
export type SubMenuInstance = InstanceType<typeof SubMenu>;
