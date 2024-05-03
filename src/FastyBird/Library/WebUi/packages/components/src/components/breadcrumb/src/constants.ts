import type { InjectionKey } from 'vue';
import type { BreadcrumbProps } from './breadcrumb';

export const breadcrumbContextKey: InjectionKey<BreadcrumbProps> = Symbol('breadcrumbContextKey');
