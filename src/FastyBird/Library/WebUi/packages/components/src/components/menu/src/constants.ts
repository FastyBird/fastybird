import { MenuItemClicked, MenuItemRegistered } from './types';

import type { InjectionKey, Ref } from 'vue';
import type { MenuProps } from './menu';

export interface MenuContext {
	openedMenus: string[];
	items: Record<string, MenuItemRegistered>;
	subMenus: Record<string, MenuItemRegistered>;
	activeIndex?: string;
	isMenuPopup: boolean;
	props: MenuProps;

	addMenuItem: (item: MenuItemRegistered) => void;
	removeMenuItem: (item: MenuItemRegistered) => void;
	addSubMenu: (item: MenuItemRegistered) => void;
	removeSubMenu: (item: MenuItemRegistered) => void;

	openMenu: (index: string, indexPath: string[]) => void;
	closeMenu: (index: string, indexPath: string[]) => void;

	handleMenuItemClick: (item: MenuItemClicked) => void;
	handleSubMenuClick: (subMenu: MenuItemRegistered) => void;
}

export const menuContextKey: InjectionKey<MenuContext> = Symbol('menuContextKey');

export interface SubMenuContext {
	addSubMenu: (item: MenuItemRegistered) => void;
	removeSubMenu: (item: MenuItemRegistered) => void;
	handleMouseleave?: (deepDispatch: boolean) => void;
	mouseInChild: Ref<boolean>;
	level: number;
}

export const subMenuContextKey: InjectionKey<SubMenuContext> = Symbol('subMenuContextKey');
