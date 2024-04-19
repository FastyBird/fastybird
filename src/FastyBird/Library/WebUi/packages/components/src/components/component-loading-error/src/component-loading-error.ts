import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { ComponentSize, ComponentSizeTypes } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';

export const componentLoadingErrorProps = buildProps({
	/**
	 * @description
	 */
	size: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: ComponentSizeTypes.DEFAULT,
	},
	/**
	 * @description
	 */
	text: {
		type: definePropType<string>(String),
		default: 'Content could not be loaded. Please reload page.',
	},
});

export type ComponentLoadingErrorProps = ExtractPropTypes<typeof componentLoadingErrorProps>;
