import { useSizeProp } from 'element-plus';

import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export const componentLoadingProps = buildProps({
	/**
	 * @description
	 */
	size: useSizeProp,
	/**
	 * @description
	 */
	text: {
		type: definePropType<string>(String),
		default: 'Preparing content, please wait...',
	},
});

export type ComponentLoadingProps = ExtractPropTypes<typeof componentLoadingProps>;
