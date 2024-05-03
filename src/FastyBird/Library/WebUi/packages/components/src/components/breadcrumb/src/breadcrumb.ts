import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { Component, ExtractPropTypes } from 'vue';

export const breadcrumbProps = buildProps({
	/**
	 * @description separator character
	 */
	separator: {
		type: definePropType<string>(String),
		default: '/',
	},
	/**
	 * @description icon component of icon separator
	 */
	separatorIcon: {
		type: definePropType<string | Component | undefined>([String, Object, Function]),
		default: undefined,
	},
} as const);

export type BreadcrumbProps = ExtractPropTypes<typeof breadcrumbProps>;
