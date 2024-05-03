import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { ComponentSizeTypes, ComponentSize } from '@fastybird/web-ui-constants';

import type { ExtractPropTypes } from 'vue';
import type { Language } from '@fastybird/web-ui-locale';
import type { MessageConfigContext } from '../../message';

export const configProviderProps = buildProps({
	/**
	 * @description Controlling if the users want a11y features
	 */
	a11y: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description Locale Object
	 */
	locale: {
		type: definePropType<Language>(Object),
		default: 'en',
	},
	/**
	 * @description global component size
	 */
	size: {
		type: definePropType<ComponentSize>(String),
		values: [ComponentSizeTypes.SMALL, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.LARGE],
		default: ComponentSizeTypes.DEFAULT,
	},
	/**
	 * @description Controls if we should handle keyboard navigation
	 */
	keyboardNavigation: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description message related configuration, [see the following table](#message-attributes)
	 */
	message: {
		type: definePropType<MessageConfigContext>(Object),
	},
	/**
	 * @description global Initial zIndex
	 */
	zIndex: Number,
} as const);

export type ConfigProviderProps = ExtractPropTypes<typeof configProviderProps>;
