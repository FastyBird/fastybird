import CollapseTransition from './src/collapse-transition.vue';

import type { App } from 'vue';
import type { SFCWithInstall } from '@fastybird/web-ui-utils';

CollapseTransition.install = (app: App): void => {
	app.component(CollapseTransition.name as string, CollapseTransition);
};

const _CollapseTransition = CollapseTransition as SFCWithInstall<typeof CollapseTransition>;

export default _CollapseTransition;

export const FbCollapseTransition = _CollapseTransition;
