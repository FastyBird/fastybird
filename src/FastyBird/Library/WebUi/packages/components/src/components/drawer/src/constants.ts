import type { InjectionKey, Ref } from 'vue';

import type { UseNamespaceReturn } from '@fastybird/web-ui-hooks';

export type DrawerContext = {
	drawerRef: Ref<HTMLElement | undefined>;
	headerRef: Ref<HTMLElement | undefined>;
	footerRef: Ref<HTMLElement | undefined>;
	bodyId: Ref<string>;
	ns: UseNamespaceReturn;
	rendered: Ref<boolean>;
};

export const drawerContextKey: InjectionKey<DrawerContext> = Symbol('drawerContextKey');
