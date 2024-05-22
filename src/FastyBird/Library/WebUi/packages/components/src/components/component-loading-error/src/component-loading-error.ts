import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { useSizeProp } from 'element-plus';

import type { ExtractPropTypes } from 'vue';

export const componentLoadingErrorProps = buildProps({
	/**
	 * @description
	 */
	size: useSizeProp,
	/**
	 * @description
	 */
	text: {
		type: definePropType<string>(String),
		default: 'Content could not be loaded. Please reload page.',
	},
});

export type ComponentLoadingErrorProps = ExtractPropTypes<typeof componentLoadingErrorProps>;
