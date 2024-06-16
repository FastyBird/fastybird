import ExpandableBox from './src/expandable-box.vue';

import type { App } from 'vue';
import type { SFCWithInstall } from '@fastybird/web-ui-utils';

ExpandableBox.install = (app: App): void => {
	app.component(ExpandableBox.name as string, ExpandableBox);
};

const _ExpandableBox = ExpandableBox as SFCWithInstall<typeof ExpandableBox>;

export default _ExpandableBox;

export const FbExpandableBox = _ExpandableBox;
