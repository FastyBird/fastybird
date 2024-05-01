import ExpandTransition from './src/expand-transition.vue';

import type { App } from 'vue';
import type { SFCWithInstall } from '@fastybird/web-ui-utils';

ExpandTransition.install = (app: App): void => {
	app.component(ExpandTransition.name as string, ExpandTransition);
};

const _ExpandTransition = ExpandTransition as SFCWithInstall<typeof ExpandTransition>;

export default _ExpandTransition;

export const FbExpandTransition = _ExpandTransition;
