import { componentSizeMap } from '@fastybird/web-ui-constants';

import type { ComponentSize } from '@fastybird/web-ui-constants';

export const getComponentSize = (size?: ComponentSize) => {
	return componentSizeMap[size || 'default'];
};
