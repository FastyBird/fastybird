import { withInstall, withNoopInstall } from '@fastybird/web-ui-utils';

import Breadcrumb from './src/breadcrumb.vue';
import BreadcrumbItem from './src/breadcrumb-item.vue';

export const FbBreadcrumb = withInstall(Breadcrumb, {
	BreadcrumbItem,
});

export const FbBreadcrumbItem = withNoopInstall(BreadcrumbItem);

export default FbBreadcrumb;

export * from './src/breadcrumb';
export * from './src/breadcrumb-item';
export * from './src/constants';

export type { BreadcrumbInstance, BreadcrumbItemInstance } from './src/instances';
