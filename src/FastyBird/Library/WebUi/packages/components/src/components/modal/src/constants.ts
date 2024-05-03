import type { CSSProperties, ComputedRef, InjectionKey, Ref } from 'vue';

import type { UseNamespaceReturn } from '@fastybird/web-ui-hooks';

export type ModalContext = {
	modalRef: Ref<HTMLElement | undefined>;
	headerRef: Ref<HTMLElement | undefined>;
	footerRef: Ref<HTMLElement | undefined>;
	bodyId: Ref<string>;
	ns: UseNamespaceReturn;
	rendered: Ref<boolean>;
	style: ComputedRef<CSSProperties>;
};

export const modalContextKey: InjectionKey<ModalContext> = Symbol('modalContextKey');
