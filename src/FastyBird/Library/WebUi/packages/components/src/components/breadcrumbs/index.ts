import { withInstall } from '@fastybird/web-ui-utils';

import Breadcrumbs from './src/breadcrumbs.vue';

export const FbBreadcrumbs = withInstall(Breadcrumbs);

export default FbBreadcrumbs;

export * from './src/breadcrumbs';

export type { BreadcrumbsInstance } from './src/instance';
