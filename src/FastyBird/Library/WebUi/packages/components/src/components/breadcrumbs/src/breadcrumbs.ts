import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const FB_BREADCRUMBS_TARGET = 'fb-breadcrumbs';

export const breadcrumbsProps = buildProps({
	/**
	 * @description
	 */
	target: {
		type: definePropType<string>(String),
		default: FB_BREADCRUMBS_TARGET,
	},
});

export type BreadcrumbItemProps = ExtractPropTypes<typeof breadcrumbsProps>;
